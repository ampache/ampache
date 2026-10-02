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
use Ampache\Gui\Preferences\PreferenceInputRenderer;
use Ampache\Gui\Preferences\PreferenceSubject;
use Ampache\Gui\Preferences\PreferencesView;
use Ampache\Gui\Preferences\PreferencesViewFactoryInterface;
use Ampache\MockeryTestCase;
use Ampache\Module\Application\Exception\AccessDeniedException;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\System\PreferencesFromRequestUpdaterInterface;
use Ampache\Module\Util\RequestParserInterface;
use Ampache\Module\Util\UiInterface;
use Ampache\Repository\Model\User;
use Ampache\Repository\PreferenceRepositoryInterface;
use DI\Container;
use Mockery\MockInterface;
use Override;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;

/**
 * Writes either your own preferences or the server's, so what decides between the two matters as much
 * as the guards do.
 */
class UpdatePreferencesActionTest extends MockeryTestCase
{
    private MockInterface|ConfigContainerInterface $configContainer;
    private MockInterface|PreferenceRepositoryInterface $preferenceRepository;
    private MockInterface|RequestParserInterface $requestParser;
    private UpdatePreferencesAction $subject;
    private MockInterface|UiInterface $ui;
    private MockInterface|PreferencesFromRequestUpdaterInterface $updater;
    private MockInterface|PreferencesViewFactoryInterface $viewFactory;

    #[Override]
    public function setUp(): void
    {
        $_POST    = [];
        $_REQUEST = [];

        // `Preference::init()` and `load_gettext()` reach the container through the global bridge
        $this->configContainer      = $this->mock(ConfigContainerInterface::class);
        $this->configContainer->shouldReceive('updateConfig')->andReturnSelf()->byDefault();
        $this->preferenceRepository = $this->mock(PreferenceRepositoryInterface::class);
        $this->preferenceRepository->shouldReceive('getInitRows')->andReturn([])->byDefault();

        $dic = $this->mock(Container::class);
        $dic->shouldReceive('get')->andReturnUsing(fn(string $id): object => match ($id) {
            PreferenceRepositoryInterface::class => $this->preferenceRepository,
            ConfigContainerInterface::class => $this->configContainer,
            default => $this->mock(LoggerInterface::class),
        });
        $GLOBALS['dic'] = $dic;

        $this->updater         = $this->mock(PreferencesFromRequestUpdaterInterface::class);
        $this->requestParser   = $this->mock(RequestParserInterface::class);
        $this->ui              = $this->mock(UiInterface::class);

        $this->viewFactory = $this->mock(PreferencesViewFactoryInterface::class);

        $this->subject = new UpdatePreferencesAction(
            $this->viewFactory,
            $this->updater,
            $this->ui,
            $this->requestParser,
        );
    }

    #[Override]
    public function tearDown(): void
    {
        $_POST    = [];
        $_REQUEST = [];
    }

    public function testAMissingFormTokenWritesNothing(): void
    {
        $this->requestParser->shouldReceive('verifyForm')->with('update_preference')->once()->andReturnFalse();
        $this->updater->shouldNotReceive('update');

        $this->expectException(AccessDeniedException::class);

        $this->subject->run($this->mock(ServerRequestInterface::class), $this->gatekeeper());
    }

    public function testANonAdminCannotWriteTheServerPreferences(): void
    {
        $_POST['method'] = 'admin';

        $this->requestParser->shouldReceive('verifyForm')->andReturnTrue();
        $this->updater->shouldNotReceive('update');

        $this->expectException(AccessDeniedException::class);

        $this->subject->run($this->mock(ServerRequestInterface::class), $this->gatekeeper(admin: false));
    }

    /**
     * Demo mode grants every privilege, and `Preference::update_level()` has no check of its own.
     */
    public function testDemoModeWritesNothing(): void
    {
        $this->updater->shouldNotReceive('update');

        $this->expectException(AccessDeniedException::class);

        $this->subject->run($this->mock(ServerRequestInterface::class), $this->gatekeeper(demo: true));
    }

    public function testTheServerPathWritesTheSharedRowAndNotAnAccount(): void
    {
        $_POST['method'] = 'admin';
        $_REQUEST['tab'] = 'system';

        $this->requestParser->shouldReceive('verifyForm')->andReturnTrue();
        $this->updater->shouldReceive('update')->with(User::INTERNAL_SYSTEM_USER_ID)->once();

        $this->viewFactory->shouldReceive('create')->once()->andReturn(
            new PreferencesView('/amp', PreferenceSubject::serverPreferences($this->operator()), [], 'system', true, false, new PreferenceInputRenderer())
        );
        $this->ui->shouldReceive('showHeader')->once();
        $this->ui->shouldReceive('showQueryStats')->once();
        $this->ui->shouldReceive('showFooter')->once();

        $request = $this->mock(ServerRequestInterface::class);
        $request->shouldReceive('getParsedBody')->andReturn(['tab' => 'system']);

        ob_start();

        try {
            $result = $this->subject->run($request, $this->gatekeeper());
        } finally {
            ob_get_clean();
        }

        $this->assertNull($result);
    }

    private function gatekeeper(bool $admin = true, bool $demo = false): MockInterface|GuiGatekeeperInterface
    {
        $gatekeeper = $this->mock(GuiGatekeeperInterface::class);
        $gatekeeper->shouldReceive('mayAccess')
            ->with(AccessTypeEnum::INTERFACE, AccessLevelEnum::USER)
            ->andReturnTrue();
        $gatekeeper->shouldReceive('mayAdminister')->andReturn($admin);
        $gatekeeper->shouldReceive('isDemoMode')->andReturn($demo);
        $gatekeeper->shouldReceive('getUser')->andReturn($this->operator());

        return $gatekeeper;
    }

    private function operator(): MockInterface|User
    {
        $user           = $this->mock(User::class);
        $user->fullname = 'operator';
        $user->shouldReceive('getId')->andReturn(42);

        return $user;
    }
}
