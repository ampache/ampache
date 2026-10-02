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
use Ampache\MockeryTestCase;
use Ampache\Module\Application\Exception\AccessDeniedException;
use Ampache\Module\Application\Exception\ObjectNotFoundException;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\System\PreferencesFromRequestUpdaterInterface;
use Ampache\Module\Util\RequestParserInterface;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\Model\User;
use Mockery\MockInterface;
use Nyholm\Psr7\Factory\Psr17Factory;
use Override;
use Psr\Http\Message\ServerRequestInterface;

/**
 * This is the only path where an admin writes into someone else's preferences, so every refusal has to
 * happen before the updater is reached.
 */
class AdminUpdatePreferencesActionTest extends MockeryTestCase
{
    private MockInterface|ConfigContainerInterface $configContainer;
    private MockInterface|ModelFactoryInterface $modelFactory;
    private MockInterface|RequestParserInterface $requestParser;
    private AdminUpdatePreferencesAction $subject;
    private MockInterface|PreferencesFromRequestUpdaterInterface $updater;

    #[Override]
    public function setUp(): void
    {
        $_POST = [];

        $this->updater         = $this->mock(PreferencesFromRequestUpdaterInterface::class);
        $this->configContainer = $this->mock(ConfigContainerInterface::class);
        $this->requestParser   = $this->mock(RequestParserInterface::class);
        $this->modelFactory    = $this->mock(ModelFactoryInterface::class);

        $this->subject = new AdminUpdatePreferencesAction(
            $this->updater,
            new Psr17Factory(),
            $this->configContainer,
            $this->requestParser,
            $this->modelFactory,
        );
    }

    #[Override]
    public function tearDown(): void
    {
        $_POST = [];
    }

    public function testAMissingFormTokenWritesNothing(): void
    {
        $this->configContainer->shouldReceive('isFeatureEnabled')->andReturnFalse();
        $this->requestParser->shouldReceive('verifyForm')->with('update_preference')->once()->andReturnFalse();
        $this->updater->shouldNotReceive('update');

        $this->expectException(AccessDeniedException::class);

        $this->subject->run($this->mock(ServerRequestInterface::class), $this->gatekeeper(true));
    }

    public function testANonAdminWritesNothing(): void
    {
        $this->updater->shouldNotReceive('update');

        $this->expectException(AccessDeniedException::class);

        $this->subject->run($this->mock(ServerRequestInterface::class), $this->gatekeeper(false));
    }

    public function testAnUnknownAccountIsRefusedBeforeAnythingIsWritten(): void
    {
        $_POST['user_id'] = '7';

        $this->configContainer->shouldReceive('isFeatureEnabled')->andReturnFalse();
        $this->requestParser->shouldReceive('verifyForm')->andReturnTrue();
        $user = $this->mock(User::class);
        $user->shouldReceive('isNew')->andReturnTrue();
        $this->modelFactory->shouldReceive('createUser')->with(7)->andReturn($user);
        $this->updater->shouldNotReceive('update');

        $this->expectException(ObjectNotFoundException::class);

        $this->subject->run($this->mock(ServerRequestInterface::class), $this->gatekeeper(true));
    }

    public function testThePostedAccountIsTheOneWrittenTo(): void
    {
        $_POST['user_id'] = '7';
        $_POST['tab']     = 'interface';

        $this->configContainer->shouldReceive('isFeatureEnabled')->andReturnFalse();
        $this->configContainer->shouldReceive('getWebPath')->andReturn('/amp/admin');
        $this->requestParser->shouldReceive('verifyForm')->andReturnTrue();
        $this->modelFactory->shouldReceive('createUser')->with(7)->once()->andReturn($this->existingUser());
        $this->updater->shouldReceive('update')->with(7)->once();

        $response = $this->subject->run($this->mock(ServerRequestInterface::class), $this->gatekeeper(true));

        $this->assertSame(302, $response->getStatusCode());
        $this->assertStringContainsString('user_id=7', $response->getHeaderLine('Location'));
    }

    /**
     * The server row has its own action and its own guard, so this one must refuse anything but an account.
     */
    public function testTheServerRowCannotBeWrittenThroughThisAction(): void
    {
        $_POST['user_id'] = (string) User::INTERNAL_SYSTEM_USER_ID;

        $this->configContainer->shouldReceive('isFeatureEnabled')->andReturnFalse();
        $this->requestParser->shouldReceive('verifyForm')->andReturnTrue();
        $this->modelFactory->shouldNotReceive('createUser');
        $this->updater->shouldNotReceive('update');

        $this->expectException(ObjectNotFoundException::class);

        $this->subject->run($this->mock(ServerRequestInterface::class), $this->gatekeeper(true));
    }

    private function existingUser(): MockInterface|User
    {
        $user = $this->mock(User::class);
        $user->shouldReceive('isNew')->andReturnFalse();

        return $user;
    }

    private function gatekeeper(bool $admin): MockInterface|GuiGatekeeperInterface
    {
        $gatekeeper = $this->mock(GuiGatekeeperInterface::class);
        $gatekeeper->shouldReceive('mayAdminister')->andReturn($admin);

        return $gatekeeper;
    }
}
