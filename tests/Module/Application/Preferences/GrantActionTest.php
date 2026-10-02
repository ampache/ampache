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
use Ampache\Gui\Preferences\PreferencesViewFactoryInterface;
use Ampache\MockeryTestCase;
use Ampache\Module\Application\Exception\AccessDeniedException;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\Util\RequestParserInterface;
use Ampache\Module\Util\UiInterface;
use Ampache\Repository\Model\User;
use Mockery\MockInterface;
use Override;
use Psr\Http\Message\ServerRequestInterface;

/**
 * This action writes a third-party session key onto the account, so a forged callback would hand the
 * listener's scrobbles to whoever sent them the link.
 */
class GrantActionTest extends MockeryTestCase
{
    private MockInterface|RequestParserInterface $requestParser;
    private GrantAction $subject;
    private MockInterface|UiInterface $ui;

    #[Override]
    public function setUp(): void
    {
        $this->ui            = $this->mock(UiInterface::class);
        $this->requestParser = $this->mock(RequestParserInterface::class);

        $this->subject = new GrantAction(
            $this->mock(PreferencesViewFactoryInterface::class),
            $this->requestParser,
            $this->ui,
            $this->mock(ConfigContainerInterface::class),
        );
    }

    /**
     * Without this, an image tag pointing at the callback binds the victim's account to the attacker's.
     */
    public function testACallbackWithoutTheTokenIsRefused(): void
    {
        $this->requestParser->shouldReceive('verifyFormFromQuery')->with('grant')->once()->andReturnFalse();
        $this->ui->shouldNotReceive('showHeader');

        $this->expectException(AccessDeniedException::class);

        $this->subject->run($this->mock(ServerRequestInterface::class), $this->gatekeeper(true, $this->user()));
    }

    public function testAnAccountBelowUserLevelIsRefusedEvenThoughItHasAnId(): void
    {
        $this->requestParser->shouldReceive('verifyFormFromQuery')->andReturnTrue();
        $this->ui->shouldNotReceive('showHeader');

        $this->expectException(AccessDeniedException::class);

        $this->subject->run($this->mock(ServerRequestInterface::class), $this->gatekeeper(false, $this->user()));
    }

    public function testAVisitorWithNoAccountIsRefused(): void
    {
        $this->requestParser->shouldReceive('verifyFormFromQuery')->andReturnTrue();
        $this->ui->shouldNotReceive('showHeader');

        $this->expectException(AccessDeniedException::class);

        $this->subject->run($this->mock(ServerRequestInterface::class), $this->gatekeeper(true, null));
    }

    private function gatekeeper(bool $mayUse, ?User $user): MockInterface|GuiGatekeeperInterface
    {
        $gatekeeper = $this->mock(GuiGatekeeperInterface::class);
        $gatekeeper->shouldReceive('getUser')->andReturn($user);
        $gatekeeper->shouldReceive('mayAccess')
            ->with(AccessTypeEnum::INTERFACE, AccessLevelEnum::USER)
            ->andReturn($mayUse);

        return $gatekeeper;
    }

    private function user(): MockInterface|User
    {
        $user     = $this->mock(User::class);
        $user->id = 42;

        return $user;
    }
}
