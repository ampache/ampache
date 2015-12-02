<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPLv3)
 * Copyright 2001 - 2015 Ampache.org
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

require_once 'lib/init.php';

UI::show_header();

// Switch on the incomming action
switch ($_REQUEST['action']) {
    case 'delete':
        if (AmpConfig::get('demo_mode')) {
            break;
        }

        $label_id = scrub_in($_REQUEST['label_id']);
        show_confirmation(
            T_('Label Deletion'),
            T_('Are you sure you want to permanently delete this label?'),
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
            debug_event('label', 'Unauthorized to remove the label `.' . $label->id . '`.', 1);
            UI::access_denied();
            exit;
        }

        if ($label->remove()) {
            show_confirmation(T_('Label Deletion'), T_('Label has been deleted.'), AmpConfig::get('web_path'));
        } else {
            show_confirmation(T_('Label Deletion'), T_('Cannot delete this label.'), AmpConfig::get('web_path'));
        }
    break;
    case 'add_label':
        // Must be at least a content manager or edit upload enabled
        if (!Access::check('interface','50') && !AmpConfig::get('upload_allow_edit')) {
            UI::access_denied();
            exit;
        }

        if (!Core::form_verify('add_label','post')) {
            UI::access_denied();
            exit;
        }

        // Remove unauthorized defined values from here
        if (isset($_POST['user'])) {
            unset($_POST['user']);
        }
        if (isset($_POST['creation_date'])) {
            unset($_POST['creation_date']);
        }

        $label_id = Label::create($_POST);
        if (!$label_id) {
            require_once AmpConfig::get('prefix') . UI::find_template('show_add_label.inc.php');
        } else {
            $body  = T_('Label Added');
            $title = '';
            show_confirmation($title, $body, AmpConfig::get('web_path') . '/browse.php?action=label');
        }
    break;
    case 'show':
        $label_id = intval($_REQUEST['label']);
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
            exit;
        }
    case 'show_add_label':
        if (Access::check('interface','50') || AmpConfig::get('upload_allow_edit')) {
            require_once AmpConfig::get('prefix') . UI::find_template('show_add_label.inc.php');
        } else {
            echo T_('Label cannot be found.');
        }
    break;
} // end switch

UI::show_footer();
