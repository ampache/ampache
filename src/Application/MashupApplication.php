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

use Ampache\Module\Util\InterfaceImplementationChecker;
use Core;
use Ampache\Module\Util\Ui;

final class MashupApplication implements ApplicationInterface
{
    public function run(): void
    {
        session_start();

        $object_type = Core::get_request('action');
        if (!InterfaceImplementationChecker::is_library_item($object_type)) {
            Ui::access_denied();

            return;
        }

        Ui::show_header();
        require_once Ui::find_template('show_mashup.inc.php');

        // Show the Footer
        Ui::show_query_stats();
        Ui::show_footer();
    }
}
