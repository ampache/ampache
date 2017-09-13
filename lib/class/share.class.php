<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPLv3)
 * Copyright 2001 - 2017 Ampache.org
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

class Share extends database_object
{
    public $id;
    public $user;
    public $object_type;
    public $object_id;
    public $allow_stream;
    public $allow_download;
    public $creation_date;
    public $lastvisit_date;
    public $expire_days;
    public $max_counter;
    public $counter;
    public $secret;
    public $public_url;
    public $description;

    public $f_name;
    public $f_object_link;
    public $f_user;
    public $f_allow_stream;
    public $f_allow_download;
    public $f_creation_date;
    public $f_lastvisit_date;

    /**
     * Constructor
     */
    public function __construct($id=0)
    {
        if (!$id) {
            return true;
        }

        /* Get the information from the db */
        $info = $this->get_info($id);

        // Foreach what we've got
        foreach ($info as $key => $value) {
            $this->$key = $value;
        }

        return true;
    } //constructor

    public static function delete_share($id)
    {
        $sql    = "DELETE FROM `share` WHERE `id` = ?";
        $params = array( $id );
        if (!$GLOBALS['user']->has_access('75')) {
            $sql .= " AND `user` = ?";
            $params[] = $GLOBALS['user']->id;
        }

        return Dba::write($sql, $params);
    }

    public static function gc()
    {
        $sql = "DELETE FROM `share` WHERE (`expire_days` > 0 AND (`creation_date` + (`expire_days` * 86400)) < " . time() . ") OR (`max_counter` > 0 AND `counter` >= `max_counter`)";
        Dba::write($sql);
    }

    public static function delete_shares($object_type, $object_id)
    {
        $sql = "DELETE FROM `share` WHERE `object_type` = ? AND `object_id` = ?";

        Dba::write($sql, array($object_type, $object_id));
    }

    public static function generate_secret($length = 8)
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $secret     = '';
        for ($i = 0; $i < $length; $i++) {
            $secret .= $characters[rand(0, strlen($characters) - 1)];
        }

        return $secret;
    }

    public static function format_type($type)
    {
        switch ($type) {
            case 'album':
            case 'song':
            case 'playlist':
            case 'video':
                return $type;
            default:
                return '';
        }
    }

    public static function create_share($object_type, $object_id, $allow_stream=true, $allow_download=true, $expire=0, $secret='', $max_counter=0, $description='')
    {
        $object_type = self::format_type($object_type);
        if (empty($object_type)) {
            return '';
        }

        if (!$allow_stream && !$allow_download) {
            return '';
        }

        $sql    = "INSERT INTO `share` (`user`, `object_type`, `object_id`, `creation_date`, `allow_stream`, `allow_download`, `expire_days`, `secret`, `counter`, `max_counter`, `description`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $params = array($GLOBALS['user']->id, $object_type, $object_id, time(), $allow_stream ?: 0, $allow_download ?: 0, $expire, $secret, 0, $max_counter, $description);
        Dba::write($sql, $params);

        $id = Dba::insert_id();

        $url = self::get_url($id, $secret);
        // Get a shortener url if any available
        foreach (Plugin::get_plugins('shortener') as $plugin_name) {
            try {
                $plugin = new Plugin($plugin_name);
                if ($plugin->load($GLOBALS['user'])) {
                    $short_url = $plugin->_plugin->shortener($url);
                    if (!empty($short_url)) {
                        $url = $short_url;
                        break;
                    }
                }
            } catch (Exception $e) {
                debug_event('share', 'Share plugin error: ' . $e->getMessage(), '1');
            }
        }
        $sql = "UPDATE `share` SET `public_url` = ? WHERE `id` = ?";
        Dba::write($sql, array($url, $id));

        return $id;
    }

    public static function get_url($id, $secret)
    {
        $url = AmpConfig::get('web_path') . '/share.php?id=' . $id;
        if (!empty($secret)) {
            $url .= '&secret=' . $secret;
        }

        return $url;
    }

    public static function get_share_list_sql()
    {
        $sql = "SELECT `id` FROM `share` ";

        if (!$GLOBALS['user']->has_access('75')) {
            $sql .= "WHERE `user` = '" . scrub_in($GLOBALS['user']->id) . "'";
        }

        return $sql;
    }

    public static function get_share_list()
    {
        $sql        = self::get_share_list_sql();
        $db_results = Dba::read($sql);
        $results    = array();

        while ($row = Dba::fetch_assoc($db_results)) {
            $results[] = $row['id'];
        }

        return $results;
    }

    public static function get_shares($object_type, $object_id)
    {
        $sql        = "SELECT `id` FROM `share` WHERE `object_type` = ? AND `object_id` = ?";
        $db_results = Dba::read($sql, array($object_type, $object_id));
        $results    = array();
        while ($row = Dba::fetch_assoc($db_results)) {
            $results[] = $row['id'];
        }

        return $results;
    }

    public function show_action_buttons()
    {
        if ($this->id) {
            if ($GLOBALS['user']->has_access('75') || $this->user == $GLOBALS['user']->id) {
                echo "<a id=\"edit_share_ " . $this->id . "\" onclick=\"showEditDialog('share_row', '" . $this->id . "', 'edit_share_" . $this->id . "', '" . T_('Share edit') . "', 'share_')\">" . UI::get_icon('edit', T_('Edit')) . "</a>";
                echo "<a href=\"" . AmpConfig::get('web_path') . "/share.php?action=show_delete&id=" . $this->id . "\">" . UI::get_icon('delete', T_('Delete')) . "</a>";
            }
        }
    }

    public function format($details = true)
    {
        if ($details) {
            $object = new $this->object_type($this->object_id);
            $object->format();
            $this->f_name        = $object->get_fullname();
            $this->f_object_link = $object->f_link;
            $user                = new User($this->user);
            $user->format();
            $this->f_user = $user->f_name;
        }
        $this->f_allow_stream   = $this->allow_stream;
        $this->f_allow_download = $this->allow_download;
        $this->f_creation_date  = date("Y-m-d H:i:s", $this->creation_date);
        $this->f_lastvisit_date = ($this->lastvisit_date > 0) ? date("Y-m-d H:i:s", $this->creation_date) : '';
    }

    public function update(array $data)
    {
        $this->max_counter    = intval($data['max_counter']);
        $this->expire_days    = intval($data['expire']);
        $this->allow_stream   = $data['allow_stream'] == '1';
        $this->allow_download = $data['allow_download'] == '1';
        $this->description    = isset($data['description']) ? $data['description'] : $this->description;

        $sql = "UPDATE `share` SET `max_counter` = ?, `expire_days` = ?, `allow_stream` = ?, `allow_download` = ?, `description` = ? " .
            "WHERE `id` = ?";
        $params = array($this->max_counter, $this->expire_days, $this->allow_stream ? 1 : 0, $this->allow_download ? 1 : 0, $this->description, $this->id);
        if (!$GLOBALS['user']->has_access('75')) {
            $sql .= " AND `user` = ?";
            $params[] = $GLOBALS['user']->id;
        }

        return Dba::write($sql, $params);
    }

    public function save_access()
    {
        $sql = "UPDATE `share` SET `counter` = (`counter` + 1), lastvisit_date = ? WHERE `id` = ?";

        return Dba::write($sql, array(time(), $this->id));
    }

    public function is_valid($secret, $action)
    {
        if (!$this->id) {
            debug_event('share', 'Access Denied: Invalid share.', '3');

            return false;
        }

        if (!AmpConfig::get('share')) {
            debug_event('share', 'Access Denied: share feature disabled.', '3');

            return false;
        }

        if ($this->expire_days > 0 && ($this->creation_date + ($this->expire_days * 86400)) < time()) {
            debug_event('share', 'Access Denied: share expired.', '3');

            return false;
        }

        if ($this->max_counter > 0 && $this->counter >= $this->max_counter) {
            debug_event('share', 'Access Denied: max counter reached.', '3');

            return false;
        }

        if (!empty($this->secret) && $secret != $this->secret) {
            debug_event('share', 'Access Denied: secret requires to access share ' . $this->id . '.', '3');

            return false;
        }

        if ($action == 'download' && (!AmpConfig::get('download') || !$this->allow_download)) {
            debug_event('share', 'Access Denied: download unauthorized.', '3');

            return false;
        }

        if ($action == 'stream' && !$this->allow_stream) {
            debug_event('share', 'Access Denied: stream unauthorized.', '3');

            return false;
        }

        return true;
    }

    public function create_fake_playlist()
    {
        $playlist = new Stream_Playlist(-1);
        $medias   = array();

        switch ($this->object_type) {
            case 'album':
            case 'playlist':
                $object = new $this->object_type($this->object_id);
                $songs  = $object->get_medias('song');
                foreach ($songs as $song) {
                    $medias[] = $song;
                }
            break;
            default:
                $medias[] = array(
                    'object_type' => $this->object_type,
                    'object_id' => $this->object_id,
                );
            break;
        }

        $playlist->add($medias, '&share_id=' . $this->id . '&share_secret=' . $this->secret);

        return $playlist;
    }

    public function is_shared_media($media_id)
    {
        $is_shared = false;
        switch ($this->object_type) {
            case 'album':
            case 'playlist':
                $object = new $this->object_type($this->object_id);
                $songs  = $object->get_songs();
                foreach ($songs as $id) {
                    $is_shared = ($media_id == $id);
                    if ($is_shared) {
                        break;
                    }
                }
            break;
            default:
                $is_shared = (($this->object_type == 'song' || $this->object_type == 'video') && $this->object_id == $media_id);
            break;
        }

        return $is_shared;
    }

    public function get_user_owner()
    {
        return $this->user;
    }

    public static function display_ui($object_type, $object_id, $show_text = true)
    {
        echo "<a onclick=\"showShareDialog(event, '" . $object_type . "', " . $object_id . ");\">" . UI::get_icon('share', T_('Share'));
        if ($show_text) {
            echo " &nbsp;" . T_('Share');
        }
        echo "</a>";
    }

    public static function display_ui_links($object_type, $object_id)
    {
        echo "<ul>";
        echo "<li><a onclick=\"handleShareAction('" . AmpConfig::get('web_path') . "/share.php?action=show_create&type=" . $object_type . "&id=" . $object_id . "')\">" . UI::get_icon('share', T_('Advanced Share')) . " &nbsp;" . T_('Advanced Share') . "</a></li>";
        if (AmpConfig::get('download')) {
            $dllink = "";
            if ($object_type == "song" || $object_type == "video") {
                $dllink = AmpConfig::get('web_path') . "/play/index.php?action=download&type=" . $object_type . "&oid=" . $object_id . "&uid=-1";
            } else {
                if (Access::check_function('batch_download') && check_can_zip($object_type)) {
                    $dllink = AmpConfig::get('web_path') . "/batch.php?action=" . $object_type . "&id=" . $object_id;
                }
            }
            if (!empty($dllink)) {
                if (AmpConfig::get('require_session')) {
                    // Add session information to the link to avoid authentication
                    $dllink .= "&ssid=" . Stream::get_session();
                }
                echo "<li><a rel=\"nohtml\" href=\"" . $dllink . "\">" . UI::get_icon('download', T_('Temporary direct link')) . " &nbsp;" . T_('Temporary direct link') . "</a></li>";
            }
        }
        echo "<li style='padding-top: 8px; text-align: right;'>";
        $plugins = Plugin::get_plugins('external_share');
        foreach ($plugins as $plugin_name) {
            echo "<a onclick=\"handleShareAction('" . AmpConfig::get('web_path') . "/share.php?action=external_share&plugin=" . $plugin_name . "&type=" . $object_type . "&id=" . $object_id . "')\" target=\"_blank\">" . UI::get_icon('share_' . strtolower($plugin_name), $plugin_name) . "</a>&nbsp;";
        }
        echo "</li>";
        echo "</ul>";
    }
} // end of recommendation class
