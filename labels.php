<?php
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

$a_root = realpath(__DIR__);
require_once $a_root . '/lib/init.php';

UI::show_header();

// Switch on the actions
switch ($_REQUEST['action']) {
    case 'delete':
        if (AmpConfig::get('demo_mode')) {
            break;
        }

        $label_id = (string) scrub_in($_REQUEST['label_id']);
        show_confirmation(T_('Are You Sure?'),
            T_('This Label will be deleted'),
            AmpConfig::get('web_path') . "/labels.php?action=confirm_delete&label_id=" . $label_id,
            1,
            'delete_label'
        );
        break;
    case 'confirm_delete':
        if (AmpConfig::get('demo_mode')) {
            break;
        }

        $label = new Label($_REQUEST['label_id']);
        if (!Catalog::can_remove($label)) {
            debug_event('labels', 'Unauthorized to remove the label `.' . $label->id . '`.', 1);
            UI::access_denied();

            return false;
        }

        if ($label->remove()) {
            show_confirmation(T_('No Problem'), T_('The Label has been deleted'), AmpConfig::get('web_path'));
        } else {
            show_confirmation(T_("There Was a Problem"), T_("Unable to delete this Label."), AmpConfig::get('web_path'));
        }
        break;
    case 'add_label':
        // Must be at least a content manager or edit upload enabled
        if (!Access::check('interface', 50) && !AmpConfig::get('upload_allow_edit')) {
            UI::access_denied();

            return false;
        }

        if (!Core::form_verify('add_label', 'post')) {
            UI::access_denied();

            return false;
        }

        // Remove unauthorized defined values from here
        if (filter_has_var(INPUT_POST, 'user')) {
            unset($_POST['user']);
        }
        if (filter_has_var(INPUT_POST, 'creation_date')) {
            unset($_POST['creation_date']);
        }

        $label_id = Label::create($_POST);
        if (!$label_id) {
            require_once AmpConfig::get('prefix') . UI::find_template('show_add_label.inc.php');
        } else {
            show_confirmation(T_('No Problem'), T_('The Label has been added'), AmpConfig::get('web_path') . '/browse.php?action=label');
        }
        break;
    case 'show':
        $label_id = (int) filter_input(INPUT_GET, 'label', FILTER_SANITIZE_NUMBER_INT);
        if (!$label_id) {
            if (!empty($_REQUEST['name'])) {
                $label_id = Label::lookup($_REQUEST);
            }
        }
        if ($label_id > 0) {
            $label = new Label($label_id);
            $label->format();
            $object_ids  = $label->get_artists();
            $object_type = 'artist';
            require_once AmpConfig::get('prefix') . UI::find_template('show_label.inc.php');
            UI::show_footer();

            return false;
        }
        // Intentional break fall-through
    case 'show_add_label':
        if (Access::check('interface', 50) || AmpConfig::get('upload_allow_edit')) {
            require_once AmpConfig::get('prefix') . UI::find_template('show_add_label.inc.php');
        } else {
            echo T_('The Label cannot be found');
        }
        break;
} // end switch

// Show the Footer
UI::show_query_stats();
UI::show_footer();
