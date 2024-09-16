<?php

declare(strict_types=0);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2024
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

namespace Ampache\Repository\Model;

use Ampache\Module\Api\Ajax;
use Ampache\Module\Statistics\Stats;
use Ampache\Module\System\Dba;
use Ampache\Config\AmpConfig;
use Ampache\Module\System\Core;
use Ampache\Module\System\Plugin\PluginTypeEnum;
use Ampache\Module\User\Activity\UserActivityPosterInterface;
use Exception;

/**
 * This tracks ratings for songs, albums, artists, videos...
 */
class Rating extends database_object
{
    protected const DB_TABLENAME = 'rating';

    private const RATING_TYPES   = [
        'artist',
        'album',
        'album_disk',
        'song',
        'stream',
        'live_stream',
        'video',
        'playlist',
        'search',
        'podcast',
        'podcast_episode',
    ];

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
    public function __construct(
        $rating_id,
        $type
    ) {
        $this->id   = (int)$rating_id;
        $this->type = $type;
    }

    public function getId(): int
    {
        return (int)($this->id ?? 0);
    }

    public static function is_valid(string $type): bool
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
        $types = [
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
            'search',
            'song',
            'user',
            'video',
        ];

        if ($object_type !== null && $object_type !== '') {
            if (in_array($object_type, $types)) {
                $sql = "DELETE FROM `rating` WHERE `object_type` = ? AND `object_id` = ?";
                Dba::write($sql, [$object_type, $object_id]);
            } else {
                debug_event(self::class, 'Garbage collect on type `' . $object_type . '` is not supported.', 1);
            }
        } else {
            foreach ($types as $type) {
                Dba::write(sprintf('DELETE FROM `rating` WHERE `object_type` = \'%s\' AND `rating`.`object_id` NOT IN (SELECT `%s`.`id` FROM `%s`);', $type, $type, $type));
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

        $ratings      = [];
        $user_ratings = [];
        $idlist       = '(' . implode(',', $ids) . ')';
        $sql          = sprintf('SELECT `rating`, `object_id` FROM `rating` WHERE `user` = ? AND `object_id` IN %s AND `object_type` = ?', $idlist);
        $db_results   = Dba::read($sql, [$user_id, $type]);

        while ($row = Dba::fetch_assoc($db_results)) {
            $user_ratings[$row['object_id']] = $row['rating'];
        }

        $sql        = sprintf('SELECT ROUND(AVG(`rating`), 2) AS `rating`, `object_id` FROM `rating` WHERE `object_id` IN %s AND `object_type` = ? GROUP BY `object_id`', $idlist);
        $db_results = Dba::read($sql, [$type]);

        while ($row = Dba::fetch_assoc($db_results)) {
            $ratings[$row['object_id']] = $row['rating'];
        }

        foreach ($ids as $object_id) {
            // First store the user-specific rating
            $rating = isset($user_ratings[$object_id]) ? (int)$user_ratings[$object_id] : 0;

            parent::add_to_cache('rating_' . $type . '_user' . $user_id, $object_id, [$rating]);
            // Then store the average
            $rating = isset($ratings[$object_id]) ? round($ratings[$object_id], 1) : 0;

            parent::add_to_cache('rating_' . $type . '_all', $object_id, [(int)$rating]);
        }

        return true;
    }

    /**
     * get_user_rating
     * Get a user's rating. If no userid is passed in, we use the currently logged in user.
     * @param int $user_id
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

        $params     = [$user_id, $this->id, $this->type];
        $sql        = "SELECT `rating` FROM `rating` WHERE `user` = ? AND `object_id` = ? AND `object_type` = ? AND `rating` > 0;";
        $db_results = Dba::read($sql, $params);
        $row        = Dba::fetch_assoc($db_results);
        //debug_event(self::class, 'get_user_rating ' . $sql . ' ' . print_r($params, true), 5);
        if ($row === []) {
            return null;
        }

        $rating = (int)$row['rating'];
        parent::add_to_cache($key, $this->id, [$rating]);

        return $rating;
    }

    /**
     * get_average_rating
     * Get the floored average rating of what everyone has rated this object as.
     */
    public function get_average_rating(): ?float
    {
        $key = 'rating_' . $this->type . '_all';
        if (parent::is_cached($key, $this->id) && parent::get_from_cache($key, $this->id)[0] > 0) {
            return (float)parent::get_from_cache($key, $this->id)[0];
        }

        $params     = [$this->id, $this->type];
        $sql        = "SELECT ROUND(AVG(`rating`), 2) AS `rating` FROM `rating` WHERE `object_id` = ? AND `object_type` = ? HAVING COUNT(object_id) > 1";
        $db_results = Dba::read($sql, $params);
        $row        = Dba::fetch_assoc($db_results);
        //debug_event(self::class, 'get_average_rating ' . $sql . ' ' . print_r($params, true), 5);
        if ($row === []) {
            return null;
        }

        $rating = (float)$row['rating'];
        parent::add_to_cache($key, $this->id, [$rating]);

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
        $sql     = "SELECT MAX(`rating`.`id`) AS `table_id`, MIN(`rating`.`object_id`) AS `id`, ROUND(AVG(`rating`.`rating`), 2) AS `rating`, COUNT(DISTINCT(`rating`.`user`)) AS `count` FROM `rating`";
        if ($input_type == 'album_artist' || $input_type == 'song_artist') {
            $sql .= " LEFT JOIN `artist` ON `artist`.`id` = `rating`.`object_id` AND `rating`.`object_type` = 'artist'";
        }

        $sql .= sprintf(' WHERE `object_type` = \'%s\'', $type);
        if (AmpConfig::get('catalog_disable') && in_array($input_type, ['artist', 'album', 'album_disk', 'song', 'video'])) {
            $sql .= " AND " . Catalog::get_enable_filter($input_type, '`object_id`');
        }

        if (AmpConfig::get('catalog_filter') && $user_id > 0) {
            $sql .= " AND" . Catalog::get_user_filter('rating_' . $type, $user_id);
        }

        if ($input_type == 'album_artist') {
            $sql .= " AND `artist`.`album_count` > 0";
        }

        if ($input_type == 'song_artist') {
            $sql .= " AND `artist`.`song_count` > 0";
        }

        //debug_event(self::class, 'get_highest_sql ' . $sql, 5);

        return $sql . " GROUP BY `rating`.`object_id` ORDER BY `rating` DESC, `count` DESC, `table_id` DESC ";
    }

    /**
     * get_highest
     * Get objects with the highest average rating.
     * @param string $input_type
     * @param int $count
     * @param int $offset
     * @param int $user_id
     * @return int[]
     */
    public static function get_highest($input_type, $count = 0, $offset = 0, $user_id = null): array
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
            $sql .= 'LIMIT ' . $limit;
        }

        //debug_event(self::class, 'get_highest ' . $sql, 5);
        $db_results = Dba::read($sql);
        $results    = [];
        while ($row = Dba::fetch_assoc($db_results)) {
            $results[] = (int)$row['id'];
        }

        return $results;
    }

    /**
     * set_rating
     * This function sets the rating for the current object.
     * If no user_id is passed in, we use the currently logged in user.
     * @param int $rating
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

        $time = time();
        // Everything else is a single item
        debug_event(self::class, sprintf('Setting rating for %s %d to %d', $this->type, $this->id, $rating), 5);
        if ($rating < 1) {
            // If score is negative or 0, then remove rating
            $sql    = "DELETE FROM `rating` WHERE `object_id` = ? AND `object_type` = ? AND `user` = ?";
            $params = [$this->id, $this->type, $user_id];
        } else {
            $sql    = "REPLACE INTO `rating` (`object_id`, `object_type`, `rating`, `user`, `date`) VALUES (?, ?, ?, ?, ?)";
            $params = [
                $this->id,
                $this->type,
                $rating,
                $user_id,
                $time,
            ];

            $this->getUserActivityPoster()->post((int) $user_id, 'rating', $this->type, $this->id, $time);
        }

        Dba::write($sql, $params);

        parent::add_to_cache('rating_' . $this->type . '_user' . $user_id, $this->id, [$rating]);

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
        if ($rating->id !== 0) {
            foreach (Plugin::get_plugins(PluginTypeEnum::RATING_SAVER) as $plugin_name) {
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
     * get_latest_sql
     * Get the latest sql
     * @param string $input_type
     * @param int $since
     * @param int $before
     */
    public static function get_latest_sql(
        $input_type,
        ?User $user = null,
        $since = 0,
        $before = 0
    ): string {
        $type    = Stats::validate_type($input_type);
        $sql     = "SELECT DISTINCT(`rating`.`object_id`) AS `id`, `rating`.`rating`, `rating`.`object_type` AS `type`, MAX(`rating`.`user`) AS `user`, MAX(`rating`.`date`) AS `date` FROM `rating`";
        if ($input_type == 'album_artist' || $input_type == 'song_artist') {
            $sql .= " LEFT JOIN `artist` ON `artist`.`id` = `rating`.`object_id` AND `rating`.`object_type` = 'artist'";
        }

        $sql .= ($user !== null)
            ? " WHERE `rating`.`object_type` = '" . $type . "' AND `rating`.`user` = '" . $user->getId() . "'"
            : " WHERE `rating`.`object_type` = '" . $type . "'";
        if (AmpConfig::get('catalog_disable') && in_array($type, ['artist', 'album', 'album_disk', 'song', 'video'])) {
            $sql .= " AND " . Catalog::get_enable_filter($type, '`object_id`');
        }

        if (AmpConfig::get('catalog_filter') && $user !== null) {
            $sql .= " AND" . Catalog::get_user_filter('rating_' . $type, $user->getId());
        }

        if ($input_type == 'album_artist') {
            $sql .= " AND `artist`.`album_count` > 0";
        }

        if ($input_type == 'song_artist') {
            $sql .= " AND `artist`.`song_count` > 0";
        }

        if ($since > 0) {
            $sql .= " AND `rating`.`date` >= '" . $since . "'";
            if ($before > 0) {
                $sql .= " AND `rating`.`date` <= '" . $before . "'";
            }
        }

        //debug_event(self::class, 'get_latest_sql ' . $sql, 5);

        return $sql . " GROUP BY `rating`.`object_id`, `type` ORDER BY `rating` DESC, `date` DESC ";
    }

    /**
     * get_latest
     * Get the latest user flagged objects
     * @param string $type
     * @param int $count
     * @param int $offset
     * @param int $since
     * @param int $before
     * @return int[]
     */
    public static function get_latest(
        $type,
        ?User $user = null,
        $count = 0,
        $offset = 0,
        $since = 0,
        $before = 0
    ): array {
        if ($count === 0) {
            $count = AmpConfig::get('popular_threshold', 10);
        }

        if ($count === -1) {
            $count  = 0;
            $offset = 0;
        }

        // Select Top objects counting by # of rows
        $sql   = self::get_latest_sql($type, $user, $since, $before);
        $limit = ($offset < 1)
            ? $count
            : $offset . "," . $count;
        if ($limit > 0) {
            $sql .= 'LIMIT ' . $limit;
        }

        //debug_event(self::class, 'get_latest ' . $sql, 5);
        $db_results = Dba::read($sql);
        $results    = [];
        while ($row = Dba::fetch_assoc($db_results)) {
            $results[] = (int)$row['id'];
        }

        return $results;
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

        for ($count = 1; $count < 6; ++$count) {
            $ratings .= sprintf(
                '<li>%s</li>',
                Ajax::text($base_url . '&rating=' . $count, '', 'rating' . $count . '_' . $rating->id . '_' . $rating->type, '', 'star' . $count)
            );
        }

        $ratedText = $rate < 1 ? T_('not rated yet') : sprintf(T_('%s of 5'), $rate);

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
     */
    public static function migrate($object_type, $old_object_id, $new_object_id): void
    {
        $sql = "UPDATE IGNORE `rating` SET `object_id` = ? WHERE `object_type` = ? AND `object_id` = ?";

        Dba::write($sql, [$new_object_id, $object_type, $old_object_id]);
    }

    /**
     * @deprecated inject dependency
     */
    private function getUserActivityPoster(): UserActivityPosterInterface
    {
        global $dic;

        return $dic->get(UserActivityPosterInterface::class);
    }
}
