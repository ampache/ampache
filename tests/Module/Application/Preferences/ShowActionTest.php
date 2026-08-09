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

use Ampache\Gui\Preferences\PreferencesView;
use Ampache\Gui\Preferences\PreferencesViewFactoryInterface;
use Ampache\MockeryTestCase;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\Util\UiInterface;
use Ampache\Repository\Model\User;
use Mockery\MockInterface;
use Override;
use Psr\Http\Message\ServerRequestInterface;

class ShowActionTest extends MockeryTestCase
{
    private MockInterface|PreferencesViewFactoryInterface $preferencesViewFactory;
    private ShowAction $subject;
    private MockInterface|UiInterface $ui;

    public function testRunShowOptions(): void
    {
        $request    = $this->mock(ServerRequestInterface::class);
        $gatekeeper = $this->mock(GuiGatekeeperInterface::class);
        $user       = $this->mock(User::class);

        $fullname    = 'some-name';
        $preferences = ['some' => 'preference'];
        $tab         = 'some-tab';

        $user->fullname = $fullname;

        $gatekeeper->shouldReceive('getUser')
            ->withNoArgs()
            ->once()
            ->andReturn($user);

        $request->shouldReceive('getQueryParams')
            ->withNoArgs()
            ->once()
            ->andReturn(['tab' => $tab]);

        $user->shouldReceive('get_preferences')
            ->with($tab)
            ->once()
            ->andReturn($preferences);

        $this->ui->shouldReceive('showHeader')
            ->withNoArgs()
            ->once();
        // render() is final, so this is a real view with no tab -- the path that renders nothing
        $this->preferencesViewFactory->shouldReceive('create')
            ->once()
            ->andReturn(new PreferencesView($this->ui, '', '', [], '', '', 0, false, false));

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

        $this->assertNull($result);
        $this->assertSame('', $output);
    }

    #[Override]
    protected function setUp(): void
    {
        $this->ui                     = $this->mock(UiInterface::class);
        $this->preferencesViewFactory = $this->mock(PreferencesViewFactoryInterface::class);

        $this->subject = new ShowAction(
            $this->ui,
            $this->preferencesViewFactory,
        );
    }
}
