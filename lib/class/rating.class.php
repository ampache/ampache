<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPLv3)
 * Copyright 2001 - 2015 Ampache.org
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

/**
 * Rating class
 *
 * This tracks ratings for songs, albums, artists, videos, tvshows, movies ...
 *
 */
class Rating extends database_object
{
    // Public variables
    public $id;        // The ID of the object rated
    public $type;        // The type of object we want

    /**
     * Constructor
     * This is run every time a new object is created, and requires
     * the id and type of object that we need to pull the rating for
     */
    public function __construct($id, $type)
    {
        $this->id   = intval($id);
        $this->type = $type;

        return true;
    } // Constructor

    /**
     * gc
     *
     * Remove ratings for items that no longer exist.
     */
    public static function gc()
    {
        $types = array('song', 'album', 'artist', 'video', 'tvshow', 'tvshow_season', 'playlist', 'label', 'podcast', 'podcast_episode');

        if ($object_type != null) {
            if (in_array($object_type, $types)) {
                $sql = "DELETE FROM `rating` WHERE `object_type` = ? AND `object_id` = ?";
                Dba::write($sql, array($object_type, $object_id));
            } else {
                debug_event('rating', 'Garbage collect on type `' . $object_type . '` is not supported.', 1);
            }
        } else {
            foreach ($types as $type) {
                Dba::write("DELETE FROM `rating` USING `rating` LEFT JOIN `$type` ON `$type`.`id` = `rating`.`object_id` WHERE `object_type` = '$type' AND `$type`.`id` IS NULL");
            }
        }
    }

    /**
      * build_cache
     * This attempts to get everything we'll need for this page load in a
     * single query, saving on connection overhead
     */
    public static function build_cache($type, $ids)
    {
        if (!is_array($ids) or !count($ids)) {
            return false;
        }

        $ratings      = array();
        $user_ratings = array();

        $idlist = '(' . implode(',', $ids) . ')';
        $sql    = "SELECT `rating`, `object_id` FROM `rating` " .
            "WHERE `user` = ? AND `object_id` IN $idlist " .
            "AND `object_type` = ?";
        $db_results = Dba::read($sql, array($GLOBALS['user']->id, $type));

        while ($row = Dba::fetch_assoc($db_results)) {
            $user_ratings[$row['object_id']] = $row['rating'];
        }

        $sql = "SELECT AVG(`rating`) as `rating`, `object_id` FROM " .
            "`rating` WHERE `object_id` IN $idlist AND " .
            "`object_type` = ? GROUP BY `object_id`";
        $db_results = Dba::read($sql, array($type));

        while ($row = Dba::fetch_assoc($db_results)) {
            $ratings[$row['object_id']] = $row['rating'];
        }

        foreach ($ids as $id) {
            // First store the user-specific rating
            if (!isset($user_ratings[$id])) {
                $rating = 0;
            } else {
                $rating = intval($user_ratings[$id]);
            }
            parent::add_to_cache('rating_' . $type . '_user' . $GLOBALS['user']->id, $id, $rating);

            // Then store the average
            if (!isset($ratings[$id])) {
                $rating = 0;
            } else {
                $rating = round($ratings[$id], 1);
            }
            parent::add_to_cache('rating_' . $type . '_all', $id, $rating);
        }

        return true;
    } // build_cache

    /**
     * get_user_rating
     * Get a user's rating.  If no userid is passed in, we use the currently
     * logged in user.
     */
     public function get_user_rating($user_id = null)
     {
         if (is_null($user_id)) {
             $user_id = $GLOBALS['user']->id;
         }

         $key = 'rating_' . $this->type . '_user' . $user_id;
         if (parent::is_cached($key, $this->id)) {
             return parent::get_from_cache($key, $this->id);
         }

         $sql = "SELECT `rating` FROM `rating` WHERE `user` = ? " .
            "AND `object_id` = ? AND `object_type` = ?";
         $db_results = Dba::read($sql, array($user_id, $this->id, $this->type));

         $rating = 0;

         if ($results = Dba::fetch_assoc($db_results)) {
             $rating = $results['rating'];
         }

         parent::add_to_cache($key, $this->id, $rating);
         return $rating;
     } // get_user_rating

    /**
     * get_average_rating
     * Get the floored average rating of what everyone has rated this object
     * as. This is shown if there is no personal rating.
     */
    public function get_average_rating()
    {
        if (parent::is_cached('rating_' . $this->type . '_all', $this->id)) {
            return parent::get_from_cache('rating_' . $this->type . '_user', $this->id);
        }

        $sql = "SELECT AVG(`rating`) as `rating` FROM `rating` WHERE " .
            "`object_id` = ? AND `object_type` = ?";
        $db_results = Dba::read($sql, array($this->id, $this->type));

        $results = Dba::fetch_assoc($db_results);

        parent::add_to_cache('rating_' . $this->type . '_all', $this->id, $results['rating']);
        return $results['rating'];
    } // get_average_rating

    /**
     * get_highest_sql
     * Get highest sql
     */
    public static function get_highest_sql($type)
    {
        $type = Stats::validate_type($type);
        $sql  = "SELECT `object_id` as `id`, AVG(`rating`) AS `rating` FROM rating" .
            " WHERE object_type = '" . $type . "'";
        if (AmpConfig::get('catalog_disable')) {
            $sql .= " AND " . Catalog::get_enable_filter($type, '`object_id`');
        }
        $sql .= " GROUP BY object_id ORDER BY `rating` DESC ";
        return $sql;
    }

    /**
     * get_highest
     * Get objects with the highest average rating.
     */
    public static function get_highest($type, $count='', $offset='')
    {
        if (!$count) {
            $count = AmpConfig::get('popular_threshold');
        }
        $count = intval($count);
        if (!$offset) {
            $limit = $count;
        } else {
            $limit = intval($offset) . "," . $count;
        }

        /* Select Top objects counting by # of rows */
        $sql = self::get_highest_sql($type);
        $sql .= "LIMIT $limit";
        $db_results = Dba::read($sql, array($type));

        $results = array();

        while ($row = Dba::fetch_assoc($db_results)) {
            $results[] = $row['id'];
        }

        return $results;
    }

    /**
     * set_rating
     * This function sets the rating for the current object.
     * If no userid is passed in, we use the currently logged in user.
     */
    public function set_rating($rating, $user_id = null)
    {
        if (is_null($user_id)) {
            $user_id = $GLOBALS['user']->id;
        }
        $user_id = intval($user_id);

        debug_event('Rating', "Setting rating for $this->type $this->id to $rating", 5);

        // If score is -1, then remove rating
        if ($rating == '-1') {
            $sql = "DELETE FROM `rating` WHERE " .
                "`object_id` = ? AND " .
                "`object_type` = ? AND " .
                "`user` = ?";
            $params = array($this->id, $this->type, $user_id);
        } else {
            $sql = "REPLACE INTO `rating` " .
            "(`object_id`, `object_type`, `rating`, `user`) " .
            "VALUES (?, ?, ?, ?)";
            $params = array($this->id, $this->type, $rating, $user_id);
        }
        Dba::write($sql, $params);

        parent::add_to_cache('rating_' . $this->type . '_user' . $user_id, $this->id, $rating);

        foreach (Plugin::get_plugins('save_rating') as $plugin_name) {
            $plugin = new Plugin($plugin_name);
            if ($plugin->load($GLOBALS['user'])) {
                $plugin->_plugin->save_rating($this, $rating);
            }
        }

        return true;
    } // set_rating

    /**
     * show
     * This takes an id and a type and displays the rating if ratings are
     * enabled.  If $static is true, the rating won't be editable.
     */
    public static function show($object_id, $type, $static=false)
    {
        // If ratings aren't enabled don't do anything
        if (!AmpConfig::get('ratings')) {
            return false;
        }

        $rating = new Rating($object_id, $type);

        if ($static) {
            require AmpConfig::get('prefix') . UI::find_template('show_static_object_rating.inc.php');
        } else {
            require AmpConfig::get('prefix') . UI::find_template('show_object_rating.inc.php');
        }
    } // show
} //end rating class
