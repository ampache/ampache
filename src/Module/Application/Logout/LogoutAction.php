<?php
/*
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 *  LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
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

namespace Ampache\Module\Application\Logout;

use Ampache\Config\AmpConfig;
use Ampache\Config\ConfigContainerInterface;
use Ampache\Module\Application\ApplicationActionInterface;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\Authentication\AuthenticationManagerInterface;
use Ampache\Module\System\LegacyLogger;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;

final class LogoutAction implements ApplicationActionInterface
{
    public const REQUEST_KEY = 'logout';

    private ConfigContainerInterface $configContainer;

    private AuthenticationManagerInterface $authenticationManager;

    private LoggerInterface $logger;

    public function __construct(
        ConfigContainerInterface $configContainer,
        AuthenticationManagerInterface $authenticationManager,
        LoggerInterface $logger
    ) {
        $this->configContainer       = $configContainer;
        $this->authenticationManager = $authenticationManager;
        $this->logger       = $logger;
    }

    public function run(ServerRequestInterface $request, GuiGatekeeperInterface $gatekeeper): ?ResponseInterface
    {
        $session_name   = $this->configContainer->getSessionName();
        $cookie_options = [
            'expires' => -1,
            'path' => (string)AmpConfig::get('cookie_path'),
            'domain' => (string)AmpConfig::get('cookie_domain'),
            'secure' => make_bool(AmpConfig::get('cookie_secure')),
            'samesite' => 'Strict'
        ];
        $this->logger->debug(
            sprintf('LogoutAction: {%d}', $session_name),
            [LegacyLogger::CONTEXT_TYPE => __CLASS__]
        );
        // To end a legitimate session, just call logout.
        setcookie($session_name . '_remember', null, $cookie_options);

        $this->authenticationManager->logout($session_name, false);

        return null;
    }
}
