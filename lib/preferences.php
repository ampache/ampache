<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */

use Lib\Metadata\Repository\MetadataField;

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
 * @param integer $user_id
 */

/**
 * update_preferences
 * grabs the current keys that should be added and then runs
 * through $_REQUEST looking for those values and updates them for this user
 * @param integer $user_id
 */
function update_preferences($user_id = 0)
{
    // Get current keys
    $sql = "SELECT `id`, `name`, `type` FROM `preference`";

    // If it isn't the System Account's preferences
    if ($user_id != '-1') {
        $sql .= " WHERE `catagory` != 'system'";
    }

    $db_results = Dba::read($sql);

    $results = array();
    // Collect the current possible keys
    while ($row = Dba::fetch_assoc($db_results)) {
        $results[] = array('id' => $row['id'], 'name' => $row['name'], 'type' => $row['type']);
    } // end collecting keys

    // Foreach through possible keys and assign them
    foreach ($results as $data) {
        // Get the Value from POST/GET var called $data
        $name         = (string) $data['name'];
        $apply_to_all = 'check_' . $data['name'];
        $new_level    = 'level_' . $data['name'];
        $pref_id      = $data['id'];
        $value        = scrub_in($_REQUEST[$name]);

        // Some preferences require some extra checks to be performed
        switch ($name) {
            case 'transcode_bitrate':
                $value = (string) Stream::validate_bitrate($value);
                break;
            default:
                break;
        }

        if (preg_match('/_pass$/', $name)) {
            if ($value == '******') {
                unset($_REQUEST[$name]);
            } else {
                if (preg_match('/md5_pass$/', $name)) {
                    $value = md5((string) $value);
                }
            }
        }

        // Run the update for this preference only if it's set
        if (isset($_REQUEST[$name])) {
            Preference::update($pref_id, $user_id, $value, $_REQUEST[$apply_to_all]);
        }

        if (Access::check('interface', 100) && $_REQUEST[$new_level]) {
            Preference::update_level($pref_id, $_REQUEST[$new_level]);
        }
    } // end foreach preferences

    // Now that we've done that we need to invalidate the cached preverences
    Preference::clear_from_session();
} // update_preferences

/**
 * update_preference
 * This function updates a single preference and is called by the update_preferences function
 * @param integer $user_id
 * @param string $name
 * @param integer $pref_id
 * @param string $value
 * @return boolean
 */
function update_preference($user_id, $name, $pref_id, $value)
{
    $apply_check = "check_" . $name;
    $level_check = "level_" . $name;

    // First see if they are an administrator and we are applying this to everything
    if (Core::get_global('user')->has_access(100) && make_bool($_REQUEST[$apply_check])) {
        Preference::update_all($pref_id, $value);

        return true;
    }

    // Check and see if they are an admin and the level def is set
    if (Core::get_global('user')->has_access(100) && make_bool($_REQUEST[$level_check])) {
        Preference::update_level($pref_id, $_REQUEST[$level_check]);
    }

    // Else make sure that the current users has the right to do this
    if (Preference::has_access($name)) {
        $sql = "UPDATE `user_preference` SET `value` = ? WHERE `preference` = ? AND `user` = ?";
        Dba::write($sql, array($value, $pref_id, $user_id));

        return true;
    }

    return false;
} // update_preference

/**
 * create_preference_input
 * takes the key and then creates the correct type of input for updating it
 * @param string $name
 * @param $value
 */
function create_preference_input($name, $value)
{
    if (!Preference::has_access($name)) {
        if ($value == '1') {
            echo T_("Enabled");
        } elseif ($value == '0') {
            echo T_("Disabled");
        } else {
            if (preg_match('/_pass$/', $name) || preg_match('/_api_key$/', $name)) {
                echo "******";
            } else {
                echo $value;
            }
        }

        return;
    } // if we don't have access to it

    switch ($name) {
        case 'display_menu':
        case 'download':
        case 'quarantine':
        case 'upload':
        case 'access_list':
        case 'lock_songs':
        case 'xml_rpc':
        case 'force_http_play':
        case 'no_symlinks':
        case 'use_auth':
        case 'access_control':
        case 'allow_stream_playback':
        case 'allow_democratic_playback':
        case 'allow_localplay_playback':
        case 'demo_mode':
        case 'condPL':
        case 'rio_track_stats':
        case 'rio_global_stats':
        case 'direct_link':
        case 'ajax_load':
        case 'now_playing_per_user':
        case 'show_played_times':
        case 'show_skipped_times':
        case 'song_page_title':
        case 'subsonic_backend':
        case 'plex_backend':
        case 'webplayer_flash':
        case 'webplayer_html5':
        case 'allow_personal_info_now':
        case 'allow_personal_info_recent':
        case 'allow_personal_info_time':
        case 'allow_personal_info_agent':
        case 'ui_fixed':
        case 'autoupdate':
        case 'webplayer_confirmclose':
        case 'webplayer_pausetabs':
        case 'stream_beautiful_url':
        case 'share':
        case 'share_social':
        case 'broadcast_by_default':
        case 'album_group':
        case 'topmenu':
        case 'demo_clear_sessions':
        case 'show_donate':
        case 'allow_upload':
        case 'upload_subdir':
        case 'upload_user_artist':
        case 'upload_allow_edit':
        case 'daap_backend':
        case 'upnp_backend':
        case 'album_release_type':
        case 'home_moment_albums':
        case 'home_moment_videos':
        case 'home_recently_played':
        case 'home_now_playing':
        case 'browser_notify':
        case 'allow_video':
        case 'geolocation':
        case 'webplayer_aurora':
        case 'upload_allow_remove':
        case 'webdav_backend':
        case 'notify_email':
        case 'libitem_contextmenu':
        case 'upload_catalog_pattern':
        case 'catalogfav_gridview':
        case 'personalfav_display':
        case 'catalog_check_duplicate':
        case 'browse_filter':
        case 'sidebar_light':
        case 'cron_cache':
        case 'show_lyrics':
        case 'unique_playlist':
        case 'ratingmatch_flags':
            $is_true  = '';
            $is_false = '';
            if ($value == '1') {
                $is_true = "selected=\"selected\"";
            } else {
                $is_false = "selected=\"selected\"";
            }
            echo "<select name=\"$name\">\n";
            echo "\t<option value=\"1\" $is_true>" . T_("On") . "</option>\n";
            echo "\t<option value=\"0\" $is_false>" . T_("Off") . "</option>\n";
            echo "</select>\n";
            break;
        case 'upload_catalog':
            show_catalog_select('upload_catalog', $value, '', true);
            break;
        case 'play_type':
            $is_stream     = '';
            $is_localplay  = '';
            $is_democratic = '';
            $is_web_player = '';
            switch ($value) {
                case 'localplay':
                    $is_localplay = 'selected="selected"';
                    break;
                case 'democratic':
                    $is_democratic = 'selected="selected"';
                    break;
                case 'web_player':
                    $is_web_player = 'selected="selected"';
                    break;
                default:
                    $is_stream = 'selected="selected"';
            }
            echo "<select name=\"$name\">\n";
            echo "\t<option value=\"\">" . T_('None') . "</option>\n";
            if (AmpConfig::get('allow_stream_playback')) {
                echo "\t<option value=\"stream\" $is_stream>" . T_('Stream') . "</option>\n";
            }
            if (AmpConfig::get('allow_democratic_playback')) {
                echo "\t<option value=\"democratic\" $is_democratic>" . T_('Democratic') . "</option>\n";
            }
            if (AmpConfig::get('allow_localplay_playback')) {
                echo "\t<option value=\"localplay\" $is_localplay>" . T_('Localplay') . "</option>\n";
            }
            echo "\t<option value=\"web_player\" $is_web_player>" . T_('Web Player') . "</option>\n";
            echo "</select>\n";
            break;
        case 'playlist_type':
            $var_name    = $value . "_type";
            ${$var_name} = "selected=\"selected\"";
            echo "<select name=\"$name\">\n";
            echo "\t<option value=\"m3u\" $m3u_type>" . T_('M3U') . "</option>\n";
            echo "\t<option value=\"simple_m3u\" $simple_m3u_type>" . T_('Simple M3U') . "</option>\n";
            echo "\t<option value=\"pls\" $pls_type>" . T_('PLS') . "</option>\n";
            echo "\t<option value=\"asx\" $asx_type>" . T_('Asx') . "</option>\n";
            echo "\t<option value=\"ram\" $ram_type>" . T_('RAM') . "</option>\n";
            echo "\t<option value=\"xspf\" $xspf_type>" . T_('XSPF') . "</option>\n";
            echo "</select>\n";
            break;
        case 'lang':
            $languages = get_languages();
            echo '<select name="' . $name . '">' . "\n";
            foreach ($languages as $lang => $tongue) {
                $selected = ($lang == $value) ? 'selected="selected"' : '';
                echo "\t<option value=\"$lang\" " . $selected . ">$tongue</option>\n";
            } // end foreach
            echo "</select>\n";
            break;
        case 'localplay_controller':
            $controllers = Localplay::get_controllers();
            echo "<select name=\"$name\">\n";
            echo "\t<option value=\"\">" . T_('None') . "</option>\n";
            foreach ($controllers as $controller) {
                if (!Localplay::is_enabled($controller)) {
                    continue;
                }
                $is_selected = '';
                if ($value == $controller) {
                    $is_selected = 'selected="selected"';
                }
                echo "\t<option value=\"" . $controller . "\" $is_selected>" . ucfirst($controller) . "</option>\n";
            } // end foreach
            echo "</select>\n";
            break;
        case 'ratingmatch_stars':
            $is_0 = '';
            $is_1 = '';
            $is_2 = '';
            $is_3 = '';
            $is_4 = '';
            $is_5 = '';
            if ($value == 0) {
                $is_0 = 'selected="selected"';
            } elseif ($value == 1) {
                $is_1 = 'selected="selected"';
            } elseif ($value == 2) {
                $is_2 = 'selected="selected"';
            } elseif ($value == 3) {
                $is_3 = 'selected="selected"';
            } elseif ($value == 4) {
                $is_4 = 'selected="selected"';
            } elseif ($value == 4) {
                $is_5 = 'selected="selected"';
            }
            echo "<select name=\"$name\">\n";
            echo "<option value=\"0\" $is_0>" . T_('Disabled') . "</option>\n";
            echo "<option value=\"1\" $is_1>" . T_('1 Star') . "</option>\n";
            echo "<option value=\"2\" $is_2>" . T_('2 Stars') . "</option>\n";
            echo "<option value=\"3\" $is_3>" . T_('3 Stars') . "</option>\n";
            echo "<option value=\"4\" $is_4>" . T_('4 Stars') . "</option>\n";
            echo "<option value=\"5\" $is_5>" . T_('5 Stars') . "</option>\n";
            echo "</select>\n";
            break;
        case 'localplay_level':
            $is_user    = '';
            $is_admin   = '';
            $is_manager = '';
            if ($value == '25') {
                $is_user = 'selected="selected"';
            } elseif ($value == '100') {
                $is_admin = 'selected="selected"';
            } elseif ($value == '50') {
                $is_manager = 'selected="selected"';
            }
            echo "<select name=\"$name\">\n";
            echo "<option value=\"0\">" . T_('Disabled') . "</option>\n";
            echo "<option value=\"25\" $is_user>" . T_('User') . "</option>\n";
            echo "<option value=\"50\" $is_manager>" . T_('Manager') . "</option>\n";
            echo "<option value=\"100\" $is_admin>" . T_('Admin') . "</option>\n";
            echo "</select>\n";
            break;
        case 'theme_name':
            $themes = get_themes();
            echo "<select name=\"$name\">\n";
            foreach ($themes as $theme) {
                $is_selected = "";
                if ($value == $theme['path']) {
                    $is_selected = "selected=\"selected\"";
                }
                echo "\t<option value=\"" . $theme['path'] . "\" $is_selected>" . $theme['name'] . "</option>\n";
            } // foreach themes
            echo "</select>\n";
            break;
        case 'theme_color':
            // This include a two-step configuration (first change theme and save, then change theme color and save)
            $theme_cfg = get_theme(AmpConfig::get('theme_name'));
            if ($theme_cfg !== null) {
                echo "<select name=\"$name\">\n";
                foreach ($theme_cfg['colors'] as $color) {
                    $is_selected = "";
                    if ($value == strtolower((string) $color)) {
                        $is_selected = "selected=\"selected\"";
                    }
                    echo "\t<option value=\"" . strtolower((string) $color) . "\" $is_selected>" . $color . "</option>\n";
                } // foreach themes
                echo "</select>\n";
            }
            break;
        case 'playlist_method':
            ${$value} = ' selected="selected"';
            echo "<select name=\"$name\">\n";
            echo "\t<option value=\"send\"$send>" . T_('Send on Add') . "</option>\n";
            echo "\t<option value=\"send_clear\"$send_clear>" . T_('Send and Clear on Add') . "</option>\n";
            echo "\t<option value=\"clear\"$clear>" . T_('Clear on Send') . "</option>\n";
            echo "\t<option value=\"default\"$default>" . T_('Default') . "</option>\n";
            echo "</select>\n";
            break;
        case 'transcode':
            ${$value} = ' selected="selected"';
            echo "<select name=\"$name\">\n";
            echo "\t<option value=\"never\"$never>" . T_('Never') . "</option>\n";
            echo "\t<option value=\"default\"$default>" . T_('Default') . "</option>\n";
            echo "\t<option value=\"always\"$always>" . T_('Always') . "</option>\n";
            echo "</select>\n";
            break;
        case 'album_sort':
            $is_sort_year_asc  = '';
            $is_sort_year_desc = '';
            $is_sort_name_asc  = '';
            $is_sort_name_desc = '';
            $is_sort_default   = '';
            if ($value == 'year_asc') {
                $is_sort_year_asc = 'selected="selected"';
            } elseif ($value == 'year_desc') {
                $is_sort_year_desc = 'selected="selected"';
            } elseif ($value == 'name_asc') {
                $is_sort_name_asc = 'selected="selected"';
            } elseif ($value == 'name_desc') {
                $is_sort_name_desc = 'selected="selected"';
            } else {
                $is_sort_default = 'selected="selected"';
            }

            echo "<select name=\"$name\">\n";
            echo "\t<option value=\"default\" $is_sort_default>" . T_('Default') . "</option>\n";
            echo "\t<option value=\"year_asc\" $is_sort_year_asc>" . T_('Year ascending') . "</option>\n";
            echo "\t<option value=\"year_desc\" $is_sort_year_desc>" . T_('Year descending') . "</option>\n";
            echo "\t<option value=\"name_asc\" $is_sort_name_asc>" . T_('Name ascending') . "</option>\n";
            echo "\t<option value=\"name_desc\" $is_sort_name_desc>" . T_('Name descending') . "</option>\n";
            echo "</select>\n";
            break;
        case 'disabled_custom_metadata_fields':
            $ids             = explode(',', $value);
            $options         = array();
            $fieldRepository = new MetadataField();
            foreach ($fieldRepository->findAll() as $field) {
                $selected  = in_array($field->getId(), $ids) ? ' selected="selected"' : '';
                $options[] = '<option value="' . $field->getId() . '"' . $selected . '>' . $field->getName() . '</option>';
            }
            echo '<select multiple size="5" name="' . $name . '[]">' . implode("\n", $options) . '</select>';
            break;
        case 'personalfav_playlist':
        case 'personalfav_smartlist':
            $ids       = explode(',', $value);
            $options   = array();
            $playlists = ($name == 'personalfav_smartlist') ? Playlist::get_details('search') : Playlist::get_details();
            if (!empty($playlists)) {
                foreach ($playlists as $list_id => $list_name) {
                    $selected  = in_array($list_id, $ids) ? ' selected="selected"' : '';
                    $options[] = '<option value="' . $list_id . '"' . $selected . '>' . $list_name . '</option>';
                }
                echo '<select multiple size="5" name="' . $name . '[]">' . implode("\n", $options) . '</select>';
            }
            break;
        case 'lastfm_grant_link':
        case 'librefm_grant_link':
            // construct links for granting access Ampache application to Last.fm and Libre.fm
            $plugin_name = ucfirst(str_replace('_grant_link', '', $name));
            $plugin      = new Plugin($plugin_name);
            $url         = $plugin->_plugin->url;
            $api_key     = rawurlencode(AmpConfig::get('lastfm_api_key'));
            $callback    = rawurlencode(AmpConfig::get('web_path') . '/preferences.php?tab=plugins&action=grant&plugin=' . $plugin_name);
            /* HINT: Plugin Name */
            echo "<a href='$url/api/auth/?api_key=$api_key&cb=$callback'>" . UI::get_icon('plugin', sprintf(T_("Click to grant %s access to Ampache"), $plugin_name)) . '</a>';
            break;
        default:
            if (preg_match('/_pass$/', $name)) {
                echo '<input type="password" name="' . $name . '" value="******" />';
            } else {
                echo '<input type="text" name="' . $name . '" value="' . $value . '" />';
            }
            break;
    }
} // create_preference_input
