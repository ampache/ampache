<?php
namespace App\Services;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Xinax\LaravelGettext\Facades\LaravelGettext;
use App\Facades\AccessService;
use App\Facades\AmpConfig;

class Preferences
{
    public function __construct()
    {
    }
    
    public function create_preference_input($name, $value, $id)
    {
        if (!$this->has_access($name)) {
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
            case 'direc__link':
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
            case 'broadcas__by_default':
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
            case 'home_momen__albums':
            case 'home_momen__videos':
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
            case 'catalog_check_duplicate':
            case 'catalogfav_gridview':
            case 'browse_filter':
            case 'show_lyrics':
            case 'sidebar_light':
                if ($value == '1') {
                    $checked = "checked=''";
                } else {
                    $checked = '';
                }
                echo '<label class="switch">' .
                '<input type="checkbox" ' . 'name="' . $name . '"' . $checked . '">' .
                '<span class="slider round"></span>' .
                '</label>';
                break;
            case 'upload_catalog':
                $this->show_catalog_select('upload_catalog', $value, '', true);
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
                echo "\t<option value=\"\">" . __('None') . "</option>\n";
                if (AmpConfig::get('allow_stream_playback')) {
                    echo "\t<option value=\"stream\" $is_stream>" . __('Stream') . "</option>\n";
                }
                if (AmpConfig::get('allow_democratic_playback')) {
                    echo "\t<option value=\"democratic\" $is_democratic>" . __('Democratic') . "</option>\n";
                }
                if (AmpConfig::get('allow_localplay_playback')) {
                    echo "\t<option value=\"localplay\" $is_localplay>" . __('Localplay') . "</option>\n";
                }
                echo "\t<option value=\"web_player\" $is_web_player>" . __('Web Player') . "</option>\n";
                echo "</select>\n";
                break;
            case 'playlist_type':
                $var_name    = $value . "_type";
                ${$var_name} = "selected=\"selected\"";
                echo "<select class=\"w3-small\" name=\"$name\">\n";
                echo "\t<option value=\"m3u\">" . __('M3U') . "</option>\n";
                echo "\t<option value=\"simple_m3u\">" . __('Simple M3U') . "</option>\n";
                echo "\t<option value=\"pls\">" . __('PLS') . "</option>\n";
                echo "\t<option value=\"asx\">" . __('Asx') . "</option>\n";
                echo "\t<option value=\"ram\">" . __('RAM') . "</option>\n";
                echo "\t<option value=\"xspf\">" . __('XSPF') . "</option>\n";
                echo "</select>\n";
                break;
            case 'lang':
                $locales   = config('laravel-gettext.supported-locales');
                $languages = config('languages');
                
                echo  '<select class="w3-small" name="' . $name . '">' . "\n";
                foreach ($locales as $lang => $name) {
                    $selected = ($languages[$name] == $value) ? 'selected="selected"' : '';
                    echo "\t<option value=\"$languages[$name]\" " . $selected . ">$languages[$name]</option>\n";
                } // end foreach
               echo "</select>\n";
               
                break;
            case 'localplay_controller':
                $controllers = LocalplayService::get_controllers();
                echo "<select name=\"$name\">\n";
                echo "\t<option value=\"\">" . __('None') . "</option>\n";
                foreach ($controllers as $controller) {
                    if (!LocalplayService::is_enabled($controller)) {
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
                echo "<option value=\"0\">" . __('Disabled') . "</option>\n";
                echo "<option value=\"25\" $is_user>" . __('User') . "</option>\n";
                echo "<option value=\"50\" $is_manager>" . __('Manager') . "</option>\n";
                echo "<option value=\"100\" $is_admin>" . __('Admin') . "</option>\n";
                echo "</select>\n";
                break;
 
            case 'playlist_method':
                ${$value} = ' selected="selected"';
                echo "<select name=\"$name\">\n";
                echo "\t<option value=\"send\">" . __('Send on Add') . "</option>\n";
                echo "\t<option value=\"send_clear\">" . __('Send and Clear on Add') . "</option>\n";
                echo "\t<option value=\"clear\">" . __('Clear on Send') . "</option>\n";
                echo "\t<option value=\"default\">" . __('Default') . "</option>\n";
                echo "</select>\n";
                break;
            case 'transcode':
                ${$value} = ' selected="selected"';
                echo "<select name=\"$name\">\n";
                echo "\t<option value=\"never\">" . __('Never') . "</option>\n";
                echo "\t<option value=\"default\">" . __('Default') . "</option>\n";
                echo "\t<option value=\"always\">" . __('Always') . "</option>\n";
                echo "</select>\n";
                break;
           case 'album_sort':
                $is_sor__year_asc  = '';
                $is_sor__year_desc = '';
                $is_sor__name_asc  = '';
                $is_sor__name_desc = '';
                $is_sor__default   = '';
                if ($value == 'year_asc') {
                    $is_sor__year_asc = 'selected="selected"';
                } elseif ($value == 'year_desc') {
                    $is_sor__year_desc = 'selected="selected"';
                } elseif ($value == 'name_asc') {
                    $is_sor__name_asc = 'selected="selected"';
                } elseif ($value == 'name_desc') {
                    $is_sor__name_desc = 'selected="selected"';
                } else {
                    $is_sor__default = 'selected="selected"';
                }
                
                echo "<select name=\"$name\">\n";
                echo "\t<option value=\"default\" $is_sor__default>" . __('Default') . "</option>\n";
                echo "\t<option value=\"year_asc\" $is_sor__year_asc>" . __('Year ascending') . "</option>\n";
                echo "\t<option value=\"year_desc\" $is_sor__year_desc>" . __('Year descending') . "</option>\n";
                echo "\t<option value=\"name_asc\" $is_sor__name_asc>" . __('Name ascending') . "</option>\n";
                echo "\t<option value=\"name_desc\" $is_sor__name_desc>" . __('Name descending') . "</option>\n";
                echo "</select>\n";
                break;
 /*
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
 */
            case 'lastfm_grant_link':
            case 'librefm_grapreferencesnt_link':
                // construct links for granting access Ampache application to Last.fm and Libre.fm
                $plugin_name = ucfirst(str_replace('_grant_link', '', $name));
 //               $plugin      = new Plugin($plugin_name);
                $url         = $plugin->_plugin->url;
                $api_key     = rawurlencode(AmpConfig::get('lastfm_api_key'));
                $callback    = rawurlencode(AmpConfig::get('web_path') . '/preferences.php?tab=plugins&action=grant&plugin=' . $plugin_name);
   //             echo "<a href='$url/api/auth/?api_key=$api_key&cb=$callback'>" . UI::ge__icon('plugin', sprintf(__("Click to grant %s access to Ampache"), $plugin_name)) . '</a>';
                break;
            default:
                if (preg_match('/_pass$/', $name)) {
                    echo '<input type="password" name="' . $name . '" value="******" />';
                } else {
                    echo '<input id="name_' . $id . '" class="w3-small"' . 'type="text" name="' . $name . '" value="' . $value . '" />';
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


    public function get_all($user_id)
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
    } // ge__all

    public static function update_config($user_id)
    {
        $preferences = new UserPreferences();
        $results     = self::get_all($user_id);
        $flattened   = array_dot($results);
        $keys        = array_keys($results);
        foreach ($flattened as $key => $value) {
            Config::get(['program.' . $key => $value]);
        }
    }
    public static function get_languages()
    {
        $results = LaravelGettext::getSelector()->render();
        
        return $results;
    } // get_languages
    
    public function show_catalog_select($name='catalog', $catalog_id=0, $style='', $allow_none=false, $filter_type='')
    {
        echo "<select name=\"$name\" style=\"$style\">\n";
        echo "\t<option value=\"none\"" . ">" . 'none' . "</option>\n";
        echo "</select>\n";
    } // show_catalog_select

    /**
     * delete
     * This deletes the specified preference, a name or a ID can be passed
     */
    public static function delete($preference)
    {
        // First prepare
        if (!is_numeric($preference)) {
            DB::table('preferences')->where('name', '=', $preference)->delete();
        } else {
            DB::table('preferences')->where('id', '=', $preference)->delete();
        }
        
        Dba::write($sql, array($preference));
        
        self::clean_preferences();
    } // delete
    
    /**
     * clean_preferences
     * This removes any garbage
     */
    public static function clean_preferences()
    {
        // First remove garbage
        DB::table('user_preferences')->leftJoin('preferences', 'preferences.id', '=', 'user_preferences.preference')->WhereNull('preferences.id')->delete();
    } // rebuild_preferences
   
    /**
     * get_by_user
     * Return a preference for specific user identifier
     */
    public static function get_by_user($user_id, $pref_name)
    {
        //debug_event('preference.class.php', 'Getting preference {'.$pref_name.'} for user identifier {'.$user_id.'}...', '5');
        $user_id   = Dba::escape($user_id);
        $pref_name = Dba::escape($pref_name);
        $id        = self::id_from_name($pref_name);
        
        if (parent::is_cached('get_by_user', $user_id)) {
            return parent::get_from_cache('get_by_user', $user_id);
        }
        
        $sql        = "SELECT `value` FROM `user_preference` WHERE `preference`='$id' AND `user`='$user_id'";
        $db_results = Dba::read($sql);
        if (Dba::num_rows($db_results) < 1) {
            $sql        = "SELECT `value` FROM `user_preference` WHERE `preference`='$id' AND `user`='-1'";
            $db_results = Dba::read($sql);
        }
        $data = Dba::fetch_assoc($db_results);
        
        parent::add_to_cache('get_by_user', $user_id, $data['value']);
        
        return $data['value'];
    } // get_by_user
    
    /**
     * update
     * This updates a single preference from the given name or id
     */
    public static function update($preference, $user_id, $value, $applytoall=false, $applytodefault=false)
    {
        // First prepare
        if (!is_numeric($preference)) {
            $id   = self::id_from_name($preference);
            $name = $preference;
        } else {
            $name = self::name_from_id($preference);
            $id   = $preference;
        }
        if ($applytoall and Access::check('interface', '100')) {
            $user_check = "";
        } else {
            $user_check = " AND `user`='$user_id'";
        }
        
        if (is_array($value)) {
            $value = implode(',', $value);
        }
        
        if ($applytodefault and Access::check('interface', '100')) {
            $sql = "UPDATE `preference` SET `value`='$value' WHERE `id`='$id'";
            Dba::write($sql);
        }
        
        $value = Dba::escape($value);
        
        if (self::has_access($name)) {
            $user_id = Dba::escape($user_id);
            $sql     = "UPDATE `user_preference` SET `value`='$value' WHERE `preference`='$id'$user_check";
            Dba::write($sql);
            Preference::clear_from_session();
            
            parent::remove_from_cache('get_by_user', $user_id);
            
            return true;
        } else {
            debug_event('denied', $GLOBALS['user'] ? $GLOBALS['user']->username : '???' . ' attempted to update ' . $name . ' but does not have sufficient permissions', 3);
        }
        
        return false;
    } // update
    
    /**
     * update_level
     * This takes a preference ID and updates the level required to update it (performed by an admin)
     */
    public static function update_level($preference, $level)
    {
        // First prepare
        if (!is_numeric($preference)) {
            $preference_id = self::id_from_name($preference);
        } else {
            $preference_id = $preference;
        }
        
        $preference_id = Dba::escape($preference_id);
        $level         = Dba::escape($level);
        
        $sql = "UPDATE `preference` SET `level`='$level' WHERE `id`='$preference_id'";
        Dba::write($sql);
        
        return true;
    } // update_level
    
    /**
     * update_all
     * This takes a preference id and a value and updates all users with the new info
     */
    public static function update_all($preference_id, $value)
    {
        $preference_id = Dba::escape($preference_id);
        $value         = Dba::escape($value);
        
        $sql = "UPDATE `user_preference` SET `value`='$value' WHERE `preference`='$preference_id'";
        Dba::write($sql);
        
        parent::clear_cache();
        
        return true;
    } // update_all
    
    /**
     * exists
     * This just checks to see if a preference currently exists
     */
    public static function exists($preference)
    {
        // We assume it's the name
        $name       = Dba::escape($preference);
        $sql        = "SELECT * FROM `preference` WHERE `name`='$name'";
        $db_results = Dba::read($sql);
        
        return Dba::num_rows($db_results);
    } // exists
    
    /**
     * has_access
     * This checks to see if the current user has access to modify this preference
     * as defined by the preference name
     */
    public static function has_access($preference)
    {
        // Nothing for those demo thugs
        if (AmpConfig::get('demo_mode')) {
            return false;
        }
        
        $preference = Dba::escape($preference);
        
        $sql        = "SELECT `level` FROM `preference` WHERE `name`='$preference'";
        $db_results = Dba::read($sql);
        $data       = Dba::fetch_assoc($db_results);
        
        if (Access::check('interface', $data['level'])) {
            return true;
        }
        
        return false;
    } // has_access
    
    /**
     * id_from_name
     * This takes a name and returns the id
     */
    public static function id_from_name($name)
    {
        $name = Dba::escape($name);
        
        if (parent::is_cached('id_from_name', $name)) {
            return parent::get_from_cache('id_from_name', $name);
        }
        
        $sql        = "SELECT `id` FROM `preference` WHERE `name`='$name'";
        $db_results = Dba::read($sql);
        $row        = Dba::fetch_assoc($db_results);
        
        parent::add_to_cache('id_from_name', $name, $row['id']);
        
        return $row['id'];
    } // id_from_name
    
    /**
     * name_from_id
     * This returns the name from an id, it's the exact opposite
     * of the function above it, amazing!
     */
    public static function name_from_id($id)
    {
        $id = Dba::escape($id);
        
        $sql        = "SELECT `name` FROM `preference` WHERE `id`='$id'";
        $db_results = Dba::read($sql);
        
        $row = Dba::fetch_assoc($db_results);
        
        return $row['name'];
    } // name_from_id
    
    /**
     * get_catagories
     * This returns an array of the names of the different possible sections
     * it ignores the 'internal' catagory
     */
    public static function get_catagories()
    {
        $sql        = "SELECT `preference`.`catagory` FROM `preference` GROUP BY `catagory` ORDER BY `catagory`";
        $db_results = Dba::read($sql);
        
        $results = array();
        
        while ($row = Dba::fetch_assoc($db_results)) {
            if ($row['catagory'] != 'internal') {
                $results[] = $row['catagory'];
            }
        } // end while
        
        return $results;
    } // get_catagories
    
    /**
     * get_all
     * This returns a nice flat array of all of the possible preferences for the specified user
     */
    public static function get_all($user_id)
    {
        $user_id = Dba::escape($user_id);
        
        $user_limit = "";
        if ($user_id != '-1') {
            $user_limit = "AND `preference`.`catagory` != 'system'";
        }
        
        $sql = "SELECT `preference`.`name`,`preference`.`description`,`preference`.`subcatagory`,`user_preference`.`value` FROM `preference` " .
            " INNER JOIN `user_preference` ON `user_preference`.`preference`=`preference`.`id` " .
            " WHERE `user_preference`.`user`='$user_id' AND `preference`.`catagory` != 'internal' $user_limit " .
            " ORDER BY `preference`.`subcatagory`,`preference`.`description`";
        
        $db_results = Dba::read($sql);
        $results    = array();
        
        while ($row = Dba::fetch_assoc($db_results)) {
            $results[] = array('name' => $row['name'],'level' => $row['level'],'description' => $row['description'],'value' => $row['value'],'subcategory' => $row['subcatagory']);
        }
        
        return $results;
    } // get_all
    
    /**
     * insert
     * This inserts a new preference into the preference table
     * it does NOT sync up the users, that should be done independently
     */
    public static function insert($name, $description, $default, $level, $type, $catagory, $subcatagory=null)
    {
        if ($subcatagory !== null) {
            $subcatagory = strtolower($subcatagory);
        }
        $sql = "INSERT INTO `preference` (`name`,`description`,`value`,`level`,`type`,`catagory`,`subcatagory`) " .
            "VALUES (?, ?, ?, ?, ?, ?, ?)";
        $db_results = Dba::write($sql, array($name, $description, $default, intval($level), $type, $catagory, $subcatagory));
        
        if (!$db_results) {
            return false;
        }
        $id         = Dba::insert_id();
        $params     = array($id, $default);
        $sql        = "INSERT INTO `user_preference` VALUES (-1,?,?)";
        $db_results = Dba::write($sql, $params);
        if (!$db_results) {
            return false;
        }
        if ($catagory !== "system") {
            $sql        = "INSERT INTO `user_preference` SELECT `user`.`id`, ?, ? FROM `user`";
            $db_results = Dba::write($sql, $params);
            if (!$db_results) {
                return false;
            }
        }
        
        return true;
    } // insert
    
    /**
     * rename
     * This renames a preference in the database
     */
    public static function rename($old, $new)
    {
        $sql = "UPDATE `preference` SET `name` = ? WHERE `name` = ?";
        Dba::write($sql, array($new, $old));
    }
    
    /**
     * fix_preferences
     * This takes the preferences, explodes what needs to
     * become an array and boolean everythings
     */
    public static function fix_preferences($results)
    {
        $arrays = array(
            'auth_methods', 'getid3_tag_order', 'metadata_order',
            'metadata_order_video', 'art_order', 'registration_display_fields',
            'registration_mandatory_fields'
        );
        
        foreach ($arrays as $item) {
            $results[$item] = trim($results[$item])
            ? explode(',', $results[$item])
            : array();
        }
        
        foreach ($results as $key => $data) {
            if (!is_array($data)) {
                if (strcasecmp($data, "true") == "0") {
                    $results[$key] = 1;
                }
                if (strcasecmp($data, "false") == "0") {
                    $results[$key] = 0;
                }
            }
        }
        
        return $results;
    } // fix_preferences
}
