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

use Ampache\Module\Api\Ajax;
use Ampache\Module\Statistics\Stats;
use Ampache\Module\System\Dba;
use Ampache\Config\AmpConfig;
use Ampache\Module\System\Core;
use Exception;
use PDOStatement;

/**
 * This tracks ratings for songs, albums, artists, videos, tvshows, movies ...
 */
class Rating extends database_object
{
    protected const DB_TABLENAME = 'rating';

    // Public variables
    public $id; // The ID of the object rated
    public $type; // The type of object we want

    /**
     * Constructor
     * This is run every time a new object is created, and requires
     * the id and type of object that we need to pull the rating for
     * @param integer $rating_id
     * @param string $type
     */
    public function __construct($rating_id, $type)
    {
        $this->id   = (int)$rating_id;
        $this->type = $type;

        return true;
    } // Constructor

    public function getId(): int
    {
        return (int) $this->id;
    }

    /**
     * garbage_collection
     *
     * Remove ratings for items that no longer exist.
     * @param string $object_type
     * @param integer $object_id
     */
    public static function garbage_collection($object_type = null, $object_id = null)
    {
        $types = array(
            'song',
            'album',
            'artist',
            'video',
            'tvshow',
            'tvshow_season',
            'playlist',
            'label',
            'podcast',
            'podcast_episode'
        );

        if ($object_type !== null && $object_type !== '') {
            if (in_array($object_type, $types)) {
                $sql = "DELETE FROM `rating` WHERE `object_type` = ? AND `object_id` = ?";
                Dba::write($sql, array($object_type, $object_id));
            } else {
                debug_event(self::class, 'Garbage collect on type `' . $object_type . '` is not supported.', 1);
            }
        } else {
            foreach ($types as $type) {
                Dba::write("DELETE FROM `rating` WHERE `object_type` = '$type' AND `rating`.`object_id` NOT IN (SELECT `$type`.`id` FROM `$type`);");
            }
        }
        // delete 'empty' ratings
        Dba::write("DELETE FROM `rating` WHERE `rating`.`rating` = 0");
    }

    /**
     * build_cache
     * This attempts to get everything we'll need for this page load in a
     * single query, saving on connection overhead
     * @param string $type
     * @param array $ids
     * @param integer $user_id
     * @return boolean
     */
    public static function build_cache($type, $ids, $user_id = null)
    {
        if (empty($ids)) {
            return false;
        }
        if ($user_id === null) {
            $user_id = Core::get_global('user')->id;
        }
        $ratings      = array();
        $user_ratings = array();
        $idlist       = '(' . implode(',', $ids) . ')';
        $sql          = "SELECT `rating`, `object_id` FROM `rating` WHERE `user` = ? AND `object_id` IN $idlist AND `object_type` = ?";
        $db_results   = Dba::read($sql, array($user_id, $type));

        while ($row = Dba::fetch_assoc($db_results)) {
            $user_ratings[$row['object_id']] = $row['rating'];
        }

        $sql        = "SELECT ROUND(AVG(`rating`), 2) as `rating`, `object_id` FROM `rating` WHERE `object_id` IN $idlist AND `object_type` = ? GROUP BY `object_id`";
        $db_results = Dba::read($sql, array($type));

        while ($row = Dba::fetch_assoc($db_results)) {
            $ratings[$row['object_id']] = $row['rating'];
        }

        foreach ($ids as $object_id) {
            // First store the user-specific rating
            if (!isset($user_ratings[$object_id])) {
                $rating = 0;
            } else {
                $rating = (int)$user_ratings[$object_id];
            }
            parent::add_to_cache('rating_' . $type . '_user' . $user_id, $object_id, array((int)$rating));

            // Then store the average
            if (!isset($ratings[$object_id])) {
                $rating = 0;
            } else {
                $rating = round($ratings[$object_id], 1);
            }
            parent::add_to_cache('rating_' . $type . '_all', $object_id, array((int)$rating));
        }

        return true;
    } // build_cache

    /**
     * get_user_rating
     * Get a user's rating.  If no userid is passed in, we use the currently
     * logged in user.
     * @param integer $user_id
     * @return double
     */
    public function get_user_rating($user_id = null)
    {
        if ($user_id === null) {
            $user_id = Core::get_global('user')->id;
        }

        $key = 'rating_' . $this->type . '_user' . $user_id;
        if (parent::is_cached($key, $this->id)) {
            return (double)parent::get_from_cache($key, $this->id)[0];
        }

        $sql        = "SELECT `rating` FROM `rating` WHERE `user` = ? AND `object_id` = ? AND `object_type` = ?";
        $db_results = Dba::read($sql, array($user_id, $this->id, $this->type));

        $rating = 0;
        if ($results = Dba::fetch_assoc($db_results)) {
            $rating = $results['rating'];
        }

        parent::add_to_cache($key, $this->id, array((int)$rating));

        return (double)$rating;
    } // get_user_rating

    /**
     * get_average_rating
     * Get the floored average rating of what everyone has rated this object
     * as. This is shown if there is no personal rating.
     * @return double
     */
    public function get_average_rating()
    {
        if (parent::is_cached('rating_' . $this->type . '_all', $this->id)) {
            return (double)parent::get_from_cache('rating_' . $this->type . '_user', $this->id)[0];
        }

        $sql        = "SELECT ROUND(AVG(`rating`), 2) as `rating` FROM `rating` WHERE `object_id` = ? AND `object_type` = ? HAVING COUNT(object_id) > 1";
        $db_results = Dba::read($sql, array($this->id, $this->type));

        $results = Dba::fetch_assoc($db_results);

        parent::add_to_cache('rating_' . $this->type . '_all', $this->id, $results);

        return (double)$results['rating'];
    } // get_average_rating

    /**
     * get_highest_sql
     * Get highest sql
     * @param string $type
     * @param integer $user_id
     * @return string
     */
    public static function get_highest_sql($type, $user_id = null)
    {
        $type              = Stats::validate_type($type);
        $sql               = "SELECT MIN(`rating`.`object_id`) as `id`, ROUND(AVG(`rating`), 2) AS `rating`, COUNT(DISTINCT(`user`)) AS `count` FROM `rating`";
        $allow_group_disks = (AmpConfig::get('album_group') && $type === 'album');
        if ($allow_group_disks) {
            $sql .= " LEFT JOIN `album` ON `rating`.`object_id` = `album`.`id` AND `rating`.`object_type` = 'album'";
        }
        $sql .= " WHERE `object_type` = '" . $type . "'";
        if (AmpConfig::get('catalog_disable') && in_array($type, array('song', 'artist', 'album'))) {
            $sql .= " AND " . Catalog::get_enable_filter($type, '`object_id`');
        }
        if (AmpConfig::get('catalog_filter') && $user_id !== null) {
            $sql .= " AND" . Catalog::get_user_filter("rating_$type", $user_id);
        }
        $sql .= ($allow_group_disks)
            ? " GROUP BY `album`.`prefix`, `album`.`name`, `album`.`album_artist`, `album`.`release_type`, `album`.`release_status`, `album`.`mbid`, `album`.`year`, `album`.`original_year` ORDER BY `rating` DESC, `count` DESC, `id` DESC "
            : " GROUP BY `rating`.`object_id` ORDER BY `rating` DESC, `count` DESC, `id` DESC ";
        //debug_event(self::class, 'get_highest_sql ' . $sql, 5);

        return $sql;
    }

    /**
     * get_highest
     * Get objects with the highest average rating.
     * @param string $type
     * @param integer $count
     * @param integer $offset
     * @return array
     */
    public static function get_highest($type, $count = 0, $offset = 0, $user_id = null)
    {
        if ($count < 1) {
            $count = AmpConfig::get('popular_threshold', 10);
        }
        $limit = ($offset < 1) ? $count : $offset . "," . $count;

        // Select Top objects counting by # of rows
        $sql = self::get_highest_sql($type, $user_id);
        $sql .= " LIMIT $limit";
        //debug_event(self::class, 'get_highest ' . $sql, 5);

        $db_results = Dba::read($sql, array($type));
        $results    = array();
        while ($row = Dba::fetch_assoc($db_results)) {
            $results[] = $row['id'];
        }

        return $results;
    }

    /**
     * set_rating
     * This function sets the rating for the current object.
     * If no user_id is passed in, we use the currently logged in user.
     * @param string $rating
     * @param integer $user_id
     * @return boolean
     */
    public function set_rating($rating, $user_id = null)
    {
        if ($user_id === null) {
            $user_id = Core::get_global('user')->id;
        }
        // albums may be a group of id's
        if ($this->type == 'album' && AmpConfig::get('album_group')) {
            $album       = new Album($this->id);
            $album_array = $album->get_group_disks_ids();
            self::set_rating_for_group($rating, $album_array, $user_id);

            return true;
        }
        // Everything else is a single item
        debug_event(self::class, "Setting rating for $this->type $this->id to $rating", 5);
        if ($rating == '-1') {
            // If score is -1, then remove rating
            $sql    = "DELETE FROM `rating` WHERE `object_id` = ? AND `object_type` = ? AND `user` = ?";
            $params = array($this->id, $this->type, $user_id);
        } else {
            $sql    = "REPLACE INTO `rating` (`object_id`, `object_type`, `rating`, `user`) VALUES (?, ?, ?, ?)";
            $params = array($this->id, $this->type, $rating, $user_id);
        }
        Dba::write($sql, $params);

        parent::add_to_cache('rating_' . $this->type . '_user' . $user_id, $this->id, array($rating));

        self::save_rating($this->id, $this->type, (int)$rating, (int)$user_id);

        return true;
    } // set_rating

    /**
     * set_rating_for_group
     * This function sets the rating for the current object.
     * This is currently only for grouped disk albums!
     * @param string $rating
     * @param array $album_array
     * @param string $user_id
     * @return boolean
     */
    private static function set_rating_for_group($rating, $album_array, $user_id = null)
    {
        foreach ($album_array as $album_id) {
            debug_event(self::class, "Setting rating for 'album' " . $album_id . " to " . $rating, 5);
            if ($rating == '-1') {
                // If score is -1, then remove rating
                $sql = "DELETE FROM `rating` WHERE `object_id` = '" . $album_id . "' AND `object_type` = 'album' AND `user` = " . $user_id;
                Dba::write($sql);
            } else {
                $sql    = "REPLACE INTO `rating` (`object_id`, `object_type`, `rating`, `user`) VALUES (?, ?, ?, ?)";
                $params = array($album_id, 'album', $rating, $user_id);
                Dba::write($sql, $params);

                parent::add_to_cache('rating_' . 'album' . '_user' . (int)$user_id, $album_id, array($rating));
            }
            self::save_rating($album_id, 'album', (int)$rating, (int)$user_id);
        }

        return true;
    } // set_rating_for_group

    /**
     * save_rating
     * Forward rating value to plugins
     * @param integer $object_id
     * @param string $object_type
     * @param integer $new_rating
     * @param integer $user_id
     */
    public static function save_rating($object_id, $object_type, $new_rating, $user_id)
    {
        $rating = new Rating($object_id, $object_type);
        $user   = new User($user_id);
        if ($rating->id) {
            foreach (Plugin::get_plugins('save_rating') as $plugin_name) {
                try {
                    $plugin = new Plugin($plugin_name);
                    if ($plugin->load($user)) {
                        debug_event(self::class, 'save_rating... ' . $plugin->_plugin->name, 5);
                        $plugin->_plugin->save_rating($rating, $new_rating);
                    }
                } catch (Exception $error) {
                    debug_event(self::class, 'save_rating plugin error: ' . $error->getMessage(), 1);
                }
            }
        }
    }

    /**
     * show
     * This takes an id and a type and displays the rating if ratings are
     * enabled.  If $show_global_rating is true, also show the average from all users.
     * @param integer $object_id
     * @param string $type
     * @param boolean $show_global_rating
     */
    public static function show($object_id, $type, $show_global_rating = false): string
    {
        // If ratings aren't enabled don't do anything
        if (!AmpConfig::get('ratings')) {
            return '';
        }

        $rating = new Rating($object_id, $type);

        $base_url = '?action=set_rating&rating_type=' . $rating->type . '&object_id=' . $rating->id;
        $rate     = ($rating->get_user_rating() ?: 0);

        $global_rating = '';

        if ($show_global_rating) {
            $global_rating_value = $rating->get_average_rating();

            if ($global_rating_value > 0) {
                $global_rating = sprintf(
                    '<span class="global-rating" title="%s">
                        (%s)
                    </span>',
                    T_('Average from all users'),
                    $global_rating_value
                );
            }
        }

        // decide width of rating (5 stars -> 20% per star)
        $width = $rate * 20;
        if ($width < 0) {
            $width = 0;
        }

        $ratings = '';

        for ($count = 1; $count < 6; $count++) {
            $ratings .= sprintf(
                '<li>%s</li>',
                Ajax::text($base_url . '&rating=' . $count, '', 'rating' . $count . '_' . $rating->id . '_' . $rating->type, '', 'star' . $count)
            );
        }

        if ($rate < 1) {
            $ratedText = T_('not rated yet');
        } else {
            /* HINT: object rating */
            $ratedText = sprintf(T_('%s of 5'), $rate);
        }

        return sprintf(
            '<span class="star-rating dynamic-star-rating">
                <ul>
                    <li class="current-rating" style="width: %d%%">%s: %s</li>
                    %s
                </ul>
                %s
                %s
            </span>',
            $width,
            T_('Current rating'),
            $ratedText,
            $ratings,
            $global_rating,
            Ajax::text($base_url . '&rating=-1', '', 'rating0_' . $rating->id . '_' . $rating->type, '', 'star0')
        );
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
        $sql = "UPDATE IGNORE `rating` SET `object_id` = ? WHERE `object_type` = ? AND `object_id` = ?";

        return Dba::write($sql, array($new_object_id, $object_type, $old_object_id));
    }
}
