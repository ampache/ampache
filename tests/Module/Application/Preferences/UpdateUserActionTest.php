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
use Ampache\Module\Statistics\Stats;
use Ampache\Module\System\AmpError;
use Ampache\Module\System\Crypto\SymmetricEncrypterInterface;
use Ampache\Module\User\Activity\UserActivityPosterInterface;
use Ampache\Module\Util\RequestParserInterface;
use Ampache\Module\Util\UiInterface;
use Ampache\Repository\AlbumRepositoryInterface;
use Ampache\Repository\ArtistRepositoryInterface;
use Ampache\Repository\Model\User;
use Ampache\Repository\PodcastEpisodeRepositoryInterface;
use Ampache\Repository\PodcastRepositoryInterface;
use Ampache\Repository\SongRepositoryInterface;
use Ampache\Repository\UserActivityRepositoryInterface;
use Ampache\Repository\UserRepositoryInterface;
use Ampache\Repository\VideoRepositoryInterface;
use Mockery\MockInterface;
use Override;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use ReflectionProperty;

class UpdateUserActionTest extends MockeryTestCase
{
    private MockInterface|ConfigContainerInterface $configContainer;
    private MockInterface|PreferencesViewFactoryInterface $preferencesViewFactory;
    private MockInterface|RequestParserInterface $requestParser;
    private UpdateUserAction $subject;
    private MockInterface|SymmetricEncrypterInterface $symmetricEncrypter;
    private MockInterface|UiInterface $ui;
    private MockInterface|UserRepositoryInterface $userRepository;

    public function testPasswordsThatDoNotMatchChangeNothing(): void
    {
        $user = $this->createUser();
        $user->shouldNotReceive('update_fullname', 'update_password', 'upload_avatar');

        $this->runWith($user, [
            'fullname' => 'New Name',
            'password1' => 'one',
            'password2' => 'two',
        ]);

        self::assertTrue(AmpError::occurred());
    }

    // the form has no public full name box, so saving it used to make a name the admin had set public private again
    public function testTheFormChangesOnlyWhatItSends(): void
    {
        $user = $this->createUser();
        $user->shouldReceive('update_fullname')->with('New Name')->once();
        $user->shouldNotReceive('update_fullname_public', 'update_access', 'update_catalog_filter_group', 'update_username');
        $user->shouldReceive('upload_avatar')->withNoArgs()->once()->andReturnTrue();

        $this->runWith($user, [
            'fullname' => 'New Name',
            'access' => (string) AccessLevelEnum::ADMIN->value,
            'catalog_filter_group' => '2',
            'username' => 'someone-else',
        ]);
    }

    #[Override]
    protected function setUp(): void
    {
        new ReflectionProperty(AmpError::class, 'errors')->setValue(null, []);
        new ReflectionProperty(AmpError::class, 'state')->setValue(null, false);

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
            new Stats(
                $this->mock(AlbumRepositoryInterface::class),
                $this->mock(ArtistRepositoryInterface::class),
                $this->mock(PodcastEpisodeRepositoryInterface::class),
                $this->mock(PodcastRepositoryInterface::class),
                $this->mock(SongRepositoryInterface::class),
                $this->mock(UserActivityPosterInterface::class),
                $this->mock(UserActivityRepositoryInterface::class),
                $this->mock(VideoRepositoryInterface::class),
                $this->mock(LoggerInterface::class),
            ),
        );
    }

    private function createUser(): MockInterface|User
    {
        $user                  = $this->mock(User::class);
        $user->username        = 'some-username';
        $user->fullname        = 'Old Name';
        $user->fullname_public = true;
        $user->shouldReceive('getId')->withNoArgs()->andReturn(42);

        return $user;
    }

    /**
     * @param array<string, string> $post
     */
    private function runWith(User $user, array $post): void
    {
        $gatekeeper = $this->mock(GuiGatekeeperInterface::class);
        $gatekeeper->shouldReceive('isDemoMode')->andReturnFalse();
        $gatekeeper->shouldReceive('mayAccess')->with(AccessTypeEnum::INTERFACE, AccessLevelEnum::USER)->andReturnTrue();
        $gatekeeper->shouldReceive('getUser')->withNoArgs()->andReturnNull();
        $this->requestParser->shouldReceive('verifyForm')->with('update_user')->andReturnTrue();
        $this->configContainer->shouldReceive('isFeatureEnabled')->andReturnFalse();
        $this->configContainer->shouldReceive('getArray')
            ->with(ConfigurationKeyEnum::REGISTRATION_MANDATORY_FIELDS)
            ->andReturn([]);
        $this->ui->shouldReceive('showHeader', 'showQueryStats', 'showFooter')->withNoArgs();

        $_SESSION        = [];
        $_POST           = $post;
        $GLOBALS['user'] = $user;

        ob_start();

        try {
            self::assertNull($this->subject->run($this->mock(ServerRequestInterface::class), $gatekeeper));
        } finally {
            ob_get_clean();
            unset($GLOBALS['user']);
        }
    }
}
