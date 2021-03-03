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

$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : '';

if (empty($action) || $action == 'stream' || $action == 'download') {
    define('NO_SESSION', '1');
    define('OUTDATED_DATABASE_OK', 1);
}
$a_root = realpath(__DIR__);
require_once $a_root . '/lib/init.php';

Preference::init();

if (!AmpConfig::get('share')) {
    debug_event('share', 'Access Denied: sharing features are not enabled.', 3);
    UI::access_denied();

    return false;
}

switch ($_REQUEST['action']) {
    case 'show_create':
        UI::show_header();

        $type = Share::format_type(Core::get_request('type'));
        if (!empty($type) && !empty($_REQUEST['id'])) {
            $object_id = Core::get_request('id');
            if (is_array($object_id)) {
                $object_id = $object_id[0];
            }

            $object = new $type($object_id);
            if ($object->id) {
                $object->format();
                require_once AmpConfig::get('prefix') . UI::find_template('show_add_share.inc.php');
            }
        }
        UI::show_footer();

        return false;
    case 'create':
        if (AmpConfig::get('demo_mode')) {
            UI::access_denied();

            return false;
        }

        if (!Core::form_verify('add_share', 'post')) {
            UI::access_denied();

            return false;
        }

        UI::show_header();
        $share_id = Share::create_share($_REQUEST['type'], (int) $_REQUEST['id'], make_bool($_REQUEST['allow_stream']), make_bool($_REQUEST['allow_download']), (int) $_REQUEST['expire'], $_REQUEST['secret'], (int) $_REQUEST['max_counter']);

        if (!$share_id) {
            require_once AmpConfig::get('prefix') . UI::find_template('show_add_share.inc.php');
        } else {
            $share = new Share($share_id);
            $body  = T_('Share created') . '<br />' .
                T_('You can now start sharing the following URL:') . '<br />' .
                '<a href="' . $share->public_url . '" target="_blank">' . $share->public_url . '</a><br />' .
                '<div id="share_qrcode" style="text-align: center"></div>' .
                '<script>$(\'#share_qrcode\').qrcode({text: "' . $share->public_url . '", width: 128, height: 128});</script>' .
                '<br /><br />' .
                T_('You can also embed this share as a web player into your website, with the following HTML code:') . '<br />' .
                '<i>' . htmlentities('<iframe style="width: 630px; height: 75px;" src="' . Share::get_url($share->id, $share->secret) . '&embed=true"></iframe>') . '</i><br />';

            $title = T_('No Problem');
            show_confirmation($title, $body, AmpConfig::get('web_path') . '/stats.php?action=share');
        }
        UI::show_footer();

        return false;
    case 'show_delete':
        UI::show_header();
        $share_id = Core::get_request('id');

        $next_url = AmpConfig::get('web_path') . '/share.php?action=delete&id=' . scrub_out($share_id);
        show_confirmation(T_('Are You Sure?'), T_('The Share will be deleted and no longer accessible to others'), $next_url, 1, 'delete_share');
        UI::show_footer();

        return false;
    case 'delete':
        if (AmpConfig::get('demo_mode')) {
            UI::access_denied();

            return false;
        }

        UI::show_header();
        $share_id = Core::get_request('id');
        if (Share::delete_share($share_id, Core::get_global('user'))) {
            $next_url = AmpConfig::get('web_path') . '/stats.php?action=share';
            show_confirmation(T_('No Problem'), T_('Share has been deleted'), $next_url);
        }
        UI::show_footer();

        return false;
    case 'clean':
        if (AmpConfig::get('demo_mode')) {
            UI::access_denied();

            return false;
        }

        UI::show_header();
        Share::garbage_collection();
        $next_url = AmpConfig::get('web_path') . '/stats.php?action=share';
        show_confirmation(T_('No Problem'), T_('Expired shares have been cleaned'), $next_url);
        UI::show_footer();

        return false;
    case 'external_share':
        if (AmpConfig::get('demo_mode')) {
            UI::access_denied();

            return false;
        }

        $plugin = new Plugin(Core::get_get('plugin'));
        if (!$plugin) {
            UI::access_denied('Access Denied - Unknown external share plugin');

            return false;
        }
        $plugin->load(Core::get_global('user'));

        $type           = Core::get_request('type');
        $share_id       = Core::get_request('id');
        $allow_download = (($type == 'song' && Access::check_function('download')) || Access::check_function('batch_download'));
        $secret         = generate_password(8);

        $share_id = Share::create_share($type, $share_id, true, $allow_download, AmpConfig::get('share_expire'), $secret, 0);
        $share    = new Share($share_id);
        $share->format(true);

        header("Location: " . $plugin->_plugin->external_share($share->public_url, $share->f_name));

        return false;
}

/**
 * If Access Control is turned on then we don't
 * even want them to be able to get to the login
 * page if they aren't in the ACL
 */
if (AmpConfig::get('access_control')) {
    if (!Access::check_network('interface', '', 5)) {
        debug_event('share', 'Access Denied:' . Core::get_server('REMOTE_ADDR') . ' is not in the Interface Access list', 3);
        UI::access_denied();

        return false;
    }
} // access_control is enabled

$share_id = Core::get_request('id');
$secret   = $_REQUEST['secret'];

$share = new Share($share_id);
if (empty($action) && $share->id) {
    if ($share->allow_stream) {
        $action = 'stream';
    } elseif ($share->allow_download) {
        $action = 'download';
    }
}

if (!$share->is_valid($secret, $action)) {
    UI::access_denied();

    return false;
}

$share->format();

$share->save_access();
if ($action == 'download') {
    if ($share->object_type == 'song' || $share->object_type == 'video') {
        $_REQUEST['action']                    = 'download';
        $_REQUEST['type']                      = $share->object_type;
        $_REQUEST[$share->object_type . '_id'] = $share->object_id;
        require AmpConfig::get('prefix') . '/stream.php';
    } else {
        $_REQUEST['action'] = $share->object_type;
        $_REQUEST['id']     = $share->object_id;
        $object_type        = $share->object_type;
        require AmpConfig::get('prefix') . '/batch.php';
    }
} elseif ($action == 'stream') {
    require AmpConfig::get('prefix') . UI::find_template('show_share.inc.php');
} else {
    debug_event('share', 'Access Denied: unknown action.', 3);
    UI::access_denied();

    return false;
}
