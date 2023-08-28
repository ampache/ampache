<?php
/*
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2023
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

namespace Ampache\Config\Init;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Module\System\Core;
use Ampache\Module\System\Session;

final class InitializationHandlerGlobals implements InitializationHandlerInterface
{
    private ConfigContainerInterface $configContainer;

    public function __construct(
        ConfigContainerInterface $configContainer
    ) {
        $this->configContainer = $configContainer;
    }

    public function init(): void
    {
        Core::get_global('user')->format(false);

        if (session_id()) {
            Session::extend(session_id());
            // We only need to create the tmp playlist if we have a session
            Core::get_global('user')->load_playlist();
        }

        $charset = $this->configContainer->get('site_charset');

        // Set CHARSET
        header("Content-Type: text/html; charset=" . $charset);

        // For the XMLRPC stuff
        $GLOBALS['xmlrpc_internalencoding'] = $charset;

        // If debug is on GIMMIE DA ERRORS
        if ($this->configContainer->get('debug')) {
            error_reporting(E_ALL);
        }
    }
}
