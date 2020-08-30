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
use Ampache\Module\Util\InterfaceImplementationChecker;
use Ampache\Config\AmpConfig;
use Ampache\Module\System\AmpError;
use Core;
use Ampache\Model\Shoutbox;
use Ampache\Module\Util\Ui;

final class ShoutApplication implements ApplicationInterface
{
    public function run(): void
    {
        Ui::show_header();

        // Switch on the actions
        switch ($_REQUEST['action']) {
            case 'add_shout':
                // Must be at least a user to do this
                if (!Access::check('interface', 25)) {
                    Ui::access_denied();

                    return;
                }

                if (!Core::form_verify('add_shout', 'post')) {
                    Ui::access_denied();

                    return;
                }

                // Remove unauthorized defined values from here
                if (filter_has_var(INPUT_POST, 'user')) {
                    unset($_POST['user']);
                }
                if (filter_has_var(INPUT_POST, 'date')) {
                    unset($_POST['date']);
                }

                if (!InterfaceImplementationChecker::is_library_item(Core::get_post('object_type'))) {
                    Ui::access_denied();

                    return;
                }

                $shout_id = Shoutbox::create($_POST);
                header("Location:" . AmpConfig::get('web_path') . '/shout.php?action=show_add_shout&type=' . $_POST['object_type'] . '&id=' . (int) ($_POST['object_id']));

                return;
            case 'show_add_shout':
                // Get our object first
                $object = Shoutbox::get_object($_REQUEST['type'], (int) Core::get_request('id'));

                if (!$object || !$object->id) {
                    AmpError::add('general', T_('Invalid object selected'));
                    AmpError::display('general');
                    break;
                }

                $object->format();
                if (get_class($object) == 'Song') {
                    $data = $_REQUEST['offset'];
                }

                // Now go ahead and display the page where we let them add a comment etc
                require_once Ui::find_template('show_add_shout.inc.php');
                break;
            default:
                header("Location:" . AmpConfig::get('web_path'));
                break;
        } // end switch on action

        // Show the Footer
        Ui::show_query_stats();
        Ui::show_footer();
    }
}
