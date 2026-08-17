<?php

declare(strict_types=1);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2026
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 */

namespace Ampache\Module\Application\Preferences;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Gui\Preferences\PreferencesViewFactoryInterface;
use Ampache\MockeryTestCase;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\System\Crypto\SymmetricEncrypterInterface;
use Ampache\Module\Util\RequestParserInterface;
use Ampache\Module\Util\UiInterface;
use Ampache\Repository\Model\User;
use Ampache\Repository\UserRepositoryInterface;
use Mockery;
use Mockery\MockInterface;
use Override;
use Psr\Http\Message\ServerRequestInterface;

class UpdateUserActionTest extends MockeryTestCase
{
    private MockInterface|ConfigContainerInterface $configContainer;
    private MockInterface|PreferencesViewFactoryInterface $preferencesViewFactory;
    private MockInterface|RequestParserInterface $requestParser;
    private UpdateUserAction $subject;
    private MockInterface|SymmetricEncrypterInterface $symmetricEncrypter;
    private MockInterface|UiInterface $ui;
    private MockInterface|UserRepositoryInterface $userRepository;

    /**
     * A self-service profile update must never let the request smuggle in `access` or `catalog_filter_group` --
     * both are admin-only fields on the exact same `User::update()` allowlist this action also uses.
     */
    public function testRunStripsAccessAndCatalogFilterGroupFromThePost(): void
    {
        $request        = $this->mock(ServerRequestInterface::class);
        $gatekeeper     = $this->mock(GuiGatekeeperInterface::class);
        $user           = $this->mock(User::class);
        $user->username = 'some-username';

        $_SESSION = [];
        $_POST    = [
            'fullname' => 'Some Name',
            'access' => (string) AccessLevelEnum::ADMIN->value,
            'catalog_filter_group' => '2',
        ];
        $GLOBALS['user'] = $user;

        try {
            $gatekeeper->shouldReceive('mayAccess')
                ->with(AccessTypeEnum::INTERFACE, AccessLevelEnum::USER)
                ->once()
                ->andReturnTrue();
            $this->requestParser->shouldReceive('verifyForm')
                ->with('update_user')
                ->once()
                ->andReturnTrue();
            $this->configContainer->shouldReceive('isFeatureEnabled')
                ->with(ConfigurationKeyEnum::SIMPLE_USER_MODE)
                ->once()
                ->andReturnFalse();
            $this->configContainer->shouldReceive('getArray')
                ->with(ConfigurationKeyEnum::REGISTRATION_MANDATORY_FIELDS)
                ->once()
                ->andReturn([]);

            $user->shouldReceive('update')
                ->once()
                ->with(Mockery::on(function (array $data): bool {
                    static::assertArrayNotHasKey('access', $data);
                    static::assertArrayNotHasKey('catalog_filter_group', $data);

                    return true;
                }))
                ->andReturnTrue();
            $user->shouldReceive('upload_avatar')
                ->withNoArgs()
                ->once()
                ->andReturnTrue();

            $this->ui->shouldReceive('showHeader')->withNoArgs()->once();
            $this->ui->shouldReceive('showQueryStats')->withNoArgs()->once();
            $this->ui->shouldReceive('showFooter')->withNoArgs()->once();

            $gatekeeper->shouldReceive('getUser')
                ->withNoArgs()
                ->once()
                ->andReturnNull();

            ob_start();

            try {
                $result = $this->subject->run($request, $gatekeeper);
            } finally {
                ob_get_clean();
            }

            $this->assertNull($result);
        } finally {
            unset($GLOBALS['user']);
        }
    }

    #[Override]
    protected function setUp(): void
    {
        $this->configContainer         = $this->mock(ConfigContainerInterface::class);
        $this->preferencesViewFactory  = $this->mock(PreferencesViewFactoryInterface::class);
        $this->requestParser           = $this->mock(RequestParserInterface::class);
        $this->symmetricEncrypter      = $this->mock(SymmetricEncrypterInterface::class);
        $this->ui                      = $this->mock(UiInterface::class);
        $this->userRepository          = $this->mock(UserRepositoryInterface::class);

        $this->subject = new UpdateUserAction(
            $this->preferencesViewFactory,
            $this->ui,
            $this->configContainer,
            $this->requestParser,
            $this->symmetricEncrypter,
            $this->userRepository,
        );
    }
}
