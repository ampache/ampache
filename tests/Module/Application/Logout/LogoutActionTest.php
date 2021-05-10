<?php
/*
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright 2001 - 2020 Ampache.org
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

declare(strict_types=1);

namespace Ampache\Module\Application\Logout;

use Ampache\Config\ConfigContainerInterface;
use Ampache\MockeryTestCase;
use Ampache\Module\Authentication\AuthenticationManagerInterface;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\Util\CookieSetterInterface;
use Mockery\MockInterface;
use Psr\Http\Message\ServerRequestInterface;

class LogoutActionTest extends MockeryTestCase
{
    /** @var ConfigContainerInterface|MockInterface|null */
    private MockInterface $configContainer;

    /** @var AuthenticationManagerInterface|MockInterface|null */
    private MockInterface $authenticationManager;

    /** @var CookieSetterInterface|MockInterface|null */
    private MockInterface $cookieSetter;

    private LogoutAction $subject;

    public function setUp(): void
    {
        $this->configContainer       = $this->mock(ConfigContainerInterface::class);
        $this->authenticationManager = $this->mock(AuthenticationManagerInterface::class);
        $this->cookieSetter          = $this->mock(CookieSetterInterface::class);

        $this->subject = new LogoutAction(
            $this->configContainer,
            $this->authenticationManager,
            $this->cookieSetter
        );
    }

    public function testRunPerformsLogout(): void
    {
        $request    = $this->mock(ServerRequestInterface::class);
        $gatekeeper = $this->mock(GuiGatekeeperInterface::class);

        $sessionName = 'some-name';

        $this->configContainer->shouldReceive('getSessionName')
            ->withNoArgs()
            ->once()
            ->andReturn($sessionName);

        $this->cookieSetter->shouldReceive('set')
            ->with(
                sprintf('%s_remember', $sessionName),
                '',
                [
                    'expires' => -1,
                    'samesite' => 'Strict',
                ]
            )
            ->once();

        $this->authenticationManager->shouldReceive('logout')
            ->with('', false)
            ->once();

        $this->assertNull(
            $this->subject->run(
                $request,
                $gatekeeper
            )
        );
    }
}
