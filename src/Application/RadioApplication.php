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

use Ampache\Module\Authorization\Access;
use Ampache\Config\AmpConfig;
use Ampache\Module\System\Core;
use Ampache\Model\Live_Stream;
use Ampache\Module\Util\Ui;

final class RadioApplication implements ApplicationInterface
{
    public function run(): void
    {
        if (!AmpConfig::get('Ampache\Model\Live_Stream')) {
            Ui::access_denied();

            return;
        }

        Ui::show_header();

        // Switch on the actions
        switch ($_REQUEST['action']) {
            case 'show_create':
                if (!Access::check('interface', 75)) {
                    Ui::access_denied();

                    return;
                }

                require_once Ui::find_template('show_add_live_stream.inc.php');

                break;
            case 'create':
                if (!Access::check('interface', 75) || AmpConfig::get('demo_mode')) {
                    Ui::access_denied();

                    return;
                }

                if (!Core::form_verify('add_radio', 'post')) {
                    Ui::access_denied();

                    return;
                }

                // Try to create the sucker
                $results = Live_Stream::create($_POST);

                if (!$results) {
                    require_once Ui::find_template('show_add_live_stream.inc.php');
                } else {
                    $body  = T_('Radio Station created');
                    $title = '';
                    show_confirmation($title, $body, AmpConfig::get('web_path') . '/browse.php?action=live_stream');
                }
                break;
            case 'show':
            default:
                $radio = new Live_Stream($_REQUEST['radio']);
                $radio->format();
                require Ui::find_template('show_live_stream.inc.php');
                break;
        } // end data collection

        // Show the Footer
        Ui::show_query_stats();
        Ui::show_footer();
    }
}
