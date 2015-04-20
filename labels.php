<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU General Public License, version 2 (GPLv2)
 * Copyright 2001 - 2015 Ampache.org
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License v2
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 *
 */

require_once 'lib/init.php';

UI::show_header();

// Switch on the incomming action
switch ($_REQUEST['action']) {
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
            require_once AmpConfig::get('prefix') . '/templates/show_add_label.inc.php';
        } else {
            $body = T_('Label Added');
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
            $object_ids = $label->get_artists();
            $object_type = 'artist';
            require_once AmpConfig::get('prefix') . '/templates/show_label.inc.php';
            UI::show_footer();
            exit;
        }
    case 'show_add_label':
        if (Access::check('interface','50') || AmpConfig::get('upload_allow_edit')) {
            require_once AmpConfig::get('prefix') . '/templates/show_add_label.inc.php';
        } else {
            echo T_('Label cannot be found.');
        }
    break;
} // end switch

UI::show_footer();
