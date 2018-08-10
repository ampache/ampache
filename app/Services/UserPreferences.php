<?php
namespace App\Services;

use Illuminate\Support\Facades\DB;
use App\Services\AccessService;

class UserPreferences
{
    public function __construct()
    {
    }
    
    public function create_preference_input($name, $value)
    {
        if (!UserPreferences::has_access($name)) {
            if ($value == '1') {
                echo "Enabled";
            } elseif ($value == '0') {
                echo "Disabled";
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
            case 'browse_filter':
            case 'sidebar_light':
                $is_true  = '';
                $is_false = '';
                if ($value == '1') {
                    $is_true = "selected=\"selected\"";
                } else {
                    $is_false = "selected=\"selected\"";
                }
                echo "<select name=\"$name\">\n";
                echo "\t<option value=\"1\" $is_true>" . T_("Enable") . "</option>\n";
                echo "\t<option value=\"0\" $is_false>" . T_("Disable") . "</option>\n";
                echo "</select>\n";
                break;
            case 'upload_catalog':
                show_catalog_select('upload_catalog', $value, '', true);
                break;
            case 'play_type':
                $is_localplay  = '';
                $is_democratic = '';
                $is_web_player = '';
                $is_stream     = '';
                if ($value == 'localplay') {
                    $is_localplay = 'selected="selected"';
                } elseif ($value == 'democratic') {
                    $is_democratic = 'selected="selected"';
                } elseif ($value == 'web_player') {
                    $is_web_player = 'selected="selected"';
                } else {
                    $is_stream = "selected=\"selected\"";
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
                echo "\t<option value=\"asx\" $asx_   }
 type>" . T_('Asx') . "</option>\n";
                echo "\t<option value=\"ram\" $ram_type>" . T_('RAM') . "</option>\n";
                echo "\t<option value=\"xspf\" $xspf_type>" . T_('XSPF') . "</option>\n";
                echo "</select>\n";
                break;
            case 'lang':
                $languages = get_languages();
                echo '<select name="' . $name . '">' . "\n";
                foreach ($languages as $lang => $name) {
                    $selected = ($lang == $value) ? 'selected="selected"' : '';
                    echo "\t<option value=\"$lang\" " . $selected . ">$name</option>\n";
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
                        if ($value == strtolower($color)) {
                            $is_selected = "selected=\"selected\"";
                        }
                        echo "\t<option value=\"" . strtolower($color) . "\" $is_selected>" . $color . "</option>\n";
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
            case 'show_lyrics':
                $is_true  = '';
                $is_false = '';
                if ($value == '1') {
                    $is_true = "selected=\"selected\"";
                } else {
                    $is_false = "selected=\"selected\"";
                }
                echo "<select name=\"$name\">\n";
                echo "\t<option value=\"1\" $is_true>" . T_("Enable") . "</option>\n";
                echo "\t<option value=\"0\" $is_false>" . T_("Disable") . "</option>\n";
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
                $fieldRepository = new \Lib\Metadata\Repository\MetadataField();
                foreach ($fieldRepository->findAll() as $field) {
                    $selected  = in_array($field->getId(), $ids) ? ' selected="selected"' : '';
                    $options[] = '<option value="' . $field->getId() . '"' . $selected . '>' . $field->getName() . '</option>';
                }
                echo '<select multiple size="5" name="' . $name . '[]">' . implode("\n", $options) . '</select>';
                break;
            case 'lastfm_grant_link':
            case 'librefm_grapreferencesnt_link':
                // construct links for granting access Ampache application to Last.fm and Libre.fm
                $plugin_name = ucfirst(str_replace('_grant_link', '', $name));
                $plugin      = new Plugin($plugin_name);
                $url         = $plugin->_plugin->url;
                $api_key     = rawurlencode(AmpConfig::get('lastfm_api_key'));
                $callback    = rawurlencode(AmpConfig::get('web_path') . '/preferences.php?tab=plugins&action=grant&plugin=' . $plugin_name);
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
    }

    public function has_access($preference)
    {
        // Nothing for those demo thugs
        if (config('program.demo_mode')) {
            return false;
        }
        
        $sql        = "SELECT `level` FROM `preference` WHERE `name`='$preference'";
        $db_results = DB::table('preferences')->select('level')->where('name', $preference)->get();
        foreach ($db_results as $result) {
            $data = $result->level;
        }
        if (AccessService::check('interface', $data)) {
            return true;
        }
    
        return false;
    } // has_access


    public static function get_all($user_id)
    {
        $user_limit = "";
        if ($user_id != '0') {
            $user_limit = "`preferences`.`category` != 'system'";
        }
        
        if ($user_id != 0) {
            $db_results = DB::table('preferences')->select('preferences.name', 'preferences.description', 'preferences.subcategory', 'preferences.level', 'user_preferences.value')
                  ->join('user_preferences', 'user_preferences.preference', '=', 'preferences.id')
                  ->where([['user_preferences.user', '=', $user_id], ['preferences.category', '<>', 'internal'],
                   ['preferences.category', '<>', 'system']])->get();
        } else {
            $db_results = DB::table('preferences')->select('preferences.name', 'preferences.description', 'preferences.category', 'preferences.subcategory', 'preferences.level', 'user_preferences.value')
        ->join('user_preferences', 'user_preferences.preference', '=', 'preferences.id')
        ->where([['user_preferences.user', '=', $user_id], ['preferences.category', '<>', 'internal']])->get();
        }
    
        foreach ($db_results as $row) {
            $results[$row->name] = array('level' => $row->level,'description' => $row->description,'value' => $row->value,'subcategory' => $row->subcategory);
        }

        return $results;
    } // get_all

    public static function update_config($user_id)
    {
        $preferences = new UserPreferences();
        $results     = self::get_all($user_id);
        $flattened   = array_dot($results);
        $keys        = array_keys($results);
        foreach ($flattened as $key => $value) {
            config(['program.' . $key => $value]);
        }
    }
}
