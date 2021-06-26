<?php
/*
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
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

declare(strict_types=0);

namespace Ampache\Repository\Model;

use Ampache\Config\AmpConfig;
use Ampache\Module\Playback\Stream_Playlist;
use Ampache\Module\System\Core;
use Ampache\Module\System\Dba;
use Ampache\Module\Util\ObjectTypeToClassNameMapper;
use Ampache\Module\Util\Ui;
use Exception;
use PDOStatement;

class Share extends database_object
{
    protected const DB_TABLENAME = 'share';

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
    public $f_user;

    private $object;

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

    public function getId(): int
    {
        return (int) $this->id;
    }

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
    public static function create_share(
        $object_type,
        $object_id,
        $allow_stream = true,
        $allow_download = true,
        $expire = 0,
        $secret = '',
        $max_counter = 0,
        $description = ''
    ) {
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
        $params = array(
            Core::get_global('user')->id,
            $object_type,
            $object_id,
            time(),
            $allow_stream ?: 0,
            $allow_download ?: 0,
            $expire,
            $secret,
            0,
            $max_counter,
            $description
        );
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
     * @param User $user
     * @return string
     */
    public static function get_share_list_sql($user)
    {
        $sql   = "SELECT `id` FROM `share` ";
        $multi = 'WHERE ';
        if (!$user->has_access('75')) {
            $sql .= "WHERE `user` = '" . $user->id . "'";
            $multi = ' AND ';
        }
        if (AmpConfig::get('catalog_filter')) {
            $sql .= $multi . Catalog::get_user_filter('share', $user->id);
        }

        return $sql;
    }

    /**
     * get_share_list
     * @param User $user
     * @return array
     */
    public static function get_share_list($user)
    {
        $sql        = self::get_share_list_sql($user);
        $db_results = Dba::read($sql);
        $results    = array();

        while ($row = Dba::fetch_assoc($db_results)) {
            $results[] = $row['id'];
        }

        return $results;
    }

    public function show_action_buttons()
    {
        if ($this->id) {
            if (Core::get_global('user')->has_access('75') || $this->user == (int)Core::get_global('user')->id) {
                if ($this->allow_download) {
                    echo "<a class=\"nohtml\" href=\"" . $this->public_url . "&action=download\">" . Ui::get_icon('download',
                            T_('Download')) . "</a>";
                }
                echo "<a id=\"edit_share_ " . $this->id . "\" onclick=\"showEditDialog('share_row', '" . $this->id . "', 'edit_share_" . $this->id . "', '" . T_('Share Edit') . "', 'share_')\">" . Ui::get_icon('edit',
                        T_('Edit')) . "</a>";
                echo "<a href=\"" . AmpConfig::get('web_path') . "/share.php?action=show_delete&id=" . $this->id . "\">" . Ui::get_icon('delete',
                        T_('Delete')) . "</a>";
            }
        }
    }

    private function getObject()
    {
        if ($this->object === null) {
            $class_name   = ObjectTypeToClassNameMapper::map($this->object_type);
            $this->object = new $class_name($this->object_id);
            $this->object->format();
        }

        return $this->object;
    }

    public function getObjectUrl(): string
    {
        return $this->getObject()->f_link;
    }

    public function getObjectName(): string
    {
        return $this->getObject()->get_fullname();
    }

    public function getUserName(): string
    {
        $user = new User($this->user);
        $user->format();

        return $user->f_name;
    }

    public function getLastVisitDateFormatted(): string
    {
        return $this->lastvisit_date > 0 ? get_datetime((int) $this->lastvisit_date) : '';
    }

    public function getCreationDateFormatted(): string
    {
        return get_datetime((int) $this->creation_date);
    }

    /**
     * update
     * @param array $data
     * @param User $user
     * @return PDOStatement|boolean
     */
    public function update(array $data, $user)
    {
        $this->max_counter    = (int)($data['max_counter']);
        $this->expire_days    = (int)($data['expire']);
        $this->allow_stream   = ($data['allow_stream'] == '1');
        $this->allow_download = ($data['allow_download'] == '1');
        $this->description    = isset($data['description']) ? $data['description'] : $this->description;

        $sql    = "UPDATE `share` SET `max_counter` = ?, `expire_days` = ?, `allow_stream` = ?, `allow_download` = ?, `description` = ? WHERE `id` = ?";
        $params = array(
            $this->max_counter,
            $this->expire_days,
            $this->allow_stream ? 1 : 0,
            $this->allow_download ? 1 : 0,
            $this->description,
            $this->id
        );
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
                $class_name = ObjectTypeToClassNameMapper::map($this->object_type);
                $object     = new $class_name($this->object_id);
                $songs      = $object->get_medias('song');
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
                if (strlen((string)$expires) > 3) {
                    $expires = (int)(substr($expires, 0, -3));
                }
                $expire_days = round(($expires - time()) / 86400, 0, PHP_ROUND_HALF_EVEN);
            }
        } else {
            // fall back to config defaults
            $expire_days = AmpConfig::get('share_expire');
        }

        return (int)$expire_days;
    }

    /**
     * @param string $object_type
     * @param integer $object_id
     * @param boolean $show_text
     */
    public static function display_ui($object_type, $object_id, $show_text = true)
    {
        $result = sprintf(
            '<a onclick="showShareDialog(event, \'%s\', %d);">%s',
            $object_type,
            $object_id,
            Ui::get_icon('share', T_('Share'))
        );

        if ($show_text) {
            $result .= sprintf('&nbsp;%s', T_('Share'));
        }
        $result .= '</a>';

        return $result;
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
}
