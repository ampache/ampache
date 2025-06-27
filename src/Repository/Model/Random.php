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

use Ampache\Config\AmpConfig;
use Ampache\Module\Playback\Stream;
use Ampache\Module\Playback\Stream_Url;
use Ampache\Module\System\Core;
use Ampache\Module\System\Dba;
use Ampache\Repository\SongRepositoryInterface;

/**
 * Random Class
 *
 * All of the 'random' type events, elements
 */
class Random
{
    public const VALID_TYPES = [
        'album',
        'artist',
        'song',
        'video',
    ];

    /**
     * artist
     * This returns the ID of a random artist, nothing special here for now
     */
    public static function artist(): int
    {
        $catalog_filter = (AmpConfig::get('catalog_disable') || AmpConfig::get('catalog_filter'));
        $user_id        = Core::get_global('user')?->getId() ?? -1;
        $sql            = ($catalog_filter)
            ? "SELECT `artist`.`id` FROM `artist` LEFT JOIN `catalog_map` ON `catalog_map`.`object_type` = 'artist' AND `catalog_map`.`object_id` = `artist`.`id` WHERE `catalog_map`.`catalog_id` IN (" . implode(',', Catalog::get_catalogs('', $user_id, true)) . ") "
            : "SELECT `artist`.`id` FROM `artist` ";

        $rating_filter = AmpConfig::get_rating_filter();
        if ($rating_filter > 0 && $rating_filter <= 5 && $user_id) {
            $sql .= ($catalog_filter)
                ? sprintf('AND `artist`.`id` NOT IN (SELECT `object_id` FROM `rating` WHERE `rating`.`object_type` = \'artist\' AND `rating`.`rating` <=%d AND `rating`.`user` = %d) ', $rating_filter, $user_id)
                : sprintf('WHERE `artist`.`id` NOT IN (SELECT `object_id` FROM `rating` WHERE `rating`.`object_type` = \'artist\' AND `rating`.`rating` <=%d AND `rating`.`user` = %d) ', $rating_filter, $user_id);
        }

        $sql .= "GROUP BY `artist`.`id` ORDER BY RAND() LIMIT 1;";

        $db_results = Dba::read($sql);
        $results    = Dba::fetch_assoc($db_results);

        return (int)$results['id'];
    }

    /**
     * playlist
     * This returns a random Playlist with songs little bit of extra
     * logic require
     */
    public static function playlist(): int
    {
        $sql = "SELECT `playlist`.`id` FROM `playlist` LEFT JOIN `playlist_data` ON `playlist`.`id`=`playlist_data`.`playlist` WHERE `playlist_data`.`object_id` IS NOT NULL ORDER BY RAND()";

        $db_results = Dba::read($sql);
        $results    = Dba::fetch_assoc($db_results);

        return (int)$results['id'];
    }

    /**
     * get_single_song
     * This returns a single song pulled based on the passed random method
     */
    public static function get_single_song(string $random_type, User $user, int $object_id = 0): int
    {
        $song_ids = match ($random_type) {
            'artist' => self::get_artist(1, $user),
            'playlist' => self::get_playlist($user, $object_id),
            'search' => self::get_search($user, $object_id),
            default => self::get_default(1, $user),
        };
        $song = array_pop($song_ids);
        //debug_event(self::class, "get_single_song:" . $song, 5);

        return (int)$song;
    }

    /**
     * get_default
     * This just randomly picks a song at whim from all catalogs
     * @return int[]
     */
    public static function get_default(int $limit, ?User $user = null): array
    {
        $results = [];

        if (empty($user)) {
            $user = Core::get_global('user');
        }

        $user_id   = $user?->getId();
        $sql       = "SELECT `song`.`id` FROM `song` ";
        $where_sql = (AmpConfig::get('catalog_disable') || AmpConfig::get('catalog_filter'))
            ? "WHERE `song`.`catalog` IN (" . implode(',', Catalog::get_catalogs('', $user_id, true)) . ") "
            : "";

        $rating_filter = AmpConfig::get_rating_filter();
        if ($rating_filter > 0 && $rating_filter <= 5 && $user_id !== null) {
            $where_sql .= ($where_sql == "")
                ? sprintf('WHERE `song`.`artist` NOT IN (SELECT `object_id` FROM `rating` WHERE `rating`.`object_type` = \'artist\' AND `rating`.`rating` <=%d AND `rating`.`user` = %d)', $rating_filter, $user_id)
                : sprintf('AND `song`.`artist` NOT IN (SELECT `object_id` FROM `rating` WHERE `rating`.`object_type` = \'artist\' AND `rating`.`rating` <=%d AND `rating`.`user` = %d)', $rating_filter, $user_id);
            $where_sql .= sprintf('AND `song`.`album` NOT IN (SELECT `object_id` FROM `rating` WHERE `rating`.`object_type` = \'album\' AND `rating`.`rating` <=%d AND `rating`.`user` = %d)', $rating_filter, $user_id);
        }

        $sql .= sprintf('%s ORDER BY RAND() LIMIT %d', $where_sql, $limit);
        $db_results = Dba::read($sql);
        //debug_event(self::class, "get_default " . $sql, 5);

        while ($row = Dba::fetch_assoc($db_results)) {
            $results[] = (int)$row['id'];
        }

        return $results;
    }

    /**
     * get_artist
     * This looks at the last artist played and then randomly picks a song from the
     * same artist
     * @return int[]
     */
    public static function get_artist(int $limit, ?User $user = null): array
    {
        $results = [];

        if (empty($user)) {
            $user = Core::get_global('user');
        }

        if (!$user instanceof User) {
            return [];
        }

        $sql       = "SELECT `song`.`id` FROM `song` ";
        $user_id   = $user->id;
        $data      = $user->get_recently_played('artist', 1);
        $where_sql = ($data[0])
            ? "WHERE `song`.`artist`='" . $data[0] . "' "
            : "";

        if (AmpConfig::get('catalog_disable') || AmpConfig::get('catalog_filter')) {
            $where_sql .= ($where_sql == "")
                ? "WHERE `song`.`catalog` IN (" . implode(',', Catalog::get_catalogs('', $user_id, true)) . ") "
                : "AND `song`.`catalog` IN (" . implode(',', Catalog::get_catalogs('', $user_id, true)) . ") ";
        }

        $rating_filter = AmpConfig::get_rating_filter();
        if ($rating_filter > 0 && $rating_filter <= 5) {
            $where_sql .= ($where_sql == "")
                ? sprintf('WHERE `song`.`artist` NOT IN (SELECT `object_id` FROM `rating` WHERE `rating`.`object_type` = \'artist\' AND `rating`.`rating` <=%d AND `rating`.`user` = %d) ', $rating_filter, $user_id)
                : sprintf('AND `song`.`artist` NOT IN (SELECT `object_id` FROM `rating` WHERE `rating`.`object_type` = \'artist\' AND `rating`.`rating` <=%d AND `rating`.`user` = %d) ', $rating_filter, $user_id);
        }

        $sql .= sprintf('%s ORDER BY RAND() LIMIT %d', $where_sql, $limit);
        $db_results = Dba::read($sql);

        while ($row = Dba::fetch_assoc($db_results)) {
            $results[] = (int)$row['id'];
        }

        return $results;
    }

    /**
     * get_playlist
     * Get a random song from a playlist (that you own)
     * @return int[]
     */
    public static function get_playlist(User $user, int $playlist_id = 0): array
    {
        $results  = [];
        $playlist = new Playlist($playlist_id);
        if (
            $playlist->isNew() === false &&
            (
                $playlist->type === 'public' ||
                $playlist->has_collaborate($user)
            )
        ) {
            foreach ($playlist->get_random_items('1') as $songs) {
                $results[] = $songs['object_id'];
            }
        }

        return $results;
    }

    /**
     * get_search
     * Get a random song from a search (that you own)
     * @return int[]
     */
    public static function get_search(User $user, int $search_id = 0): array
    {
        $results = [];
        $search  = new Search($search_id, 'song', $user);
        if (
            $search->isNew() === false &&
            (
                $search->type === 'public' ||
                $search->has_access($user)
            )
        ) {
            foreach ($search->get_random_items('1') as $songs) {
                $results[] = (int)$songs['object_id'];
            }
        }

        return $results;
    }

    /**
     * advanced
     * This processes the results of a post from a form and returns an
     * array of song items that were returned from said randomness
     * @param string $type
     * @param array<string, mixed> $data
     * @return int[]
     */
    public static function advanced(string $type, array $data): array
    {
        /* Figure out our object limit */
        $limit     = (int)($data['limit'] ?? -1);
        $limit_sql = "LIMIT " . Dba::escape($limit);

        /* If they've passed -1 as limit then get everything */
        if ($limit == -1) {
            if (array_key_exists('limit', $data)) {
                unset($data['limit']);
            }

            $limit_sql = "";
        }

        $search  = self::advanced_sql($data, $type, $limit_sql);
        $results = self::advanced_results($search['sql'], $search['parameters'], $data);
        //debug_event(self::class, 'advanced ' . print_r($search, true), 5);

        return self::get_songs($type, $results);
    }

    /**
     * get_songs
     * This processes the results of a post from a form and returns an
     * array of song items that were returned from said randomness
     * @param string $type
     * @param int[] $results
     * @return int[]
     */
    public static function get_songs(string $type, array $results): array
    {
        switch ($type) {
            case 'song':
            case 'video':
                return $results;
            case 'album':
                $songs = [];
                foreach ($results as $object_id) {
                    $songs = array_merge($songs, self::getSongRepository()->getByAlbum($object_id));
                }

                return $songs;
            case 'artist':
                $songs = [];
                foreach ($results as $object_id) {
                    $songs = array_merge($songs, self::getSongRepository()->getByArtist($object_id));
                }

                return $songs;
            default:
                return [];
        }
    }

    /**
     * advanced_results
     * Run the query generated above by self::advanced so we can while it
     * @param string $sql_query
     * @param array<int, mixed> $sql_params
     * @param array<string, mixed> $data
     * @return int[]
     */
    private static function advanced_results(string $sql_query, array $sql_params, array $data): array
    {
        // Run the query generated above so we can while it
        $db_results = Dba::read($sql_query, $sql_params);
        $results    = [];

        $size_total = 0;
        $fuzzy_size = 0;
        $time_total = 0;
        $fuzzy_time = 0;
        $size_limit = (array_key_exists('size_limit', $data) && $data['size_limit'] > 0);
        $length     = (array_key_exists('length', $data) && $data['length'] > 0);
        while ($row = Dba::fetch_assoc($db_results)) {
            // If size limit is specified
            if ($size_limit) {
                // Convert
                $new_size = ($row['size'] / 1024) / 1024;

                // Only fuzzy 100 times
                if ($fuzzy_size > 100) {
                    break;
                }

                // Add and check, skip if over size
                if (($size_total + $new_size) > $data['size_limit']) {
                    ++$fuzzy_size;
                    continue;
                }

                $size_total += $new_size;
                $results[] = (int)$row['id'];

                // If we are within 4mb of target then jump ship
                if (($data['size_limit'] - floor($size_total)) < 4) {
                    break;
                }
            } // if size_limit

            // If length really does matter
            if ($length) {
                // base on min, seconds are for chumps and chumpettes
                $new_time = floor($row['time'] / 60);

                if ($fuzzy_time > 100) {
                    break;
                }

                // If the new one would go over skip!
                if (($time_total + $new_time) > $data['length']) {
                    ++$fuzzy_time;
                    continue;
                }

                $time_total += $new_time;
                $results[] = (int)$row['id'];

                // If there are less then 2 min of free space return
                if (($data['length'] - $time_total) < 2) {
                    return $results;
                }
            } // if length does matter

            if (!$size_limit && !$length) {
                $results[] = (int)$row['id'];
            }
        }

        return $results;
    }

    /**
     * advanced_sql
     * Generate the sql query for self::advanced
     * @param array<string, mixed> $data
     * @param string $type
     * @param string $limit_sql
     * @return array{
     *     sql: string,
     *     parameters: array<int, mixed>
     * }
     */
    private static function advanced_sql(array $data, string $type, string $limit_sql): array
    {
        $search = new Search(0, $type);
        $search->set_rules($data);

        $search_info     = $search->to_sql();
        $catalog_disable = AmpConfig::get('catalog_disable');

        $catalog_disable_sql = "";
        if ($catalog_disable) {
            $catalog_disable_sql = "LEFT JOIN `catalog` ON `catalog`.`id` = `song`.`catalog` WHERE `catalog`.`enabled` = '1'";
        }

        $sql = "";
        switch ($type) {
            case 'video':
            case 'song':
                $sql = sprintf('SELECT `%s`.`id`, `%s`.`size`, `%s`.`time` FROM `%s` ', $type, $type, $type, $type);
                if (!empty($search_info['table_sql'])) {
                    $sql .= $search_info['table_sql'];
                }

                $sql .= $catalog_disable_sql;
                if (!empty($search_info['where_sql'])) {
                    $sql .= ($catalog_disable)
                        ? " AND " . $search_info['where_sql']
                        : " WHERE " . $search_info['where_sql'];
                }
                break;
            case 'album':
            case 'artist':
                $sql = sprintf('SELECT `%s`.`id`, SUM(`song`.`size`) AS `size`, SUM(`%s`.`time`) AS `time` FROM `%s` ', $type, $type, $type);
                if (!array_key_exists('join', $search_info) || !array_key_exists('song', $search_info['join'])) {
                    $sql .= sprintf('LEFT JOIN `song` ON `song`.`%s`=`%s`.`id` ', $type, $type);
                }

                if (!empty($search_info['table_sql'])) {
                    $sql .= $search_info['table_sql'];
                }

                $sql .= $catalog_disable_sql;
                if (!empty($search_info['where_sql'])) {
                    $sql .= ($catalog_disable)
                        ? " AND " . $search_info['where_sql']
                        : " WHERE " . $search_info['where_sql'];
                }

                $sql .= sprintf(' GROUP BY `%s`.`id`', $type);
                break;
        }

        $sql .= ' ORDER BY RAND() ' . $limit_sql;

        return [
            'sql' => $sql,
            'parameters' => $search_info['parameters'],
        ];
    }

    /**
     * get_play_url
     * This returns the special play URL for random play
     */
    public static function get_play_url(string $object_type, int $object_id): string
    {
        $user = Core::get_global('user');
        $link = Stream::get_base_url(false, $user?->streamtoken) . 'uid=' . scrub_out((string)($user?->id ?? '')) . '&random=1&random_type=' . scrub_out($object_type) . '&random_id=' . scrub_out((string)$object_id);

        return Stream_Url::format($link);
    }

    /**
     * @deprecated
     */
    private static function getSongRepository(): SongRepositoryInterface
    {
        global $dic;

        return $dic->get(SongRepositoryInterface::class);
    }
}
