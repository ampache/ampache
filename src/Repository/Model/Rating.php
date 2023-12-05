<?php

/**
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
    private const RATING_TYPES   = array(
        'artist',
        'album',
        'album_disk',
        'song',
        'stream',
        'live_stream',
        'video',
        'playlist',
        'tvshow',
        'tvshow_season',
        'podcast',
        'podcast_episode'
    );

    // Public variables
    public int $id; // The object_id of the object rated
    public string $type; // The object_type of object we want

    /**
     * Constructor
     * This is run every time a new object is created, and requires
     * the id and type of object that we need to pull the rating for
     * @param int|null $rating_id
     * @param string $type
     */
    public function __construct($rating_id, $type)
    {
        $this->id   = (int)$rating_id;
        $this->type = $type;
    }

    public function getId(): int
    {
        return (int)($this->id ?? 0);
    }

    public static function is_valid($type): bool
    {
        return in_array($type, self::RATING_TYPES);
    }

    /**
     * garbage_collection
     *
     * Remove ratings for items that no longer exist.
     * @param string $object_type
     * @param int $object_id
     */
    public static function garbage_collection($object_type = null, $object_id = null): void
    {
        $types = array(
            'album',
            'album_disk',
            'artist',
            'catalog',
            'tag',
            'label',
            'live_stream',
            'playlist',
            'podcast',
            'podcast_episode',
            'song',
            'tvshow',
            'tvshow_season',
            'user',
            'video'
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
        Dba::write("DELETE FROM `rating` WHERE `rating`.`rating` = 0;");
    }

    /**
     * build_cache
     * This attempts to get everything we'll need for this page load in a
     * single query, saving on connection overhead
     * @param string $type
     * @param array $ids
     * @param int $user_id
     */
    public static function build_cache($type, $ids, $user_id = null): bool
    {
        if (empty($ids)) {
            return false;
        }
        if ($user_id === null) {
            $user    = Core::get_global('user');
            $user_id = $user->id ?? 0;
        }
        if ($user_id === 0) {
            return false;
        }
        $ratings      = array();
        $user_ratings = array();
        $idlist       = '(' . implode(',', $ids) . ')';
        $sql          = "SELECT `rating`, `object_id` FROM `rating` WHERE `user` = ? AND `object_id` IN $idlist AND `object_type` = ?";
        $db_results   = Dba::read($sql, array($user_id, $type));

        while ($row = Dba::fetch_assoc($db_results)) {
            $user_ratings[$row['object_id']] = $row['rating'];
        }

        $sql        = "SELECT ROUND(AVG(`rating`), 2) AS `rating`, `object_id` FROM `rating` WHERE `object_id` IN $idlist AND `object_type` = ? GROUP BY `object_id`";
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
            parent::add_to_cache('rating_' . $type . '_user' . $user_id, $object_id, array($rating));

            // Then store the average
            if (!isset($ratings[$object_id])) {
                $rating = 0;
            } else {
                $rating = round($ratings[$object_id], 1);
            }
            parent::add_to_cache('rating_' . $type . '_all', $object_id, array((int)$rating));
        }

        return true;
    }

    /**
     * get_user_rating
     * Get a user's rating. If no userid is passed in, we use the currently logged in user.
     * @param int $user_id
     * @return int|null
     */
    public function get_user_rating($user_id = null): ?int
    {
        if ($user_id === null) {
            $user    = Core::get_global('user');
            $user_id = $user->id ?? 0;
        }
        if ($user_id === 0) {
            return null;
        }

        $key = 'rating_' . $this->type . '_user' . $user_id;
        if (parent::is_cached($key, $this->id) && parent::get_from_cache($key, $this->id)[0] > 0) {
            return parent::get_from_cache($key, $this->id)[0];
        }

        $sql        = "SELECT `rating` FROM `rating` WHERE `user` = ? AND `object_id` = ? AND `object_type` = ? AND `rating` > 0;";
        $db_results = Dba::read($sql, array($user_id, $this->id, $this->type));
        $row        = Dba::fetch_assoc($db_results);
        if (empty($row)) {
            return null;
        }
        $rating = (int)$row['rating'];
        parent::add_to_cache($key, $this->id, array($rating));

        return $rating;
    }

    /**
     * get_average_rating
     * Get the floored average rating of what everyone has rated this object as.
     * @return double|null
     */
    public function get_average_rating(): ?float
    {
        $key = 'rating_' . $this->type . '_all';
        if (parent::is_cached($key, $this->id) && parent::get_from_cache($key, $this->id)[0] > 0) {
            return (float)parent::get_from_cache($key, $this->id)[0];
        }

        $sql        = "SELECT ROUND(AVG(`rating`), 2) AS `rating` FROM `rating` WHERE `object_id` = ? AND `object_type` = ? HAVING COUNT(object_id) > 1";
        $db_results = Dba::read($sql, array($this->id, $this->type));
        $row        = Dba::fetch_assoc($db_results);
        if (empty($row)) {
            return null;
        }
        $rating = (float)$row['rating'];
        parent::add_to_cache($key, $this->id, array($rating));

        return $rating;
    }

    /**
     * get_highest_sql
     * Get highest sql
     * @param string $input_type
     * @param int $user_id
     */
    public static function get_highest_sql($input_type, $user_id = null): string
    {
        $type    = Stats::validate_type($input_type);
        $user_id = (int)($user_id);
        $sql     = "SELECT MIN(`rating`.`object_id`) AS `id`, ROUND(AVG(`rating`.`rating`), 2) AS `rating`, COUNT(DISTINCT(`rating`.`user`)) AS `count` FROM `rating`";
        if ($input_type == 'album_artist' || $input_type == 'song_artist') {
            $sql .= " LEFT JOIN `artist` ON `artist`.`id` = `rating`.`object_id` AND `rating`.`object_type` = 'artist'";
        }
        $sql .= " WHERE `object_type` = '$type'";
        if (AmpConfig::get('catalog_disable') && in_array($input_type, array('artist', 'album', 'album_disk', 'song', 'video'))) {
            $sql .= " AND " . Catalog::get_enable_filter($input_type, '`object_id`');
        }
        if (AmpConfig::get('catalog_filter') && $user_id > 0) {
            $sql .= " AND" . Catalog::get_user_filter("rating_$type", $user_id);
        }
        if ($input_type == 'album_artist') {
            $sql .= " AND `artist`.`album_count` > 0";
        }
        if ($input_type == 'song_artist') {
            $sql .= " AND `artist`.`song_count` > 0";
        }
        $sql .= " GROUP BY `rating`.`object_id` ORDER BY `rating` DESC, `count` DESC, `date` DESC, `id` DESC ";
        //debug_event(self::class, 'get_highest_sql ' . $sql, 5);

        return $sql;
    }

    /**
     * get_highest
     * Get objects with the highest average rating.
     * @param string $input_type
     * @param int $count
     * @param int $offset
     * @param int $user_id
     * @return array
     */
    public static function get_highest($input_type, $count = 0, $offset = 0, $user_id = null)
    {
        if ($count === 0) {
            $count = AmpConfig::get('popular_threshold', 10);
        }
        if ($count === -1) {
            $count  = 0;
            $offset = 0;
        }

        // Select Top objects counting by # of rows
        $sql   = self::get_highest_sql($input_type, $user_id);
        $limit = ($offset < 1)
            ? $count
            : $offset . "," . $count;
        if ($limit > 0) {
            $sql .= "LIMIT $limit";
        }

        //debug_event(self::class, 'get_highest ' . $sql, 5);
        $db_results = Dba::read($sql);
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
     * @param int $user_id
     */
    public function set_rating($rating, $user_id = null): bool
    {
        if ($user_id === null) {
            $user    = Core::get_global('user');
            $user_id = $user->id ?? 0;
        }
        if ($user_id === 0) {
            return false;
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
    }

    /**
     * save_rating
     * Forward rating value to plugins
     * @param int $object_id
     * @param string $object_type
     * @param int $new_rating
     * @param int $user_id
     */
    public static function save_rating($object_id, $object_type, $new_rating, $user_id): void
    {
        $rating = new Rating($object_id, $object_type);
        $user   = new User($user_id);
        if ($rating->id) {
            foreach (Plugin::get_plugins('save_rating') as $plugin_name) {
                try {
                    $plugin = new Plugin($plugin_name);
                    if ($plugin->_plugin !== null && $plugin->load($user)) {
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
     * @param int $object_id
     * @param string $type
     * @param bool $show_global_rating
     */
    public static function show($object_id, $type, $show_global_rating = false): string
    {
        // If ratings aren't enabled don't do anything
        if (!AmpConfig::get('ratings') || !in_array($type, self::RATING_TYPES)) {
            return '';
        }

        $rating = new Rating($object_id, $type);

        $base_url = '?action=set_rating&rating_type=' . $rating->type . '&object_id=' . $rating->id;
        $rate     = ($rating->get_user_rating() ?? 0);

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
        $sql = "UPDATE IGNORE `rating` SET `object_id` = ? WHERE `object_type` = ? AND `object_id` = ?";

        return Dba::write($sql, array($new_object_id, $object_type, $old_object_id));
    }
}
