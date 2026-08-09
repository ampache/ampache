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

namespace Ampache\Module\Application\Login;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Gui\Form\LoginFormViewFactoryInterface;
use Ampache\Module\Application\Exception\AccessDeniedException;
use Ampache\Module\Authentication\Oidc\OidcAuthenticationServiceInterface;
use Ampache\Module\Authorization\Check\NetworkCheckerInterface;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;
use Override;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;

class OidcActionTest extends MockeryTestCase
{
    private MockInterface|ConfigContainerInterface|null $configContainer;
    private MockInterface|LoggerInterface|null $logger;
    private LoginFormViewFactoryInterface|MockInterface|null $loginFormViewFactory;
    private MockInterface|NetworkCheckerInterface|null $networkChecker;
    private MockInterface|OidcAuthenticationServiceInterface|null $oidcAuthenticationService;
    private ?OidcAction $subject;

    public function testRunThrowsIfOidcIsNotEnabled(): void
    {
        $request    = Mockery::mock(ServerRequestInterface::class);
        $gatekeeper = Mockery::mock(GuiGatekeeperInterface::class);

        $this->configContainer->shouldReceive('get')
            ->with(ConfigurationKeyEnum::AUTH_METHODS)
            ->once()
            ->andReturn(['mysql', 'ldap']);

        $this->oidcAuthenticationService->shouldNotReceive('redirectToProvider');

        static::expectException(AccessDeniedException::class);

        $this->subject->run($request, $gatekeeper);
    }

    #[Override]
    protected function setUp(): void
    {
        $this->configContainer           = Mockery::mock(ConfigContainerInterface::class);
        $this->logger                    = Mockery::mock(LoggerInterface::class);
        $this->networkChecker            = Mockery::mock(NetworkCheckerInterface::class);
        $this->oidcAuthenticationService = Mockery::mock(OidcAuthenticationServiceInterface::class);
        $this->loginFormViewFactory      = Mockery::mock(LoginFormViewFactoryInterface::class);

        $this->subject = new OidcAction(
            $this->configContainer,
            $this->logger,
            $this->networkChecker,
            $this->oidcAuthenticationService,
            $this->loginFormViewFactory
        );
    }
}
