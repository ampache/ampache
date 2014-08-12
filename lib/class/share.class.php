<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU General Public License, version 2 (GPLv2)
 * Copyright 2001 - 2014 Ampache.org
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
        if (!$id) { return true; }

        /* Get the information from the db */
        $info = $this->get_info($id);

        // Foreach what we've got
        foreach ($info as $key=>$value) {
            $this->$key = $value;
        }

        return true;
    } //constructor

    public static function delete_share($id)
    {
        $sql = "DELETE FROM `share` WHERE `id` = ?";
        $params = array( $id );
        if (!$GLOBALS['user']->has_access('75')) {
            $sql .= " AND `user` = ?";
            $params[] = $GLOBALS['user']->id;
        }

        return Dba::write($sql, $params);
    }

    public static function delete_shares($object_type, $object_id)
    {
        $sql = "DELETE FROM `share` WHERE `object_type` = ? AND `object_id` = ?";

        Dba::write($sql, array($object_type, $object_id));
    }

    public static function generate_secret($length = 8)
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $secret = '';
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
                return $type;
            default:
                return '';
        }
    }

    public static function create_share($object_type, $object_id, $allow_stream=true, $allow_download=true, $expire=0, $secret='', $max_counter=0, $description='')
    {
        $object_type = self::format_type($object_type);
        if (empty($object_type)) return '';

        if (!$allow_stream && !$allow_download) return '';

        $sql = "INSERT INTO `share` (`user`, `object_type`, `object_id`, `creation_date`, `allow_stream`, `allow_download`, `expire_days`, `secret`, `counter`, `max_counter`, `description`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
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
        $sql = self::get_share_list_sql();
        $db_results = Dba::read($sql);
        $results = array();

        while ($row = Dba::fetch_assoc($db_results)) {
            $results[] = $row['id'];
        }

        return $results;
    }

    public static function get_shares($object_type, $object_id)
    {
        $sql = "SELECT `id` FROM `share` WHERE `object_type` = ? AND `object_id` = ?";
        $db_results = Dba::read($sql, array($object_type, $object_id));
        $results = array();
        while ($row = Dba::fetch_assoc($db_results)) {
            $results[] = $row['id'];
        }

        return $results;
    }

    public function show_action_buttons()
    {
        if ($this->id) {
            if ($GLOBALS['user']->has_access('75') || $this->user == $GLOBALS['user']->id) {
                echo "<a href=\"" . AmpConfig::get('web_path') . "/share.php?action=show_delete&id=" . $this->id ."\">" . UI::get_icon('delete', T_('Delete')) . "</a>";
            }
        }
    }

    public function format()
    {
        $object = new $this->object_type($this->object_id);
        $object->format();
        $this->f_object_link = $object->f_link;
        $user = new User($this->user);
        $this->f_user = $user->fullname;
        $this->f_allow_stream = $this->allow_stream;
        $this->f_allow_download = $this->allow_download;
        $this->f_creation_date = date("Y-m-d H:i:s", $this->creation_date);
        $this->f_lastvisit_date = ($this->lastvisit_date > 0) ? date("Y-m-d H:i:s", $this->creation_date) : '';
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
        $medias = array();

        switch ($this->object_type) {
            case 'album':
            case 'playlist':
                $object = new $this->object_type($this->object_id);
                $songs = $object->get_songs('song');
                foreach ($songs as $id) {
                    $medias[] = array(
                        'object_type' => 'song',
                        'object_id' => $id,
                    );
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

    public function is_shared_song($song_id)
    {
        $is_shared = false;
        switch ($this->object_type) {
            case 'album':
            case 'playlist':
                $object = new $this->object_type($this->object_id);
                $songs = $object->get_songs();
                foreach ($songs as $id) {
                    $is_shared = ($song_id == $id);
                    if ($is_shared) { break; }
                }
            break;
            default:
                $is_shared = ($this->object_type == 'song' && $this->object_id == $song_id);
            break;
        }

        return $is_shared;
    }

} // end of recommendation class
