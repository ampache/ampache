<?php

declare(strict_types=0);

/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
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

namespace Ampache\Application;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Module\System\Session;
use Ampache\Module\Util\Ui;

final class LoginApplication implements ApplicationInterface
{
    private $configContainer;

    public function __construct(
        ConfigContainerInterface $configContainer
    ) {
        $this->configContainer = $configContainer;
    }

    public function run(): void
    {
        // Avoid form login if still connected
        if ($this->configContainer->get('use_auth') && !filter_has_var(INPUT_GET, 'force_display')) {
            $auth = false;
            if (Session::exists('interface', $_COOKIE[$this->configContainer->getSessionName()])) {
                $auth = true;
            } else {
                if (Session::auth_remember()) {
                    $auth = true;
                }
            }
            if ($auth) {
                header("Location: " . $this->configContainer->get('web_path'));

                return;
            }
        }
        require_once __DIR__ . '/../../src/Config/login.php';

        require Ui::find_template('show_login_form.inc.php');
    }
}
