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
     * @param $share_id
     */
    public function __construct($share_id)
    {
        /* Get the information from the db */
        $info = $this->get_info($share_id);

        // Foreach what we've got
        foreach ($info as $key => $value) {
            $this->$key = $value;
        }

        return true;
    } // constructor

    /**
     * delete_share
     * @param $share_id
     * @param User $user
     * @return PDOStatement|boolean
     */
    public static function delete_share($share_id, $user)
    {
        $sql    = "DELETE FROM `share` WHERE `id` = ?";
        $params = array($share_id);
        if (!$user->has_access('75')) {
            $sql .= " AND `user` = ?";
            $params[] = $user->id;
        }

        return Dba::write($sql, $params);
    }

    /**
     * garbage_collection
     */
    public static function garbage_collection()
    {
        $sql = "DELETE FROM `share` WHERE (`expire_days` > 0 AND (`creation_date` + (`expire_days` * 86400)) < " . time() . ") OR (`max_counter` > 0 AND `counter` >= `max_counter`)";
        Dba::write($sql);
    }

    /**
     * delete_shares
     * @param string $object_type
     * @param integer $object_id
     */
    public static function delete_shares($object_type, $object_id)
    {
        $sql = "DELETE FROM `share` WHERE `object_type` = ? AND `object_id` = ?";

        Dba::write($sql, array($object_type, $object_id));
    }

    /**
     * @param string $type
     * @return string
     */
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

    /**
     * @param string $object_type
     * @param integer $object_id
     * @param boolean $allow_stream
     * @param boolean $allow_download
     * @param integer $expire
     * @param string $secret
     * @param integer $max_counter
     * @param string $description
     * @return string|null
     */
    public static function create_share($object_type, $object_id, $allow_stream = true, $allow_download = true, $expire = 0, $secret = '', $max_counter = 0, $description = '')
    {
        $object_type = self::format_type($object_type);
        if (empty($object_type)) {
            return '';
        }
        if (!$allow_stream && !$allow_download) {
            return '';
        }

        if ($description == '') {
            if ($object_type == 'song') {
                $song        = new Song($object_id);
                $description = $song->title;
            } elseif ($object_type == 'playlist') {
                $playlist    = new Playlist($object_id);
                $description = 'Playlist - ' . $playlist->name;
            } elseif ($object_type == 'album') {
                $album = new Album($object_id);
                $album->format();
                $description = $album->f_name . ' (' . $album->f_album_artist_name . ')';
            }
        }
        $sql    = "INSERT INTO `share` (`user`, `object_type`, `object_id`, `creation_date`, `allow_stream`, `allow_download`, `expire_days`, `secret`, `counter`, `max_counter`, `description`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $params = array(Core::get_global('user')->id, $object_type, $object_id, time(), $allow_stream ?: 0, $allow_download ?: 0, $expire, $secret, 0, $max_counter, $description);
        Dba::write($sql, $params);

        $share_id = Dba::insert_id();

        $url = self::get_url($share_id, $secret);
        // Get a shortener url if any available
        foreach (Plugin::get_plugins('shortener') as $plugin_name) {
            try {
                $plugin = new Plugin($plugin_name);
                if ($plugin->load(Core::get_global('user'))) {
                    $short_url = $plugin->_plugin->shortener($url);
                    if (!empty($short_url)) {
                        $url = $short_url;
                        break;
                    }
                }
            } catch (Exception $error) {
                debug_event(self::class, 'Share plugin error: ' . $error->getMessage(), 1);
            }
        }
        $sql = "UPDATE `share` SET `public_url` = ? WHERE `id` = ?";
        Dba::write($sql, array($url, $share_id));

        return $share_id;
    }

    /**
     * get_url
     * @param string $secret
     * @param string|null $share_id
     * @return string
     */
    public static function get_url($share_id, $secret)
    {
        $url = AmpConfig::get('web_path') . '/share.php?id=' . $share_id;
        if (!empty($secret)) {
            $url .= '&secret=' . $secret;
        }

        return $url;
    }

    /**
     * get_share_list_sql
     * @return string
     */
    public static function get_share_list_sql()
    {
        $sql = "SELECT `id` FROM `share` ";

        if (!Core::get_global('user')->has_access('75')) {
            $sql .= "WHERE `user` = '" . (string) Core::get_global('user')->id . "'";
        }

        return $sql;
    }

    /**
     * get_share_list
     * @return array
     */
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

    /**
     * get_shares
     * @param string $object_type
     * @param integer $object_id
     * @return array
     */
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
            if (Core::get_global('user')->has_access('75') || $this->user == (int) Core::get_global('user')->id) {
                if ($this->allow_download) {
                    echo "<a class=\"nohtml\" href=\"" . $this->public_url . "&action=download\">" . UI::get_icon('download', T_('Download')) . "</a>";
                }
                echo "<a id=\"edit_share_ " . $this->id . "\" onclick=\"showEditDialog('share_row', '" . $this->id . "', 'edit_share_" . $this->id . "', '" . T_('Share Edit') . "', 'share_')\">" . UI::get_icon('edit', T_('Edit')) . "</a>";
                echo "<a href=\"" . AmpConfig::get('web_path') . "/share.php?action=show_delete&id=" . $this->id . "\">" . UI::get_icon('delete', T_('Delete')) . "</a>";
            }
        }
    }

    /**
     * format
     * @param boolean $details
     */
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
        $time_format            = AmpConfig::get('custom_datetime') ? (string) AmpConfig::get('custom_datetime') : 'm/d/Y H:i:s';
        $this->f_creation_date  = get_datetime($time_format, (int) $this->creation_date);
        $this->f_lastvisit_date = ($this->lastvisit_date > 0) ? get_datetime($time_format, (int) $this->creation_date) : '';
    }

    /**
     * update
     * @param array $data
     * @param User $user
     * @return PDOStatement|boolean
     */
    public function update(array $data, $user)
    {
        $this->max_counter    = (int) ($data['max_counter']);
        $this->expire_days    = (int) ($data['expire']);
        $this->allow_stream   = ($data['allow_stream'] == '1');
        $this->allow_download = ($data['allow_download'] == '1');
        $this->description    = isset($data['description']) ? $data['description'] : $this->description;

        $sql = "UPDATE `share` SET `max_counter` = ?, `expire_days` = ?, `allow_stream` = ?, `allow_download` = ?, `description` = ? " .
            "WHERE `id` = ?";
        $params = array($this->max_counter, $this->expire_days, $this->allow_stream ? 1 : 0, $this->allow_download ? 1 : 0, $this->description, $this->id);
        if (!$user->has_access('75')) {
            $sql .= " AND `user` = ?";
            $params[] = $user->id;
        }

        return Dba::write($sql, $params);
    }

    /**
     * save_access
     * @return PDOStatement|boolean
     */
    public function save_access()
    {
        $sql = "UPDATE `share` SET `counter` = (`counter` + 1), lastvisit_date = ? WHERE `id` = ?";

        return Dba::write($sql, array(time(), $this->id));
    }

    /**
     * is_valid
     * @param $secret
     * @param $action
     * @return boolean
     */
    public function is_valid($secret, $action)
    {
        if (!$this->id) {
            debug_event(self::class, 'Access Denied: Invalid share.', 3);

            return false;
        }

        if (!AmpConfig::get('share')) {
            debug_event(self::class, 'Access Denied: share feature disabled.', 3);

            return false;
        }

        if ($this->expire_days > 0 && ($this->creation_date + ($this->expire_days * 86400)) < time()) {
            debug_event(self::class, 'Access Denied: share expired.', 3);

            return false;
        }

        if ($this->max_counter > 0 && $this->counter >= $this->max_counter) {
            debug_event(self::class, 'Access Denied: max counter reached.', 3);

            return false;
        }

        if (!empty($this->secret) && $secret != $this->secret) {
            debug_event(self::class, 'Access Denied: secret requires to access share ' . $this->id . '.', 3);

            return false;
        }

        if ($action == 'download' && (!AmpConfig::get('download') || !$this->allow_download)) {
            debug_event(self::class, 'Access Denied: download unauthorized.', 3);

            return false;
        }

        if ($action == 'stream' && !$this->allow_stream) {
            debug_event(self::class, 'Access Denied: stream unauthorized.', 3);

            return false;
        }

        return true;
    }

    /**
     * @return Stream_Playlist
     */
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

    /**
     * @param $media_id
     * @return boolean
     */
    public function is_shared_media($media_id)
    {
        $is_shared = false;
        switch ($this->object_type) {
            case 'album':
            case 'playlist':
                $object = new $this->object_type($this->object_id);
                $songs  = $object->get_songs();
                foreach ($songs as $songid) {
                    $is_shared = ($media_id == $songid);
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

    /**
     * @return mixed
     */
    public function get_user_owner()
    {
        return $this->user;
    }

    /**
     * get_expiry
     * @param integer $days
     * @return integer
     */
    public static function get_expiry($days = null)
    {
        if (isset($days)) {
            $expires = $days;
            // no limit expiry
            if ($expires == 0) {
                $expire_days = 0;
            } else {
                // Parse as a string to work on 32-bit computers
                if (strlen((string) $expires) > 3) {
                    $expires = (int) (substr($expires, 0, - 3));
                }
                $expire_days = round(($expires - time()) / 86400, 0, PHP_ROUND_HALF_EVEN);
            }
        } else {
            // fall back to config defaults
            $expire_days = AmpConfig::get('share_expire');
        }

        return (int) $expire_days;
    }

    /**
     * @param string $object_type
     * @param integer $object_id
     * @param boolean $show_text
     */
    public static function display_ui($object_type, $object_id, $show_text = true)
    {
        echo "<a onclick=\"showShareDialog(event, '" . $object_type . "', " . $object_id . ");\">" . UI::get_icon('share', T_('Share'));
        if ($show_text) {
            echo " &nbsp;" . T_('Share');
        }
        echo "</a>";
    }

    /**
     * @param string $object_type
     * @param integer $object_id
     */
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
                echo "<li><a class=\"nohtml\" href=\"" . $dllink . "\">" . UI::get_icon('download', T_('Temporary direct link')) . " &nbsp;" . T_('Temporary direct link') . "</a></li>";
            }
        }
        echo "<li style='padding-top: 8px; text-align: right;'>";
        $plugins = Plugin::get_plugins('external_share');
        foreach ($plugins as $plugin_name) {
            echo "<a onclick=\"handleShareAction('" . AmpConfig::get('web_path') . "/share.php?action=external_share&plugin=" . $plugin_name . "&type=" . $object_type . "&id=" . $object_id . "')\" target=\"_blank\">" . UI::get_icon('share_' . strtolower((string) $plugin_name), $plugin_name) . "</a>&nbsp;";
        }
        echo "</li>";
        echo "</ul>";
    }

    /**
     * Migrate an object associate stats to a new object
     * @param string $object_type
     * @param integer $old_object_id
     * @param integer $new_object_id
     * @return PDOStatement|boolean
     */
    public static function migrate($object_type, $old_object_id, $new_object_id)
    {
        $sql = "UPDATE `share` SET `object_id` = ? WHERE `object_type` = ? AND `object_id` = ?";

        return Dba::write($sql, array($new_object_id, $object_type, $old_object_id));
    }
} // end share.class
