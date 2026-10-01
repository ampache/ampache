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

namespace Ampache\Module\Application\Admin\User;

use Ampache\Gui\Preferences\PreferenceInputRenderer;
use Ampache\Gui\Preferences\PreferenceSubject;
use Ampache\Gui\Preferences\PreferencesView;
use Ampache\Gui\Preferences\PreferencesViewFactoryInterface;
use Ampache\MockeryTestCase;
use Ampache\Module\Application\Exception\AccessDeniedException;
use Ampache\Module\Application\Exception\ObjectNotFoundException;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\Util\UiInterface;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\Model\User;
use Mockery\MockInterface;
use Override;
use Psr\Http\Message\ServerRequestInterface;

class ShowPreferencesActionTest extends MockeryTestCase
{
    private MockInterface|ConfigContainerInterface $configContainer;
    private MockInterface|ModelFactoryInterface $modelFactory;
    private MockInterface|PreferencesViewFactoryInterface $preferencesViewFactory;
    private ShowPreferencesAction $subject;
    private MockInterface|UiInterface $ui;

    #[Override]
    public function setUp(): void
    {
        $this->ui                     = $this->mock(UiInterface::class);
        $this->modelFactory           = $this->mock(ModelFactoryInterface::class);
        $this->preferencesViewFactory = $this->mock(PreferencesViewFactoryInterface::class);

        $this->subject = new ShowPreferencesAction(
            $this->ui,
            $this->modelFactory,
            $this->preferencesViewFactory,
        );
    }

    public function testANonAdminIsRefusedBeforeAnythingIsLoaded(): void
    {
        $gatekeeper = $this->mock(GuiGatekeeperInterface::class);
        $gatekeeper->shouldReceive('mayAdminister')
            ->once()
            ->andReturnFalse();

        $this->modelFactory->shouldNotReceive('createUser');
        $this->ui->shouldNotReceive('showHeader');

        $this->expectException(AccessDeniedException::class);

        $this->subject->run($this->mock(ServerRequestInterface::class), $gatekeeper);
    }

    public function testAnUnknownAccountIsNotFound(): void
    {
        $request    = $this->mock(ServerRequestInterface::class);
        $gatekeeper = $this->mock(GuiGatekeeperInterface::class);
        $operator   = $this->mock(User::class);
        $target     = $this->mock(User::class);

        $gatekeeper->shouldReceive('mayAdminister')->andReturnTrue();
        $gatekeeper->shouldReceive('getUser')->andReturn($operator);
        $request->shouldReceive('getQueryParams')->andReturn(['user_id' => '666']);
        $this->modelFactory->shouldReceive('createUser')->with(666)->once()->andReturn($target);
        $target->shouldReceive('isNew')->once()->andReturnTrue();

        $this->ui->shouldNotReceive('showHeader');

        $this->expectException(ObjectNotFoundException::class);

        $this->subject->run($request, $gatekeeper);
    }

    /**
     * Demo mode grants every privilege, which is why the gatekeeper answers this question and not a level.
     */
    public function testDemoModeIsRefused(): void
    {
        $gatekeeper = $this->mock(GuiGatekeeperInterface::class);
        $gatekeeper->shouldReceive('mayAdminister')->once()->andReturnFalse();

        $this->modelFactory->shouldNotReceive('createUser');

        $this->expectException(AccessDeniedException::class);

        $this->subject->run($this->mock(ServerRequestInterface::class), $gatekeeper);
    }

    public function testItRendersTheRequestedAccountAndNotTheOperator(): void
    {
        $request    = $this->mock(ServerRequestInterface::class);
        $gatekeeper = $this->mock(GuiGatekeeperInterface::class);
        $operator   = $this->mock(User::class);
        $target     = $this->mock(User::class);

        $operator->fullname = 'admin';
        $target->fullname   = 'bituur';
        $operator->shouldReceive('getId')->andReturn(1);
        $target->shouldReceive('getId')->andReturn(7);
        $target->shouldReceive('isNew')->once()->andReturnFalse();

        $gatekeeper->shouldReceive('mayAdminister')->andReturnTrue();
        $gatekeeper->shouldReceive('getUser')->andReturn($operator);
        $request->shouldReceive('getQueryParams')->andReturn(['user_id' => '7', 'tab' => 'streaming']);
        $this->modelFactory->shouldReceive('createUser')->with(7)->once()->andReturn($target);

        $captured = null;
        $this->preferencesViewFactory->shouldReceive('create')
            ->once()
            ->andReturnUsing(function ($gate, $subject, $user, $tab) use (&$captured): PreferencesView {
                $captured = [$subject, $user, $tab];

                // render() is final, so this is a real view with no tab -- the path that renders nothing
                return new PreferencesView('', $subject, [], '', false, false, new PreferenceInputRenderer());
            });

        $this->ui->shouldReceive('showHeader')->once();
        $this->ui->shouldReceive('showQueryStats')->once();
        $this->ui->shouldReceive('showFooter')->once();

        ob_start();

        try {
            $this->subject->run($request, $gatekeeper);
        } finally {
            ob_get_clean();
        }

        /** @var array{0: PreferenceSubject, 1: User, 2: string} $captured */
        $this->assertSame(7, $captured[0]->userId, 'the subject is the account asked for');
        $this->assertSame('bituur', $captured[0]->label);
        $this->assertFalse($captured[0]->isSelf);
        $this->assertSame($operator, $captured[1], 'editability follows the operator, not the subject');
        $this->assertSame('streaming', $captured[2]);
    }

    /**
     * The account form always renders and writes the signed-in account, whatever the page is titled.
     */
    public function testTheAccountTabIsNeverShownForSomeoneElse(): void
    {
        $request    = $this->mock(ServerRequestInterface::class);
        $gatekeeper = $this->mock(GuiGatekeeperInterface::class);
        $operator   = $this->mock(User::class);
        $target     = $this->mock(User::class);

        $operator->fullname = 'admin';
        $target->fullname   = 'bituur';
        $operator->shouldReceive('getId')->andReturn(1);
        $target->shouldReceive('getId')->andReturn(7);
        $target->shouldReceive('isNew')->andReturnFalse();

        $gatekeeper->shouldReceive('mayAdminister')->andReturnTrue();
        $gatekeeper->shouldReceive('getUser')->andReturn($operator);
        $request->shouldReceive('getQueryParams')->andReturn(['user_id' => '7', 'tab' => 'account']);
        $this->modelFactory->shouldReceive('createUser')->with(7)->andReturn($target);

        $captured = null;
        $this->preferencesViewFactory->shouldReceive('create')
            ->andReturnUsing(function ($gate, $subject, $user, $tab) use (&$captured): PreferencesView {
                $captured = $tab;

                return new PreferencesView('', $subject, [], '', false, false, new PreferenceInputRenderer());
            });
        $this->ui->shouldReceive('showHeader')->once();
        $this->ui->shouldReceive('showQueryStats')->once();
        $this->ui->shouldReceive('showFooter')->once();

        ob_start();

        try {
            $this->subject->run($request, $gatekeeper);
        } finally {
            ob_get_clean();
        }

        $this->assertSame('interface', $captured, 'the account tab would show the admin their own form');
    }
}
