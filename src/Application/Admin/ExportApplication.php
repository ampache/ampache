<?php

declare(strict_types=0);

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

namespace Ampache\Application\Admin;

use Ampache\Module\Authorization\Access;
use Ampache\Application\ApplicationInterface;
use AmpConfig;
use Catalog;
use Ampache\Module\Util\Ui;

final class ExportApplication implements ApplicationInterface
{
    public function run(): void
    {
        if (!Access::check('interface', 75)) {
            Ui::access_denied();

            return;
        }

        Ui::show_header();

        // Switch on the actions
        switch ($_REQUEST['action']) {
            case 'export':
                // This may take a while
                set_time_limit(0);

                // Clear everything we've done so far
                ob_end_clean();

                // This will disable buffering so contents are sent immediately to browser.
                // This is very useful for large catalogs because it will immediately display the download dialog to user,
                // instead of waiting until contents are generated, which could take a long time.
                ob_implicit_flush(1);

                header("Content-Transfer-Encoding: binary");
                header("Cache-control: public");

                $date = get_datetime(time(), 'short', 'none', 'y-MM-dd');

                switch ($_REQUEST['export_format']) {
                    case 'itunes':
                        header("Content-Type: application/itunes+xml; charset=utf-8");
                        header("Content-Disposition: attachment; filename=\"ampache-itunes-$date.xml\"");
                        Catalog::export('itunes', $_REQUEST['export_catalog']);
                        break;
                    case 'csv':
                        header("Content-Type: application/vnd.ms-excel");
                        header("Content-Disposition: filename=\"ampache-export-$date.csv\"");
                        Catalog::export('csv', $_REQUEST['export_catalog']);
                        break;
                } // end switch on format

                // We don't want the footer so we're done here
                return;
            default:
                require_once Ui::find_template('show_export.inc.php');
                break;
        } // end switch on action

        // Show the Footer
        Ui::show_query_stats();
        Ui::show_footer();
    }
}
