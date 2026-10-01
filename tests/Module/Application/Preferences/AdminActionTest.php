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
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\Util\UiInterface;
use Ampache\Repository\Model\User;
use Mockery\MockInterface;
use Override;
use Psr\Http\Message\ServerRequestInterface;

class AdminActionTest extends MockeryTestCase
{
    private MockInterface|ConfigContainerInterface $configContainer;
    private MockInterface|PreferencesViewFactoryInterface $preferencesViewFactory;
    private AdminAction $subject;
    private MockInterface|UiInterface $ui;

    public function testRunShowOptions(): void
    {
        $request    = $this->mock(ServerRequestInterface::class);
        $gatekeeper = $this->mock(GuiGatekeeperInterface::class);
        $user       = $this->mock(User::class);

        $tab         = 'some-tab';

        $gatekeeper->shouldReceive('mayAdminister')->once()->andReturnTrue();
        $gatekeeper->shouldReceive('getUser')
            ->withNoArgs()
            ->once()
            ->andReturn($user);

        $request->shouldReceive('getQueryParams')
            ->withNoArgs()
            ->once()
            ->andReturn(['tab' => $tab]);

        $this->ui->shouldReceive('showHeader')
            ->withNoArgs()
            ->once();
        // render() is final, so this is a real view with no tab -- the path that renders nothing
        $captured = null;
        $this->preferencesViewFactory->shouldReceive('create')
            ->once()
            ->andReturnUsing(function ($gate, $subject, $operator, $tab) use (&$captured, $user): PreferencesView {
                $captured = [$subject, $operator, $tab];

                return new PreferencesView('', $subject, [], '', false, false, new PreferenceInputRenderer());
            });

        $this->ui->shouldReceive('showQueryStats')
            ->withNoArgs()
            ->once();
        $this->ui->shouldReceive('showFooter')
            ->withNoArgs()
            ->once();

        ob_start();

        try {
            $result = $this->subject->run($request, $gatekeeper);
        } finally {
            $output = (string) ob_get_clean();
        }

        /** @var array{0: PreferenceSubject, 1: User, 2: string} $captured */
        $this->assertTrue($captured[0]->isServer, 'this screen edits the shared row');
        $this->assertSame($user, $captured[1], 'editability follows whoever is filling in the form');
        $this->assertSame($tab, $captured[2]);

        $this->assertNull($result);
        $this->assertSame('', $output);
    }

    public function testRunThrowsExceptionIfAccessIsDenied(): void
    {
        $request    = $this->mock(ServerRequestInterface::class);
        $gatekeeper = $this->mock(GuiGatekeeperInterface::class);

        $this->expectException(AccessDeniedException::class);

        $gatekeeper->shouldReceive('mayAdminister')->once()->andReturnFalse();

        $this->subject->run($request, $gatekeeper);
    }

    #[Override]
    protected function setUp(): void
    {
        $this->ui                     = $this->mock(UiInterface::class);
        $this->preferencesViewFactory = $this->mock(PreferencesViewFactoryInterface::class);

        $this->configContainer = $this->mock(ConfigContainerInterface::class);
        $this->configContainer->shouldReceive('isFeatureEnabled')->andReturnFalse()->byDefault();

        $this->subject = new AdminAction(
            $this->ui,
            $this->preferencesViewFactory,
            $this->configContainer,
        );
    }
}
