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

/**
 * Userflag class
 *
 * This user flag/unflag songs, albums, artists, videos, tvshows, movies ... as favorite.
 *
 */
class Useractivity extends database_object
{
    /* Variables from DB */
    public $id;
    public $user;
    public $object_type;
    public $object_id;
    public $action;
    public $activity_date;

    /**
     * Constructor
     * This is run every time a new object is created, and requires
     * the id and type of object that we need to pull the flag for
     * @param integer $useract_id
     */
    public function __construct($useract_id)
    {
        if (!$useract_id) {
            return false;
        }

        /* Get the information from the db */
        $info = $this->get_info($useract_id, 'user_activity');

        foreach ($info as $key => $value) {
            $this->$key = $value;
        } // foreach info

        return true;
    } // Constructor

    /**
     * this attempts to build a cache of the data from the passed activities all in one query
     * @param integer[] $ids
     * @return boolean
     */
    public static function build_cache($ids)
    {
        if (empty($ids)) {
            return false;
        }

        $idlist     = '(' . implode(',', $ids) . ')';
        $sql        = "SELECT * FROM `user_activity` WHERE `id` IN $idlist";
        $db_results = Dba::read($sql);

        while ($row = Dba::fetch_assoc($db_results)) {
            parent::add_to_cache('user_activity', $row['id'], $row);
        }

        return true;
    }

    /**
     * garbage_collection
     *
     * Remove activities for items that no longer exist.
     * @param string $object_type
     * @param integer $object_id
     */
    public static function garbage_collection($object_type = null, $object_id = null)
    {
        $types = array('song', 'album', 'artist', 'video', 'tvshow', 'tvshow_season');

        if ($object_type !== null) {
            if (in_array($object_type, $types)) {
                $sql = "DELETE FROM `user_activity` WHERE `object_type` = ? AND `object_id` = ?";
                Dba::write($sql, array($object_type, $object_id));
            } else {
                debug_event(self::class, 'Garbage collect on type `' . $object_type . '` is not supported.', 1);
            }
        } else {
            foreach ($types as $type) {
                Dba::write("DELETE FROM `user_activity` USING `user_activity` LEFT JOIN `$type` ON `$type`.`id` = `user_activity`.`object_id` WHERE `object_type` = '$type' AND `$type`.`id` IS NULL");
            }
        }
    }

    /**
     * post_activity
     * @param integer $user_id
     * @param string $action
     * @param string $object_type
     * @param integer $object_id
     * @param integer $date
     * @return PDOStatement|boolean
     */
    public static function post_activity($user_id, $action, $object_type, $object_id, $date)
    {
        if ($object_type === 'song') {
            // insert fields to be more like last.fm activity stats
            $sql = "INSERT INTO `user_activity` (`user`, `action`, `object_type`, `object_id`, `activity_date`," .
                    " `name_track`, `name_artist`, `name_album`, `mbid_track`, `mbid_artist`, `mbid_album`)" .
                    " VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $song = new Song($object_id);
            $song->format();
            $name_song   = $song->f_title;
            $name_artist = $song->f_artist;
            $name_album  = $song->f_album;
            $mbid_song   = $song->mbid;
            $mbid_artist = $song->artist_mbid;
            $mbid_album  = $song->album_mbid;
            debug_event(self::class, 'post_activity: ' . $action . ' ' . $object_type . ' by user: ' . $user_id . ': {' . $object_id . '}', 5);

            if ($name_song && $name_artist && $name_album) {
                return Dba::write($sql, array($user_id, $action, $object_type, $object_id, $date, $name_song, $name_artist, $name_album, $mbid_song, $mbid_artist, $mbid_album));
            }
            $sql = "INSERT INTO `user_activity` (`user`, `action`, `object_type`, `object_id`, `activity_date`) VALUES (?, ?, ?, ?, ?)";

            return Dba::write($sql, array($user_id, $action, $object_type, $object_id, $date));
        }
        if ($object_type === 'artist') {
            // insert fields to be more like last.fm activity stats
            $sql = "INSERT INTO `user_activity` (`user`, `action`, `object_type`, `object_id`, `activity_date`, `name_artist`, `mbid_artist`)" .
                    " VALUES (?, ?, ?, ?, ?, ?, ?)";
            $artist = new Artist($object_id);
            $artist->format();
            $name_artist = $artist->f_name;
            $mbid_artist = $artist->mbid;
            debug_event(self::class, 'post_activity: ' . $action . ' ' . $object_type . ' by user: ' . $user_id . ': {' . $object_id . '}', 5);

            if ($name_artist) {
                return Dba::write($sql, array($user_id, $action, $object_type, $object_id, $date, $name_artist, $mbid_artist));
            }
            $sql = "INSERT INTO `user_activity` (`user`, `action`, `object_type`, `object_id`, `activity_date`) VALUES (?, ?, ?, ?, ?)";

            return Dba::write($sql, array($user_id, $action, $object_type, $object_id, $date));
        }
        if ($object_type === 'album') {
            // insert fields to be more like last.fm activity stats
            $sql = "INSERT INTO `user_activity` (`user`, `action`, `object_type`, `object_id`, `activity_date`, `name_artist`, `name_album`, `mbid_artist`, `mbid_album`)" .
                    " VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $album = new Album($object_id);
            $album->format();
            $name_artist  = $album->f_album_artist_name;
            $name_album   = $album->f_title;
            $mbid_album   = $album->mbid;
            $mbid_artist  = $album->mbid_group;

            if ($name_artist && $name_album) {
                debug_event(self::class, 'post_activity: ' . $action . ' ' . $object_type . ' by user: ' . $user_id . ': {' . $object_id . '}', 5);

                return Dba::write($sql, array($user_id, $action, $object_type, $object_id, $date, $name_artist, $name_album, $mbid_artist, $mbid_album));
            }
            $sql = "INSERT INTO `user_activity` (`user`, `action`, `object_type`, `object_id`, `activity_date`) VALUES (?, ?, ?, ?, ?)";

            return Dba::write($sql, array($user_id, $action, $object_type, $object_id, $date));
        }
        // This is probably a good feature to keep by default
        debug_event(self::class, 'post_activity: ' . $action . ' ' . $object_type . ' by user: ' . $user_id . ': {' . $object_id . '}', 5);
        $sql = "INSERT INTO `user_activity` (`user`, `action`, `object_type`, `object_id`, `activity_date`) VALUES (?, ?, ?, ?, ?)";

        return Dba::write($sql, array($user_id, $action, $object_type, $object_id, $date));
    }

    /**
     * del_activity
     * Delete activity by date
     * @param integer $date
     * @param string $action
     * @param integer $user_id
     * @return PDOStatement|boolean
     */
    public static function del_activity($date, $action, $user_id = 0)
    {
        $sql = "DELETE FROM `user_activity` WHERE `activity_date` = ? AND `action` = ? AND `user` = ?";

        return Dba::write($sql, array($date, $action, $user_id));
    }

    /**
     * get_activities
     * @param integer $user_id
     * @param integer $limit
     * @param integer $since
     * @return integer[]
     */
    public static function get_activities($user_id, $limit = 0, $since = 0)
    {
        if ($limit < 1) {
            $limit = AmpConfig::get('popular_threshold', 10);
        }

        $params = array($user_id);
        $sql    = "SELECT `id` FROM `user_activity` WHERE `user` = ?";
        if ($since > 0) {
            $sql .= " AND `activity_date` <= ?";
            $params[] = $since;
        }
        $sql .= " ORDER BY `activity_date` DESC LIMIT " . $limit;
        $db_results = Dba::read($sql, $params);
        $results    = array();
        while ($row = Dba::fetch_assoc($db_results)) {
            $results[] = $row['id'];
        }

        return $results;
    }

    /**
     * get_friends_activities
     * @param integer $user_id
     * @param integer $limit
     * @param integer $since
     * @return integer[]
     */
    public static function get_friends_activities($user_id, $limit = 0, $since = 0)
    {
        if ($limit < 1) {
            $limit = AmpConfig::get('popular_threshold', 10);
        }

        $params = array($user_id);
        $sql    = "SELECT `user_activity`.`id` FROM `user_activity`" .
                " INNER JOIN `user_follower` ON `user_follower`.`follow_user` = `user_activity`.`user`"
                . " WHERE `user_follower`.`user` = ?";
        if ($since > 0) {
            $sql .= " AND `user_activity`.`activity_date` <= ?";
            $params[] = $since;
        }
        $sql .= " ORDER BY `user_activity`.`activity_date` DESC LIMIT " . $limit;
        $db_results = Dba::read($sql, $params);
        $results    = array();
        while ($row = Dba::fetch_assoc($db_results)) {
            $results[] = $row['id'];
        }

        return $results;
    }

    /**
     * show
     * Show the activity entry.
     */
    public function show()
    {
        // If user flags aren't enabled don't do anything
        if (!AmpConfig::get('userflags') || !$this->id) {
            return false;
        }

        $user = new User($this->user);
        $user->format();
        $libitem = new $this->object_type($this->object_id);
        $libitem->format();

        echo '<div>';
        $time_format = AmpConfig::get('custom_datetime') ? (string) AmpConfig::get('custom_datetime') : 'm/d/Y H:i';
        $fdate       = get_datetime($time_format, (int) $this->activity_date);

        echo $fdate . ' ';

        $descr = $user->f_link . ' ';
        switch ($this->action) {
            case 'shout':
                $descr .= T_('commented on');
                break;
            case 'upload':
                $descr .= T_('uploaded');
                break;
            case 'play':
                $descr .= T_('played');
                break;
            case 'userflag':
                $descr .= T_('favorited');
                break;
            case 'follow':
                $descr .= T_('started to follow');
                break;
            default:
                $descr .= T_('did something on');
                break;
        }
        $descr .= ' ' . $libitem->f_link;
        echo $descr;

        /*
        if (Core::is_library_item($this->object_type)) {
            echo ' ';
            $libitem->display_art(10);
        }
        echo '</div>';
         */

        echo '</div>';//<br />';

        return true;
    } // show

    /**
     * Migrate an object associate stats to a new object
     * @param string $object_type
     * @param integer $old_object_id
     * @param integer $new_object_id
     * @return PDOStatement|boolean
     */
    public static function migrate($object_type, $old_object_id, $new_object_id)
    {
        $sql = "UPDATE `user_activity` SET `object_id` = ? WHERE `object_type` = ? AND `object_id` = ?";

        return Dba::write($sql, array($new_object_id, $object_type, $old_object_id));
    }
} // end useractivity.class
