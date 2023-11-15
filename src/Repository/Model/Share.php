<?php
/*
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2023
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
    public const VALID_TYPES     = array(
        'song',
        'album',
        'album_disk',
        'playlist',
        'podcast_episode',
        'video'
    );

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
        $info = $this->get_info($share_id, static::DB_TABLENAME);
        foreach ($info as $key => $value) {
            $this->$key = $value;
        }

        return true;
    } // constructor

    public function getId(): int
    {
        return (int)($this->id ?? 0);
    }

    /**
     * delete_share
     * @param $share_id
     * @param User $user
     * @return PDOStatement|bool
     */
    public static function delete_share($share_id, $user)
    {
        $sql    = "DELETE FROM `share` WHERE `id` = ?";
        $params = array($share_id);
        if (!$user->has_access(75)) {
            $sql .= " AND `user` = ?";
            $params[] = $user->id;
        }

        return Dba::write($sql, $params);
    }

    /**
     * garbage_collection
     */
    public static function garbage_collection(): void
    {
        $sql = "DELETE FROM `share` WHERE (`expire_days` > 0 AND (`creation_date` + (`expire_days` * 86400)) < " . time() . ") OR (`max_counter` > 0 AND `counter` >= `max_counter`)";
        Dba::write($sql);
    }

    /**
     * @param string $type
     * @return string
     */
    public static function is_valid_type($type)
    {
        if (in_array(strtolower($type), self::VALID_TYPES)) {
            return $type;
        }

        return false;
    }

    /**
     * @param int $user_id
     * @param string $object_type
     * @param int $object_id
     * @param bool $allow_stream
     * @param bool $allow_download
     * @param int $expire_days
     * @param string $secret
     * @param int $max_counter
     * @param string $description
     * @return string|null
     */
    public static function create_share(
        $user_id,
        $object_type,
        $object_id,
        $allow_stream = true,
        $allow_download = true,
        $expire_days = 0,
        $secret = '',
        $max_counter = 0,
        $description = ''
    ) {
        if (!self::is_valid_type($object_type)) {
            debug_event(self::class, 'create_share: Bad object_type ' . $object_type, 1);

            return '';
        }
        if (!$allow_stream && !$allow_download) {
            debug_event(self::class, 'create_share: must allow stream OR allow download', 1);

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
                $description = $album->get_fullname() . ' (' . $album->get_artist_fullname() . ')';
            } elseif ($object_type == 'album_disk') {
                $albumdisk = new AlbumDisk($object_id);
                $albumdisk->format();
                $description = $albumdisk->get_fullname() . ' (' . $albumdisk->get_artist_fullname() . ')';
            }
        }
        $sql    = "INSERT INTO `share` (`user`, `object_type`, `object_id`, `creation_date`, `allow_stream`, `allow_download`, `expire_days`, `secret`, `counter`, `max_counter`, `description`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $params = array(
            $user_id,
            $object_type,
            $object_id,
            time(),
            (int)$allow_stream,
            (int)$allow_download,
            $expire_days,
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
    public static function get_share_list_sql(User $user)
    {
        $sql     = "SELECT `id` FROM `share` ";
        $multi   = 'WHERE ';
        if (!$user->has_access(75)) {
            $sql .= "WHERE `user` = " . $user->id;
            $multi = ' AND ';
        }
        if (AmpConfig::get('catalog_filter') && $user->id > 0) {
            $sql .= $multi . Catalog::get_user_filter('share', $user->id);
        }
        //debug_event(self::class, 'get_share_list_sql ' . $sql, 5);

        return $sql;
    }

    /**
     * get_share_list
     * @param User $user
     * @return int[]
     */
    public static function get_share_list(User $user)
    {
        $sql        = self::get_share_list_sql($user);
        $db_results = Dba::read($sql);
        $results    = array();

        while ($row = Dba::fetch_assoc($db_results)) {
            $results[] = (int)$row['id'];
        }

        return $results;
    }

    public function show_action_buttons()
    {
        if ($this->id) {
            if ((!empty(Core::get_global('user')) && Core::get_global('user')->has_access(75)) || $this->user == (int)Core::get_global('user')->id) {
                if ($this->allow_download) {
                    echo "<a class=\"nohtml\" href=\"" . $this->public_url . "&action=download\">" . Ui::get_icon('download', T_('Download')) . "</a>";
                }
                echo "<a id=\"edit_share_ " . $this->id . "\" onclick=\"showEditDialog('share_row', '" . $this->id . "', 'edit_share_" . $this->id . "', '" . T_('Share Edit') . "', 'share_')\">" . Ui::get_icon('edit', T_('Edit')) . "</a>";
                echo "<a href=\"" . AmpConfig::get('web_path') . "/share.php?action=show_delete&id=" . $this->id . "\">" . Ui::get_icon('delete', T_('Delete')) . "</a>";
            }
        }
    }

    /**
     * @return Song|Artist|Album|playlist_object
     */
    private function getObject()
    {
        if ($this->object === null) {
            $className    = ObjectTypeToClassNameMapper::map($this->object_type);
            $this->object = new $className($this->object_id);
        }

        return $this->object;
    }

    public function getObjectUrl(): string
    {
        return $this->getObject()->get_f_link();
    }

    public function getObjectName(): string
    {
        return $this->getObject()->get_fullname();
    }

    public function getUserName(): string
    {
        return User::get_username($this->user);
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
     * @return PDOStatement|bool
     */
    public function update(array $data, $user)
    {
        $this->max_counter    = (int)($data['max_counter']);
        $this->expire_days    = (int)($data['expire']);
        $this->allow_stream   = ($data['allow_stream'] == '1');
        $this->allow_download = ($data['allow_download'] == '1');
        $this->description    = $data['description'] ?? $this->description;

        $sql    = "UPDATE `share` SET `max_counter` = ?, `expire_days` = ?, `allow_stream` = ?, `allow_download` = ?, `description` = ? WHERE `id` = ?";
        $params = array(
            $this->max_counter,
            $this->expire_days,
            $this->allow_stream ? 1 : 0,
            $this->allow_download ? 1 : 0,
            $this->description,
            $this->id
        );
        if (!$user->has_access(75)) {
            $sql .= " AND `user` = ?";
            $params[] = $user->id;
        }

        return Dba::write($sql, $params);
    }

    /**
     * save_access
     * @return PDOStatement|bool
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
     * @return bool
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
     * is_shared_media
     * Has this media object come from a shared object?
     * @param int|string $media_id
     */
    public function is_shared_media($media_id): bool
    {
        $isShare = false;
        switch ($this->object_type) {
            case 'album':
            case 'album_disk':
            case 'playlist':
                $className = ObjectTypeToClassNameMapper::map($this->object_type);
                /** @var Album|AlbumDisk|Playlist $object */
                $object = new $className($this->object_id);
                $songs  = (isset($object->id)) ? $object->get_songs() : array();
                foreach ($songs as $songid) {
                    $isShare = ($media_id == $songid);
                    if ($isShare) {
                        break;
                    }
                }
                break;
            default:
                $isShare = (($this->object_type == 'song' || $this->object_type == 'video') && $this->object_id == $media_id);
                break;
        }

        return $isShare;
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
            case 'album_disk':
            case 'playlist':
                $className = ObjectTypeToClassNameMapper::map($this->object_type);
                /** @var Album|AlbumDisk|Playlist $object */
                $object = new $className($this->object_id);
                $songs  = (isset($object->id)) ? $object->get_medias('song') : array();
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
        if (!empty($medias)) {
            $playlist->add($medias, '&share_id=' . $this->id . '&share_secret=' . $this->secret);
        }

        return $playlist;
    }

    /**
     * @return int|null
     */
    public function get_user_owner()
    {
        return $this->user;
    }

    /**
     * get_expiry
     * get the expiry date in days from a time()
     * @param int $time
     * @return int
     */
    public static function get_expiry($time = null)
    {
        if (isset($time)) {
            // 0 is a valid expiry too
            $expire_days = ((int)$time > 0)
                ? round(($time - time()) / 86400, 0, PHP_ROUND_HALF_EVEN)
                : 0;
        } else {
            // fall back to config defaults
            $expire_days = AmpConfig::get('share_expire', 7);
        }

        return (int)$expire_days;
    }

    /**
     * @param string $object_type
     * @param int $object_id
     * @param bool $show_text
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
     * @param int $old_object_id
     * @param int $new_object_id
     * @return PDOStatement|bool
     */
    public static function migrate($object_type, $old_object_id, $new_object_id)
    {
        $sql = "UPDATE `share` SET `object_id` = ? WHERE `object_type` = ? AND `object_id` = ?";

        return Dba::write($sql, array($new_object_id, $object_type, $old_object_id));
    }
}
