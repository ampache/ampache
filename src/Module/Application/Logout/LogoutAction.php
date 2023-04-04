<?php
/*
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 *  LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright 2001 - 2022 Ampache.org
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

use Ampache\Config\ConfigContainerInterface;
use Ampache\Module\Application\ApplicationActionInterface;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\Authentication\AuthenticationManagerInterface;
use Ampache\Module\System\LegacyLogger;
use Ampache\Module\System\Session;
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
        $this->logger                = $logger;
    }

    public function run(ServerRequestInterface $request, GuiGatekeeperInterface $gatekeeper): ?ResponseInterface
    {
        // only log out the session passed to the user, not just whatever the browser has stored
        $input = $request->getQueryParams();
        if (array_key_exists('session', $input) && Session::exists('interface', $input['session'])) {
            $sessionName    = $this->configContainer->get('session_name');
            $cookie_options = [
                'expires' => -1,
                'path' => (string)$this->configContainer->get('cookie_path'),
                'domain' => (string)$this->configContainer->get('cookie_domain'),
                'secure' => make_bool($this->configContainer->get('cookie_secure')),
                'samesite' => 'Strict'
            ];
            $this->logger->debug(
                'LogoutAction: ' . $sessionName,
                [LegacyLogger::CONTEXT_TYPE => __CLASS__]
            );
            // To end a legitimate session, just call logout.
            setcookie($sessionName . '_remember', '', $cookie_options);

            $this->authenticationManager->logout($input['session'], false);
        } else {
            header('Location: ' . $this->configContainer->get('web_path'));
        }

        return null;
    }
}
