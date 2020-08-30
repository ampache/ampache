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
use Core;
use Ampache\Module\Util\Ui;
use Ampache\Module\Util\Upload;

final class UploadApplication implements ApplicationInterface
{
    public function run(): void
    {
        if (!AmpConfig::get('allow_upload') || !Access::check('interface', 25)) {
            Ui::access_denied();

            return;
        }

        $upload_max = return_bytes(ini_get('upload_max_filesize'));
        $post_max   = return_bytes(ini_get('post_max_size'));
        if ($post_max > 0 && ($post_max < $upload_max || $upload_max == 0)) {
            $upload_max = $post_max;
        }
        // Check to handle POST requests exceeding max post size.
        if (Core::get_server('CONTENT_LENGTH') > 0 && $post_max > 0 && Core::get_server('CONTENT_LENGTH') > $post_max) {
            Upload::rerror();

            return;
        }

        // Switch on the actions
        switch ($_REQUEST['actionp']) {
            case 'upload':
                if (AmpConfig::get('demo_mode')) {
                    Ui::access_denied();

                    return;
                }

                Upload::process();

                return;

            default:
                Ui::show_header();
                require Ui::find_template('show_add_upload.inc.php');
                break;
        } // switch on the action

        // Show the Footer
        Ui::show_query_stats();
        Ui::show_footer();
    }
}
