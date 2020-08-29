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

use Ampache\Module\Access;
use Album;
use Ampache\Application\ApplicationInterface;
use AmpConfig;
use Artist;
use Ampache\Module\System\AutoUpdate;
use Core;
use Dba;
use Ampache\Module\Util\Horde_Browser;
use Song;
use Ampache\Module\Util\Ui;

final class SystemApplication implements ApplicationInterface
{
    private Horde_Browser $browser;

    public function __construct(
        Horde_Browser $browser
    ) {
        $this->browser = $browser;
    }

    public function run(): void
    {
        if (!Access::check('interface', 100) || AmpConfig::get('demo_mode')) {
            Ui::access_denied();

            return;
        }

        Ui::show_header();

        // Switch on the actions
        switch ($_REQUEST['action']) {
            /* This re-generates the config file comparing
             * /config/ampache.cfg to .cfg.dist
             */
            case 'generate_config':
                ob_end_clean();
                $current = parse_ini_file(__DIR__ . '/../../../config/ampache.cfg.php');
                $final   = generate_config($current);
                $this->browser->downloadHeaders('ampache.cfg.php', 'text/plain', false, filesize(__DIR__ . '/../../../config/ampache.cfg.php.dist'));
                echo $final;

                return;
            case 'write_config':
                write_config(__DIR__ . '/../../../config/ampache.cfg.php');
                header('Location: ' . AmpConfig::get('web_path') . '/index.php');

                return ;
            case 'reset_db_charset':
                Dba::reset_db_charset();
                show_confirmation(T_('No Problem'), T_('Your database and associated tables have been updated to match your currently configured charset'), AmpConfig::get('web_path') . '/admin/system.php?action=show_debug');
                break;
            case 'show_debug':
                $configuration = AmpConfig::get_all();
                if (Core::get_request('autoupdate') == 'force') {
                    $version = AutoUpdate::get_latest_version(true);
                }
                require_once Ui::find_template('show_debug.inc.php');
                break;
            case 'clear_cache':
                switch ($_REQUEST['type']) {
                    case 'song':
                        Song::clear_cache();
                        break;
                    case 'artist':
                        Artist::clear_cache();
                        break;
                    case 'album':
                        Album::clear_cache();
                        break;
                }
                show_confirmation(T_('No Problem'), T_('Your cache has been cleared successfully'), AmpConfig::get('web_path') . '/admin/system.php?action=show_debug');
                break;
            default:
                break;
        } // end switch

        // Show the Footer
        Ui::show_query_stats();
        Ui::show_footer();
    }
}
