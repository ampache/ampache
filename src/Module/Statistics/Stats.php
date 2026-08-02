<?php

declare(strict_types=1);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2026
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

namespace Ampache\Module\Statistics;

use Ampache\Config\AmpConfig;
use Ampache\Module\Authorization\Access;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Module\Catalog\Catalog;
use Ampache\Module\System\Core;
use Ampache\Module\System\Dba;
use Ampache\Module\System\LegacyLogger;
use Ampache\Module\User\Activity\UserActivityPosterInterface;
use Ampache\Repository\AlbumRepositoryInterface;
use Ampache\Repository\ArtistRepositoryInterface;
use Ampache\Repository\Model\Podcast_Episode;
use Ampache\Repository\Model\Song;
use Ampache\Repository\Model\User;
use Ampache\Repository\Model\Video;
use Ampache\Repository\PodcastEpisodeRepositoryInterface;
use Ampache\Repository\PodcastRepositoryInterface;
use Ampache\Repository\SongRepositoryInterface;
use Ampache\Repository\UserActivityRepositoryInterface;
use Ampache\Repository\VideoRepositoryInterface;
use PDOStatement;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * Stats Class
 *
 * this class handles the object_count
 * stuff, before this was done in the user class
 * but that's not good, all done through here.
 *
 */
final class Stats
{
    /**
     * Tables carrying a `weight` column; playing, rating and flagging bump it, other types must not be handed to it.
     */
    public const array WEIGHT_TYPES = ['album', 'album_disk', 'artist', 'podcast', 'podcast_episode', 'song', 'video'];
    /**
     * Types written by the Song/Podcast_Episode::set_played fan-out. They duplicate the date, user, agent and
     * location of the media row that triggered them, so consolidation drops them instead of archiving them and
     * Stats::restore() rebuilds them from the archived media rows.
     */
    private const array DERIVED_TYPES = ['album', 'album_disk', 'artist', 'podcast'];

    public ?string $agent = null;
    public int $date;

    /* Base vars */
    public int $id = 0;
    public int $object_id;
    public ?string $object_type = null;
    public int $user;

    public function __construct(
        private AlbumRepositoryInterface $albumRepository,
        private ArtistRepositoryInterface $artistRepository,
        private PodcastEpisodeRepositoryInterface $podcastEpisodeRepository,
        private PodcastRepositoryInterface $podcastRepository,
        private SongRepositoryInterface $songRepository,
        private UserActivityPosterInterface $userActivityPoster,
        private UserActivityRepositoryInterface $userActivityRepository,
        private VideoRepositoryInterface $videoRepository,
        private LoggerInterface $logger,
    ) {}

    /**
     * consolidate
     *
     * Consolidate play history older than $older_than days into `object_count_summary` and delete the detail rows,
     * inside a transaction.
     *
     * Stored counters stay exact: the rebuild queries (Catalog::update_counts, Album/Artist::update_table_counts,
     * Video::update_video_counts, Stats::clear) combine both tables, all-time readers (Stats::get_object_count,
     * User::get_play_size, rating match plugin) include the summary table, and the cron cache (ObjectCache) merges
     * consolidated counts into its threshold 0 entries.
     *
     * Readers that inspect individual plays only evaluate the retained window: period-based statistics (trending,
     * recent, graphs, Last.fm export), live all-time top charts (Stats::get_top_sql with a 0 threshold without
     * cron_cache), smart playlist play-history rules and play count sorting.
     *
     * Nothing is lost: every detail row except the DERIVED_TYPES is copied to `object_count_archive` first, and the
     * derived rows are rebuilt from the archived media rows by Stats::restore().
     *
     * @return array{rows: int, groups: int, executed: bool}
     */
    public static function consolidate(int $older_than, ?string $count_type = null, bool $dry_run = true): array
    {
        $threshold = time() - ($older_than * 86400);
        $where     = "`date` < ? AND `count_type` IS NOT NULL";
        $params    = [$threshold];
        if ($count_type !== null) {
            $where .= " AND `count_type` = ?";
            $params[] = $count_type;
        }

        $db_results = Dba::read("SELECT COUNT(*) AS `rows`, COUNT(DISTINCT CONCAT_WS('|', `object_type`, `object_id`, `user`, `count_type`)) AS `groups` FROM `object_count` WHERE " . $where . ";", $params);
        $row        = Dba::fetch_assoc($db_results);
        $rows       = (int) ($row['rows'] ?? 0);
        $groups     = (int) ($row['groups'] ?? 0);

        if ($dry_run || $rows === 0) {
            return ['rows' => $rows, 'groups' => $groups, 'executed' => false];
        }

        // archive, aggregate then purge inside a transaction so an interruption cannot double count rows on the
        // next run. VALUES() is deprecated on MySQL 8 but its replacement isn't in MariaDB (as in Playlist::update_map)
        $dbh            = Dba::dbh();
        $in_transaction = ($dbh !== null && $dbh->beginTransaction());
        $archive        = Dba::write("INSERT INTO `object_count_archive` (`object_type`, `object_id`, `date`, `user`, `agent`, `geo_latitude`, `geo_longitude`, `geo_name`, `count_type`) SELECT `object_type`, `object_id`, `date`, `user`, `agent`, `geo_latitude`, `geo_longitude`, `geo_name`, `count_type` FROM `object_count` WHERE " . $where . " AND `object_type` NOT IN (" . self::derivedTypeList() . ");", $params);
        $insert         = ($archive !== null)
            ? Dba::write("INSERT INTO `object_count_summary` (`object_type`, `object_id`, `user`, `count_type`, `count`, `date_from`, `date_to`) SELECT `object_type`, `object_id`, `user`, `count_type`, COUNT(*), MIN(`date`), MAX(`date`) FROM `object_count` WHERE " . $where . " GROUP BY `object_type`, `object_id`, `user`, `count_type` ON DUPLICATE KEY UPDATE `count` = `object_count_summary`.`count` + VALUES(`count`), `date_from` = LEAST(`object_count_summary`.`date_from`, VALUES(`date_from`)), `date_to` = GREATEST(`object_count_summary`.`date_to`, VALUES(`date_to`));", $params)
            : null;
        $delete = ($insert !== null)
            ? Dba::write("DELETE FROM `object_count` WHERE " . $where . ";", $params)
            : null;
        if ($archive === null || $insert === null || $delete === null) {
            if ($in_transaction && $dbh->inTransaction()) {
                $dbh->rollBack();
            }

            throw new RuntimeException('Stats consolidation failed and was rolled back');
        }

        if ($in_transaction && $dbh->inTransaction()) {
            $dbh->commit();
        }

        return ['rows' => $rows, 'groups' => $groups, 'executed' => true];
    }

    /**
     * update the play_count for an object
     *
     * `$date` rides along so `last_played` stays current; it is ignored when a play is taken back.
     */
    public static function count(string $type, int $object_id, string $count_type, ?int $date = null): void
    {
        // 'down' turns a play into a skip; 'remove' takes the play back without recording one
        $takesAPlayBack = ($count_type === 'down' || $count_type === 'remove');
        $skip           = ($count_type === 'down') ? ', `total_skip` = `total_skip` + 1' : '';
        $played         = ($takesAPlayBack)
            ? ''
            : sprintf(', `last_played` = GREATEST(COALESCE(`last_played`, 0), %d)', $date ?? time());

        switch ($type) {
            case 'podcast_episode':
            case 'song':
            case 'video':
                $sql = ($takesAPlayBack)
                    ? "UPDATE `$type` SET `weight` = `weight` - 1, `total_count` = CASE WHEN `total_count` > 0 THEN `total_count` - 1 ELSE `total_count` END" . $skip . " WHERE `id` = ?;"
                    : "UPDATE `$type` SET `total_count` = `total_count` + 1, `weight` = `weight` + 1" . $played . " WHERE `id` = ?;";
                Dba::write($sql, [$object_id]);
                // update the folder the object lives in AND every ancestor folder
                $folder_ids = self::getFolderTree($type, $object_id);
                if ($folder_ids !== []) {
                    $idlist = implode(', ', $folder_ids);
                    $sql    = ($takesAPlayBack)
                        ? "UPDATE `folder` SET `total_count` = CASE WHEN `total_count` > 0 THEN `total_count` - 1 ELSE `total_count` END" . $skip . " WHERE `id` IN ($idlist);"
                        : "UPDATE `folder` SET `total_count` = `total_count` + 1 WHERE `id` IN ($idlist);";
                    Dba::write($sql);
                }

                break;
            case 'album_disk':
            case 'album':
            case 'artist':
            case 'podcast':
                $sql = ($takesAPlayBack)
                    ? sprintf('UPDATE `%s` SET `weight` = `weight` - 1, `total_count` = CASE WHEN `total_count` > 0 THEN `total_count` - 1 ELSE `total_count` END%s WHERE `id` = ?;', $type, $skip)
                    : sprintf('UPDATE `%s` SET `total_count` = `total_count` + 1, `weight` = `weight` + 1%s WHERE `id` = ?;', $type, $played);
                Dba::write($sql, [$object_id]);
                break;
        }

        if (
            $takesAPlayBack
            && in_array($type, ['song', 'podcast_episode', 'video'], true)
        ) {
            $sql = sprintf('UPDATE `%s` SET `played` = 0 WHERE `id` = ? AND `total_count` = 0 and `played` = 1;', $type);
            Dba::write($sql, [$object_id]);
        }
    }

    /**
     * Delete a user activity in object_count, find related objects and reduce counts for parent/child objects
     */
    public static function delete(int $activity_id): void
    {
        if ($activity_id > 0) {
            $sql        = "SELECT `object_count`.`object_id`, `object_count`.`object_type`, `object_count`.`date`, `object_count`.`user`, `object_count`.`agent`, `object_count`.`count_type` FROM `object_count` WHERE `object_count`.`id` = ?;";
            $db_results = Dba::read($sql, [$activity_id]);
            if ($row = Dba::fetch_assoc($db_results)) {
                $params     = [$row['date'], $row['user'], $row['agent'], $row['count_type']];
                $sql        = "SELECT `object_id`, `object_type` FROM `object_count` WHERE `object_count`.`date` = ? AND `object_count`.`user` = ? AND `object_count`.`agent` = ? AND `object_count`.`count_type` = ? AND `count_type` = 'stream'";
                $db_results = Dba::read($sql, $params);
                while ($row = Dba::fetch_assoc($db_results)) {
                    // reduce the counts for these objects too
                    if (in_array($row['object_type'], ['song', 'album', 'artist', 'video', 'podcast', 'podcast_episode'])) {
                        self::count($row['object_type'], (int) $row['object_id'], 'remove');
                    }
                }

                // delete the row and all related activities
                $sql = "DELETE FROM `object_count` WHERE `object_count`.`date` = ? AND `object_count`.`user` = ? AND `object_count`.`agent` = ? AND `object_count`.`count_type` = ?";
                Dba::write($sql, $params);
            }
        }
    }

    /**
     * When deleting an artist_map, remove the stat rows too
     */
    public static function delete_map(string $source_type, int $source_id, string $dest_type, int $dest_id): void
    {
        if ($source_id > 0 && $dest_id > 0) {
            debug_event(self::class, "delete_map " . $source_type . " {" . $source_id . "} => " . $dest_type . " {" . $dest_id . "}", 5);
            $sql        = "SELECT `object_count`.`date`, `object_count`.`user`, `object_count`.`agent`, `object_count`.`geo_latitude`, `object_count`.`geo_longitude`, `object_count`.`geo_name`, `object_count`.`count_type` FROM `object_count` WHERE `object_count`.`count_type` = 'stream' AND `object_count`.`object_type` = ? AND `object_count`.`object_id` = ?;";
            $db_results = Dba::read($sql, [$source_type, $source_id]);
            while ($row = Dba::fetch_assoc($db_results)) {
                $sql = "DELETE FROM `object_count` WHERE `object_count`.`object_type` = ? AND `object_count`.`object_id` = ? AND `object_count`.`date` = ? AND `object_count`.`user` = ? AND `object_count`.`agent` = ? AND `object_count`.`geo_latitude` = ? AND `object_count`.`geo_longitude` = ? AND `object_count`.`geo_name` = ? AND `object_count`.`count_type` = ?";
                Dba::write($sql, [$dest_type, $dest_id, $row['date'], $row['user'], $row['agent'], $row['geo_latitude'], $row['geo_longitude'], $row['geo_name'], $row['count_type']]);
            }
        }
    }

    /**
     * When creating an artist_map, duplicate the stat rows
     */
    public static function duplicate_map(string $source_type, int $source_id, string $dest_type, int $dest_id): void
    {
        if ($source_id > 0 && $dest_id > 0) {
            debug_event(self::class, "duplicate_map " . $source_type . " {" . $source_id . "} => " . $dest_type . " {" . $dest_id . "}", 5);
            $sql        = "SELECT `object_count`.`date`, `object_count`.`user`, `object_count`.`agent`, `object_count`.`geo_latitude`, `object_count`.`geo_longitude`, `object_count`.`geo_name`, `object_count`.`count_type` FROM `object_count` WHERE `object_count`.`count_type` = 'stream' AND `object_count`.`object_type` = ? AND `object_count`.`object_id` = ?;";
            $db_results = Dba::read($sql, [$source_type, $source_id]);
            while ($row = Dba::fetch_assoc($db_results)) {
                $sql = "INSERT IGNORE INTO `object_count` (`object_type`, `object_id`, `count_type`, `date`, `user`, `agent`, `geo_latitude`, `geo_longitude`, `geo_name`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
                Dba::write($sql, [$dest_type, $dest_id, $row['count_type'], $row['date'], $row['user'], $row['agent'], $row['geo_latitude'], $row['geo_longitude'], $row['geo_name']]);
            }
        }
    }

    /**
     * garbage_collection
     *
     * This removes stats for things that no longer exist.
     */
    public static function garbage_collection(): void
    {
        foreach (['album', 'artist', 'song', 'playlist', 'tag', 'live_stream', 'video', 'podcast', 'podcast_episode'] as $object_type) {
            Dba::write(sprintf("DELETE FROM `object_count` WHERE `object_type` = '%s' AND `object_count`.`object_id` NOT IN (SELECT `%s`.`id` FROM `%s`);", $object_type, $object_type, $object_type));
            Dba::write(sprintf("DELETE FROM `object_count_summary` WHERE `object_type` = '%s' AND `object_count_summary`.`object_id` NOT IN (SELECT `%s`.`id` FROM `%s`);", $object_type, $object_type, $object_type));
            Dba::write(sprintf("DELETE FROM `object_count_archive` WHERE `object_type` = '%s' AND `object_count_archive`.`object_id` NOT IN (SELECT `%s`.`id` FROM `%s`);", $object_type, $object_type, $object_type));
        }

        // if deletes are copmleted you can have left over stuff
        Dba::write("DELETE FROM `object_count` WHERE `object_type` IN ('album', 'artist', 'podcast') AND `count_type` = ('skip');");
        Dba::write("DELETE FROM `object_count_summary` WHERE `object_type` IN ('album', 'artist', 'podcast') AND `count_type` = 'skip';");
    }

    /**
     * get_cached_place_name
     */
    public static function get_cached_place_name(float $latitude, float $longitude): ?string
    {
        $name       = null;
        $sql        = "SELECT `geo_name` FROM `object_count` WHERE `geo_latitude` = ? AND `geo_longitude` = ? AND `geo_name` IS NOT NULL ORDER BY `id` DESC LIMIT 1";
        $db_results = Dba::read($sql, [$latitude, $longitude]);
        $results    = Dba::fetch_assoc($db_results);
        if ($results !== []) {
            $name = $results['geo_name'];
        }

        return $name;
    }

    /**
     * get_last_play
     * This returns the full data for the last song/video/podcast_episode that was played, including when it
     * was played, this is used by, among other things, the LastFM plugin to figure out
     * if we should re-submit or if this is a duplicate / if it's too soon. This takes an
     * optional user_id because when streaming we don't have $GLOBALS()
     * @return array{id: int, object_type: ?string, object_id: ?int, user: int, agent: string, date: int, count_type: string}
     */
    public static function get_last_play(int $user_id = 0, string $agent = '', int $date = 0): array
    {
        if ($user_id === 0) {
            $user    = Core::get_global('user');
            $user_id = $user->id ?? 0;
        }

        if ($user_id === 0) {
            return [
                'id' => 0,
                'object_type' => null,
                'object_id' => null,
                'user' => 0,
                'agent' => '',
                'date' => 0,
                'count_type' => ''
            ];
        }

        $sql    = "SELECT `object_count`.`id`, `object_count`.`object_type`, `object_count`.`object_id`, `object_count`.`user`, `object_count`.`agent`, `object_count`.`date`, `object_count`.`count_type` FROM `object_count` WHERE `object_count`.`user` = ? AND `object_count`.`object_type` IN ('song', 'video', 'podcast_episode') AND `object_count`.`count_type` IN ('stream', 'skip') ";
        $params = [$user_id];
        if ($agent !== '' && $agent !== '0') {
            $sql .= "AND `object_count`.`agent` = ? ";
            $params[] = $agent;
        }

        if ($date > 0) {
            $sql .= "AND `object_count`.`date` <= ? ";
            $params[] = $date;
        }

        $sql .= "ORDER BY `object_count`.`date` DESC LIMIT 1";
        $db_results = Dba::read($sql, $params);
        $row        = Dba::fetch_assoc($db_results);

        return [
            'id' => $row['id'] ?? 0,
            'object_type' => $row['object_type'] ?? null,
            'object_id' => $row['object_id'] ?? null,
            'user' => $row['user'] ?? 0,
            'agent' => $row['agent'] ?? '',
            'date' => $row['date'] ?? 0,
            'count_type' => $row['count_type'] ?? ''
        ];
    }

    /**
     * get_newest
     * This returns an array of the newest artists/albums/whatever in this Ampache instance
     * @return int[]
     */
    public static function get_newest(
        string $input_type,
        int $count = 0,
        int $offset = 0,
        int $catalog_id = 0,
        ?User $user = null,
    ): array {
        if ($count === 0) {
            $count = AmpConfig::get('popular_threshold', 10);
        }

        if ($count === -1) {
            $count  = 0;
            $offset = 0;
        }

        $sql   = self::get_newest_sql($input_type, $catalog_id, $user);
        $limit = ($offset < 1)
            ? $count
            : $offset . "," . $count;
        if ($limit > 0) {
            $sql .= 'LIMIT ' . $limit;
        }

        //debug_event(self::class, 'get_newest ' . $sql, 5);
        $db_results = Dba::read($sql);
        $results    = [];
        while ($row = Dba::fetch_row($db_results)) {
            $results[] = (int) $row[0];
        }

        return $results;
    }

    /**
     * get_newest_sql
     * This returns the get_newest sql
     */
    public static function get_newest_sql(
        string $input_type,
        ?int   $catalog_id = 0,
        ?User  $user = null,
    ): string {
        $type = self::validate_type($input_type);
        // all objects could be filtered
        $catalog_filter = (AmpConfig::get('catalog_filter'));

        // add playlists to mashup browsing
        if ($type === 'playlist') {
            return ($catalog_filter && $user !== null)
                ? "SELECT `playlist`.`id`, MAX(`playlist`.`last_update`) AS `real_atime` FROM `playlist` WHERE" . Catalog::get_user_filter($type, $user->getId()) . "GROUP BY `playlist`.`id` ORDER BY `real_atime` DESC "
                : "SELECT `playlist`.`id`, MAX(`playlist`.`last_update`) AS `real_atime` FROM `playlist` GROUP BY `playlist`.`id` ORDER BY `real_atime` DESC ";
        }

        $base_type      = 'song';
        $group_by       = '';
        $where          = [];
        $catalog_column = null;
        // everything else
        if ($type === 'song') {
            $sql            = "SELECT `song`.`id` AS `id`, `song`.`addition_time` AS `real_atime` FROM `song` ";
            $sql_type       = "`song`.`id`";
            $catalog_column = "`song`.`catalog`";
        } elseif ($type === 'album') {
            $base_type      = 'album';
            $sql            = "SELECT `album`.`id` AS `id`, `album`.`addition_time` AS `real_atime` FROM `album` ";
            $sql_type       = "`album`.`id`";
            $catalog_column = "`album`.`catalog`";
        } elseif ($type === 'album_disk') {
            $base_type      = 'album';
            $sql            = "SELECT `album_disk`.`id` AS `id`, `album`.`addition_time` AS `real_atime` FROM `album_disk` LEFT JOIN `album` ON `album`.`id` = `album_disk`.`album_id` ";
            $sql_type       = "`album_disk`.`id`";
            $catalog_column = "`album_disk`.`catalog`";
        } elseif ($type === 'video') {
            $base_type      = 'video';
            $sql            = "SELECT `video`.`id` AS `id`, `video`.`addition_time` AS `real_atime` FROM `video` ";
            $sql_type       = "`video`.`id`";
            $catalog_column = "`video`.`catalog`";
        } elseif ($input_type === 'artist') {
            $base_type = 'artist';
            $sql       = "SELECT `artist`.`id` AS `id`, `artist`.`addition_time` AS `real_atime` FROM `artist` ";
            $sql_type  = '`artist`.`id`';
        } elseif ($input_type === 'song_artist' || $input_type === 'album_artist') {
            $base_type = 'artist';
            $sql       = "SELECT `artist`.`id` AS `id`, `artist`.`addition_time` AS `real_atime` FROM `artist` ";
            $sql_type  = '`artist`.`id`';
            $type      = 'artist';
            $map_type  = ($input_type === 'song_artist') ? 'song' : 'album';
            $where[]   = "EXISTS (SELECT 1 FROM `artist_map` WHERE `artist_map`.`artist_id` = `artist`.`id` AND `artist_map`.`object_type` = '" . $map_type . "')";
        } elseif ($type === 'podcast') {
            $base_type      = 'podcast';
            $sql            = "SELECT `podcast`.`id` AS `id`, `podcast`.`lastsync` AS `real_atime` FROM `podcast` ";
            $sql_type       = "`podcast`.`id`";
            $catalog_column = "`podcast`.`catalog`";
        } elseif ($type === 'podcast_episode') {
            $base_type      = 'podcast_episode';
            $sql            = "SELECT `podcast_episode`.`id` AS `id`, `podcast_episode`.`addition_time` AS `real_atime` FROM `podcast_episode` ";
            $sql_type       = "`podcast_episode`.`id`";
            $catalog_column = "`podcast_episode`.`catalog`";
        } else {
            // what else? this one really does aggregate, so it keeps the map join and the grouping
            $sql      = sprintf('SELECT MIN(`%s`) AS `id`, MIN(`song`.`addition_time`) AS `real_atime` FROM `%s` ', $type, $base_type);
            $sql_type = "`song`.`" . $type . "`";
            $sql .= "LEFT JOIN `catalog_map` ON `catalog_map`.`object_id` = " . $sql_type . " AND `catalog_map`.`object_type` = '" . $base_type . "' ";
            $group_by       = sprintf('GROUP BY %s ', $sql_type);
            $catalog_column = "`catalog_map`.`catalog_id`";
        }

        $catalogs = ((int) $catalog_id !== 0)
            ? [(int) $catalog_id]
            : Catalog::get_catalogs('', $user?->getId(), true);
        if ($catalogs === []) {
            $where[] = '1 = 0';
        } elseif ($catalog_column === null) {
            $where[] = "EXISTS (SELECT 1 FROM `catalog_map` WHERE `catalog_map`.`object_id` = " . $sql_type . " AND `catalog_map`.`object_type` = '" . $base_type . "' AND `catalog_map`.`catalog_id` IN (" . implode(',', $catalogs) . "))";
        } else {
            $where[] = $catalog_column . " IN (" . implode(',', array_diff($catalogs, [0])) . ")";
        }

        $rating_filter = AmpConfig::get_rating_filter();
        $user_id       = (int) (Core::get_global('user')?->getId());
        if ($rating_filter > 0 && $rating_filter <= 5 && $user_id > 0) {
            $where[] = $sql_type . " NOT IN (SELECT `object_id` FROM `rating` WHERE `rating`.`object_type` = '" . $type . "' AND `rating`.`rating` <=" . $rating_filter . " AND `rating`.`user` = " . $user_id . ")";
        }

        $sql .= 'WHERE ' . implode(' AND ', $where) . ' ' . $group_by . 'ORDER BY `real_atime` DESC ';

        //debug_event(self::class, 'get_newest_sql ' . $sql, 5);

        return $sql;
    }

    /**
     * get_object_count
     * Get count for an object
     */
    public static function get_object_count(string $object_type, int $object_id, ?string $threshold = null, string $count_type = 'stream'): int
    {
        if ($threshold === null || $threshold === '') {
            $threshold = 0;
        }

        if (AmpConfig::get('cron_cache')) {
            $sql = "SELECT `count` AS `total_count` FROM `cache_object_count` WHERE `object_type` = ? AND `object_id` = ? AND `count_type` = ? AND `threshold` = " . $threshold;
        } else {
            $sql = "SELECT COUNT(*) AS `total_count` FROM `object_count` WHERE `object_type` = ? AND `object_id` = ? AND `count_type` = ?";
            if ($threshold > 0) {
                $date = time() - (86400 * (int) $threshold);
                $sql .= " AND `date` >= '" . $date . "'";
            }
        }

        $db_results = Dba::read($sql, [$object_type, $object_id, $count_type]);
        $results    = Dba::fetch_assoc($db_results);
        $total      = (int) ($results['total_count'] ?? 0);

        if (AmpConfig::get('cron_cache')) {
            // The cache is only refreshed by the cron task, so all-time counts
            // would lag behind until the next run (see issue #2587 and PR2589).
            // When cron_cache_live_count is enabled, add the plays recorded
            // since the cache was generated. That delta is small so it stays
            // fast; it is opt-in to preserve the existing behaviour on large
            // instances where the extra per-count query is not wanted.
            if ((int) $threshold === 0 && AmpConfig::get('cron_cache_live_count')) {
                $last_cache = Catalog::get_update_info('cache_object_count', 0);
                $sql        = "SELECT COUNT(*) AS `total_count` FROM `object_count` WHERE `object_type` = ? AND `object_id` = ? AND `count_type` = ? AND `date` > ?";
                $db_results = Dba::read($sql, [$object_type, $object_id, $count_type, $last_cache]);
                $results    = Dba::fetch_assoc($db_results);
                $total += (int) ($results['total_count'] ?? 0);
            }
        } elseif ((int) $threshold === 0) {
            // all-time counts must include consolidated history
            $db_results = Dba::read("SELECT COALESCE(SUM(`count`), 0) AS `total_count` FROM `object_count_summary` WHERE `object_type` = ? AND `object_id` = ? AND `count_type` = ?;", [$object_type, $object_id, $count_type]);
            $results    = Dba::fetch_assoc($db_results);
            $total += (int) ($results['total_count'] ?? 0);
        }

        return $total;
    }

    /**
     * get_play_data
     * Get data about object history and play data from object_count
     */
    public static function get_object_data(string $dataType, int $startTime, int $endTime, User $user): string
    {
        $params = [$startTime, $endTime, $user->getId()];
        switch ($dataType) {
            case 'song_count':
                $sql = "SELECT COUNT(`object_id`) AS `data` FROM `object_count` WHERE `date` >= ? AND `date` <= ? AND `user` = ? AND `count_type` = 'stream' AND `object_type` = 'song';";
                break;
            case 'song_minutes':
                $sql = "SELECT ROUND(SUM(`song`.`time`) / 60) AS `data` FROM `object_count` LEFT JOIN `song` ON `song`.`id` = `object_count`.`object_id` AND `object_type` = 'song' WHERE `date` > ? AND `date` < ? AND `user` = ? AND `count_type` = 'stream' AND `object_type` = 'song';";
                break;
            default:
                return '';
        }

        $db_results = Dba::read($sql, $params);
        $results    = Dba::fetch_assoc($db_results);

        return (string) ($results['data'] ?? '');
    }

    /**
     * get_object_history
     * This returns the objects that have happened for $user_id sometime after $time
     * used primarily by the democratic cooldown code
     * @return int[]
     */
    public static function get_object_history(int $time, bool $newest = true): array
    {
        $user_id = Core::get_global('user')?->getId() ?? -1;
        $order   = ($newest) ? 'DESC' : 'ASC';
        $sql     = (AmpConfig::get('catalog_disable') || AmpConfig::get('catalog_filter'))
            ? "SELECT * FROM `object_count` LEFT JOIN `song` ON `song`.`id` = `object_count`.`object_id` LEFT JOIN `catalog` ON `catalog`.`id` = `song`.`catalog` WHERE `object_count`.`user` = ? AND `object_count`.`object_type`='song' AND `object_count`.`date` >= ? AND `song`.`catalog` IN (" . implode(',', Catalog::get_catalogs('', $user_id, true)) . ") ORDER BY `object_count`.`date` " . $order
            : "SELECT * FROM `object_count` LEFT JOIN `song` ON `song`.`id` = `object_count`.`object_id` WHERE `object_count`.`user` = ? AND `object_count`.`object_type`='song' AND `object_count`.`date` >= ? ORDER BY `object_count`.`date` " . $order;
        $db_results = Dba::read($sql, [$user_id, $time]);

        $results = [];
        while ($row = Dba::fetch_assoc($db_results)) {
            $results[] = (int) $row['object_id'];
        }

        return $results;
    }

    /**
     * get_recent
     * This returns the recent X for type Y
     * @return int[]
     */
    public static function get_recent(
        string $input_type,
        int $count = 0,
        int $offset = 0,
        ?User $user = null,
        bool $newest = true,
        int $catalog_id = 0,
    ): array {
        if ($count === 0) {
            $count = AmpConfig::get('popular_threshold', 10);
        }

        if ($count === -1) {
            $count  = 0;
            $offset = 0;
        }

        $sql   = self::get_recent_sql($input_type, $user, $newest, $catalog_id);
        $limit = ($offset < 1)
            ? $count
            : $offset . "," . $count;
        if ($limit > 0) {
            $sql .= ' LIMIT ' . $limit;
        }

        //debug_event(self::class, 'get_recent ' . $sql, 5);
        $db_results = Dba::read($sql);
        $results    = [];
        while ($row = Dba::fetch_assoc($db_results)) {
            $results[] = (int) $row['id'];
        }

        return $results;
    }

    /**
     * get_recent_sql
     * This returns the get_recent sql
     */
    public static function get_recent_sql(string $input_type, ?User $user = null, bool $newest = true, int $catalog_id = 0): string
    {
        $type           = self::validate_type($input_type);
        $ordersql       = ($newest) ? 'DESC' : 'ASC';
        $user_sql       = ($user !== null) ? " AND `object_count`.`user` = '" . $user->getId() . "'" : '';
        $catalog_filter = (AmpConfig::get('catalog_filter'));
        $filter_user    = ($user ?? Core::get_global('user'));

        $sql = "SELECT `object_id` AS `id`, MAX(`date`) AS `date` FROM `object_count` WHERE `object_type` = '" . $type . "' AND `count_type` = 'stream'" . $user_sql;
        if ($input_type === 'album_disk') {
            $sql = "SELECT `album_disk`.`id` AS `id`, MAX(`date`) AS `date` FROM `object_count` LEFT JOIN `album_disk` ON `album_disk`.`album_id` = `object_id` AND `object_type` = 'album' WHERE `object_type` = 'album' AND `count_type` = 'stream'" . $user_sql;
        }

        if ($input_type === 'album_artist') {
            $sql = "SELECT `object_id` AS `id`, MAX(`date`) AS `date` FROM `object_count` LEFT JOIN `artist` ON `artist`.`id` = `object_id` AND `object_type` = 'artist' WHERE `artist`.`album_count` > 0 AND `object_type` = 'artist' AND `count_type` = 'stream'" . $user_sql;
        }

        if (AmpConfig::get('catalog_disable') && in_array($type, ['artist', 'album', 'album_disk', 'song', 'video'], true)) {
            $sql .= " AND " . Catalog::get_enable_filter($type, '`object_id`');
        }

        if ($catalog_filter && in_array($type, ['video', 'artist', 'album_artist', 'album', 'album_disk', 'song'], true) && $filter_user !== null) {
            $sql .= " AND" . Catalog::get_user_filter('object_count_' . $type, $filter_user->getId());
        }

        // album_disk rows are keyed on the joined table, everything else filters the object_count id directly
        $catalog_column = ($input_type === 'album_disk')
            ? '`album_disk`.`id`'
            : '`object_count`.`object_id`';
        $catalog_sql = Catalog::get_catalog_id_filter($input_type, $catalog_column, $catalog_id);
        if ($catalog_sql !== '') {
            $sql .= " AND " . $catalog_sql;
        }

        $rating_filter = AmpConfig::get_rating_filter();
        if ($rating_filter > 0 && $rating_filter <= 5 && $user !== null) {
            $sql .= " AND `object_id` NOT IN (SELECT `object_id` FROM `rating` WHERE `rating`.`object_type` = '" . $type . "' AND `rating`.`rating` <=" . $rating_filter . " AND `rating`.`user` = " . $user->getId() . ")";
        }

        if ($input_type === 'album_disk') {
            $sql .= " GROUP BY `album_disk`.`id` ORDER BY MAX(`date`) " . $ordersql . ", `album_disk`.`id` ";
        } else {
            $sql .= " GROUP BY `object_count`.`object_id` ORDER BY MAX(`date`) " . $ordersql . ", `object_count`.`object_id` ";
        }

        // playlists aren't the same as other objects so change the sql
        if ($type === 'playlist') {
            $sql = "SELECT `id`, `last_update` AS `date` FROM `playlist`";
            if ($user !== null) {
                $sql .= " WHERE `user` = '" . $user->getId() . "'";
                if ($catalog_filter) {
                    $sql .= " AND" . Catalog::get_user_filter($type, $user->getId());
                }
            }

            $sql .= " ORDER BY `last_update` " . $ordersql;
        }

        //debug_event(self::class, 'get_recent_sql ' . $sql, 5);

        return $sql;
    }

    /**
     * get_recently_played
     * This function returns the last X played media objects ('live_stream','podcast_episode','song','video')
     * It uses the popular threshold to figure out how many to pull it will only return unique object
     * @return array<int, array{
     *     object_id: int,
     *     catalog_id: int,
     *     user: int,
     *     object_type: string,
     *     date: int,
     *     agent: string,
     *     geo_latitude: ?float,
     *     geo_longitude: ?float,
     *     geo_name: ?string,
     *     user_recent: int,
     *     user_time: int,
     *     user_agent: int,
     *     activity_id: int
     * }>
     */
    public static function get_recently_played(?int $user_id, string $count_type = 'stream', ?string $object_type = null, bool $user_only = false): array
    {
        $limit         = AmpConfig::get('popular_threshold', 10);
        $geolocation   = AmpConfig::get('geolocation', false);
        $access100     = Access::check(AccessTypeEnum::INTERFACE, AccessLevelEnum::ADMIN);
        $object_string = (in_array($object_type, [null, '', '0'], true) || !in_array($object_type, ['album', 'album_disk', 'artist', 'catalog', 'tag', 'label', 'live_stream', 'playlist', 'podcast', 'podcast_episode', 'search', 'song', 'user', 'video'], true))
            ? "'song', 'live_stream', 'podcast_episode', 'video'"
            : sprintf("'%s'", $object_type);

        $results = [];
        $params  = [];
        $sql     = sprintf("SELECT `object_count`.`object_id`, `catalog_map`.`catalog_id`, `object_count`.`user`, `object_count`.`object_type`, `date`, `agent`, `geo_latitude`, `geo_longitude`, `geo_name`, `pref_recent`.`value` AS `user_recent`, `pref_time`.`value` AS `user_time`, `pref_agent`.`value` AS `user_agent`, `object_count`.`id` AS `activity_id` FROM `object_count` LEFT JOIN `user_preference` AS `pref_recent` ON `pref_recent`.`name`='allow_personal_info_recent' AND `pref_recent`.`user` = `object_count`.`user` AND `pref_recent`.`value`='1' LEFT JOIN `user_preference` AS `pref_time` ON `pref_time`.`name`='allow_personal_info_time' AND `pref_time`.`user` = `object_count`.`user` AND `pref_time`.`value`='1' LEFT JOIN `user_preference` AS `pref_agent` ON `pref_agent`.`name`='allow_personal_info_agent' AND `pref_agent`.`user` = `object_count`.`user` AND `pref_agent`.`value`='1' LEFT JOIN `catalog_map` ON `catalog_map`.`object_type` = `object_count`.`object_type` AND `catalog_map`.`object_id` = `object_count`.`object_id` WHERE `object_count`.`object_type` IN (%s) AND `object_count`.`count_type` = '%s' ", $object_string, $count_type);
        // check for valid catalogs
        $sql .= (AmpConfig::get('catalog_filter'))
            ? "AND `catalog_map`.`catalog_id` IN (" . implode(',', Catalog::get_catalogs('', $user_id, true)) . ") "
            : "";

        if ((int) $user_id > 0 || !$access100) {
            $sql .= ($user_only)
                ? "AND (`object_count`.`user` = ? AND `pref_recent`.`user` IS NOT NULL) "
                : "AND (`object_count`.`user` = ? OR `pref_recent`.`user` IS NOT NULL) ";
            $params[] = $user_id;
        }

        $sql .= "ORDER BY `date` DESC LIMIT " . $limit;
        //debug_event(self::class, 'get_recently_played ' . $sql, 5);

        $db_results = Dba::read($sql, $params);
        while ($row = Dba::fetch_assoc($db_results)) {
            if (
                $geolocation
                && empty($row['geo_name'])
                && !empty($row['geo_latitude'])
                && !empty($row['geo_longitude'])
            ) {
                $row['geo_name'] = Stats::get_cached_place_name((float) $row['geo_latitude'], (float) $row['geo_longitude']);
            }

            $results[] = [
                'object_id' => $row['object_id'],
                'catalog_id' => $row['catalog_id'],
                'user' => $row['user'],
                'object_type' => $row['object_type'],
                'date' => $row['date'],
                'agent' => $row['agent'],
                'geo_latitude' => $row['geo_latitude'] ?? null,
                'geo_longitude' => $row['geo_longitude'] ?? null,
                'geo_name' => $row['geo_name'] ?? null,
                'user_recent' => $row['user_recent'],
                'user_time' => $row['user_time'],
                'user_agent' => $row['user_agent'],
                'activity_id' => $row['activity_id'],
            ];
        }

        return $results;
    }

    /**
     * get_time
     *
     * get the time for the object (song, video, podcast_episode)
     */
    public static function get_time(int $object_id, string $object_type): int
    {
        // you can't get the last played when you haven't played something before
        if (!$object_id || !$object_type) {
            return 0;
        }

        $sql        = sprintf('SELECT `time` FROM `%s` WHERE `id` = ?', $object_type);
        $db_results = Dba::read($sql, [$object_id]);
        $results    = Dba::fetch_assoc($db_results);

        return (int) ($results['time'] ?? 0);
    }

    /**
     * get_top
     * This returns the top X for type Y from the
     * last stats_threshold days
     * @return int[]
     */
    public static function get_top(
        string $input_type,
        int $count,
        int|string $threshold = 0,
        int $offset = 0,
        ?User $user = null,
        bool $random = false,
        int $since = 0,
        int $before = 0,
        bool $by_user = false,
        int $catalog_id = 0,
    ): array {
        if ($count === 0) {
            $count = AmpConfig::get('popular_threshold', 10);
        }

        if ($count === -1) {
            $count  = 0;
            $offset = 0;
        }

        $sql   = self::get_top_sql($input_type, (int) $threshold, 'stream', $user, $random, $since, $before, false, $by_user, $catalog_id);
        $limit = ($offset < 1)
            ? $count
            : $offset . "," . $count;
        if ($limit > 0) {
            $sql .= ' LIMIT ' . $limit;
        }

        //debug_event(self::class, 'get_top ' . $sql, 5);
        $db_results = Dba::read($sql);
        $results    = [];
        while ($row = Dba::fetch_assoc($db_results)) {
            $results[] = (int) $row['id'];
        }

        return $results;
    }

    /**
     * get_top_sql
     * This returns the get_top sql
     */
    public static function get_top_sql(
        string $input_type,
        int $threshold = 0,
        string $count_type = 'stream',
        ?User $user = null,
        bool $random = false,
        int $since = 0,
        int $before = 0,
        bool $addAdditionalColumns = false,
        bool $by_user = false,
        int $catalog_id = 0,
    ): string {
        $type           = self::validate_type($input_type);
        $date           = $since ?: time() - (86400 * $threshold);
        $catalog_filter = (AmpConfig::get('catalog_filter'));
        $filter_user    = ($user ?? Core::get_global('user'));
        if ($type === 'playlist' && !$addAdditionalColumns) {
            $sql = "SELECT `id` FROM `playlist`";
            if ($threshold > 0) {
                $sql .= " WHERE `last_update` >= '" . $date . "' ";
            }

            if ($catalog_filter && $filter_user !== null) {
                $sql .= ($threshold > 0)
                    ? " AND" . Catalog::get_user_filter($type, $filter_user->getId())
                    : " WHERE" . Catalog::get_user_filter($type, $filter_user->getId());
            }

            // playlist is now available in object_count too
            $sql .= "UNION SELECT `object_id` FROM `object_count` WHERE `object_type` = 'playlist'";
            if ($by_user && $filter_user?->id > 0) {
                $sql .= sprintf(" AND `object_count`.`user` = '%s'", $filter_user->id);
            }

            if ($threshold > 0) {
                $sql .= " AND `date` >= '" . $date . "' ";
            }

            if ($catalog_filter && $filter_user !== null) {
                $sql .= " AND" . Catalog::get_user_filter("object_count_" . $type, $filter_user->getId());
            }

            //debug_event(self::class, 'get_top_sql ' . $sql, 5);

            return $sql;
        }

        if (
            $user === null
            && $catalog_id === 0
            && AmpConfig::get('cron_cache')
            && !$addAdditionalColumns
            && in_array($type, ['album', 'album_disk', 'artist', 'song', 'genre', 'catalog', 'live_stream', 'video', 'podcast', 'podcast_episode', 'playlist'], true)
        ) {
            $sql = "SELECT `object_id` AS `id`, MAX(`count`) AS `count` FROM `cache_object_count` WHERE `object_type` = '" . $type . "' AND `count_type` = '" . $count_type . "' AND `threshold` = '" . $threshold . "' GROUP BY `object_id`, `object_type`";
        } else {
            $is_podcast = ($type === 'podcast');
            $select_sql = ($is_podcast)
                ? "`podcast_episode`.`podcast`"
                : "MIN(`object_id`)";
            // Select Top objects counting by # of rows for you only
            $sql   = sprintf('SELECT %s AS `id`, COUNT(*) AS `count`', $select_sql);
            $group = '`object_count`.`object_id`';
            // Add additional columns to use the select query as insert values directly
            if ($addAdditionalColumns) {
                $sql .= ($is_podcast)
                    ? ", 'podcast' AS `object_type`, `count_type`, " . $threshold . " AS `threshold`"
                    : ", `object_type`, `count_type`, " . $threshold . " AS `threshold`";
            }

            $sql .= " FROM `object_count`";
            if ($is_podcast) {
                $group = '`podcast_episode`.`podcast`';
                $type  = 'podcast_episode';
                $sql .= " LEFT JOIN `podcast_episode` ON `podcast_episode`.`id` = `object_count`.`object_id` AND `object_count`.`object_type` = 'podcast_episode'";
            }

            if ($input_type === 'album_artist' || $input_type === 'song_artist') {
                $sql .= " LEFT JOIN `artist` ON `artist`.`id` = `object_count`.`object_id` AND `object_count`.`object_type` = 'artist'";
            }

            if ($input_type === 'album_disk') {
                $sql = ($addAdditionalColumns)
                    ? "SELECT `album_disk`.`id` AS `id`, COUNT(*) AS `count`, 'album_disk' AS `object_type`, `count_type`, " . $threshold . " AS `threshold` FROM `album_disk` LEFT JOIN `song`  ON `song`.`album` = `album_disk`.`album_id` AND `song`.`disk` = `album_disk`.`disk` LEFT JOIN `object_count`  ON `object_count`.`object_id` = `song`.id AND `object_count`.`object_type` = 'song' AND `object_count`.`count_type` = 'download'"
                    : "SELECT `album_disk`.`id` AS `id`, COUNT(*) AS `count` FROM `album_disk` LEFT JOIN `song` ON `song`.`album` = `album_disk`.`album_id` AND `song`.`disk` = `album_disk`.`disk` LEFT JOIN `object_count` ON `object_count`.`object_id` = `song`.`id` AND `object_count`.`object_type` = 'song' AND `object_count`.`count_type` = 'download'";
                $group = '`album_disk`.`id`';
                $type  = 'song';
            }

            if ($user !== null) {
                $sql .= " WHERE `object_count`.`object_type` = '" . $type . "' AND `object_count`.`user` = " . $user->getId();
            } else {
                $sql .= " WHERE `object_count`.`object_type` = '" . $type . "' ";
            }

            if ($by_user && $filter_user?->id > 0) {
                $sql .= sprintf(" AND `object_count`.`user` = '%s'", $filter_user->id);
            }

            if ($threshold > 0) {
                $sql .= " AND `object_count`.`date` >= '" . $date . "'";
                if ($before > 0) {
                    $sql .= " AND `object_count`.`date` <= '" . $before . "'";
                }
            }

            if ($input_type === 'album_artist') {
                $sql .= " AND `artist`.`album_count` > 0";
            }

            if ($input_type === 'song_artist') {
                $sql .= " AND `artist`.`song_count` > 0";
            }

            if (AmpConfig::get('catalog_disable') && in_array($type, ['artist', 'album', 'album_disk', 'song', 'video'], true)) {
                $sql .= " AND " . Catalog::get_enable_filter($type, '`object_id`');
            }

            if ($catalog_filter && in_array($type, ['artist', 'album', 'album_disk', 'podcast_episode', 'song', 'video'], true) && $filter_user !== null) {
                $sql .= " AND" . Catalog::get_user_filter('object_count_' . $type, $filter_user->getId());
            }

            // album_disk rows are keyed on the joined table, everything else filters the object_count id directly
            $catalog_column = ($input_type === 'album_disk')
                ? '`album_disk`.`id`'
                : '`object_count`.`object_id`';
            $catalog_sql = Catalog::get_catalog_id_filter($input_type, $catalog_column, $catalog_id);
            if ($catalog_sql !== '') {
                $sql .= " AND " . $catalog_sql;
            }

            $rating_filter = AmpConfig::get_rating_filter();
            if ($rating_filter > 0 && $rating_filter <= 5 && $user !== null) {
                $sql .= " AND `object_id` NOT IN (SELECT `object_id` FROM `rating` WHERE `rating`.`object_type` = '" . $type . "' AND `rating`.`rating` <=" . $rating_filter . " AND `rating`.`user` = " . $user->getId() . ")";
            }

            $sql .= " AND `count_type` = '" . $count_type . sprintf("' GROUP BY %s, `object_count`.`object_type`, `object_count`.`count_type`", $group);
        }

        if ($random) {
            $sql .= " ORDER BY RAND() DESC ";
        } else {
            $sql .= " ORDER BY `count` DESC ";
        }

        //debug_event(self::class, 'get_top_sql ' . $sql, 5);

        return $sql;
    }

    /**
     * is_already_inserted
     * Check if the same stat has not already been inserted within a graceful delay
     */
    public static function is_already_inserted(
        string $type,
        int $object_id,
        int $user,
        string $agent,
        int $time,
        bool $exact = false,
    ): bool {
        $sql = ($exact)
            ? sprintf("SELECT `object_id`, `date`, `count_type` FROM `object_count` WHERE `object_count`.`user` = ? AND `object_count`.`object_type` = ? AND `object_count`.`count_type` = 'stream' AND `object_count`.`date` = %d ", $time)
            : sprintf("SELECT `object_id`, `date`, `count_type` FROM `object_count` WHERE `object_count`.`user` = ? AND `object_count`.`object_type` = ? AND `object_count`.`count_type` = 'stream' AND (`object_count`.`date` >= (%d - 5) AND `object_count`.`date` <= (%d + 5)) ", $time, $time);
        $params = [$user, $type];
        if ($agent !== '') {
            $sql .= "AND `object_count`.`agent` = ? ";
            $params[] = $agent;
        }

        $sql .= "ORDER BY `object_count`.`date` DESC";

        $db_results = Dba::read($sql, $params);
        while ($row = Dba::fetch_assoc($db_results)) {
            // Stop double ups
            if ($row['object_id'] == $object_id) {
                debug_event(self::class, 'Object already inserted {' . $object_id . '} date: ' . $time, 5);

                return true;
            }
        }

        return false;
    }

    /**
     * Migrate an object associate stats to a new object
     */
    public static function migrate(string $object_type, int $old_object_id, int $new_object_id, int $child_id): void
    {
        if (!in_array($object_type, ['song', 'album', 'artist', 'video', 'live_stream', 'playlist', 'podcast', 'podcast_episode'], true)) {
            return;
        }

        $sql    = "UPDATE IGNORE `object_count` SET `object_id` = ? WHERE `object_type` = ? AND `object_id` = ?";
        $params = [$new_object_id, $object_type, $old_object_id];
        if ($child_id > 0) {
            $sql .= " AND `date` IN (SELECT `date` FROM (SELECT `date` FROM `object_count` WHERE `object_type` = 'song' AND `object_id` = ?) AS `song_date`)";
            $params[] = $child_id;
        }

        Dba::write($sql, $params);

        if ((int) $child_id === 0) {
            // move consolidated history as well (merge counts on conflict)
            Dba::write("INSERT INTO `object_count_summary` (`object_type`, `object_id`, `user`, `count_type`, `count`, `date_from`, `date_to`) SELECT `old_summary`.`object_type`, ?, `old_summary`.`user`, `old_summary`.`count_type`, `old_summary`.`count`, `old_summary`.`date_from`, `old_summary`.`date_to` FROM `object_count_summary` AS `old_summary` WHERE `old_summary`.`object_type` = ? AND `old_summary`.`object_id` = ? ON DUPLICATE KEY UPDATE `count` = `object_count_summary`.`count` + VALUES(`count`), `date_from` = LEAST(`object_count_summary`.`date_from`, VALUES(`date_from`)), `date_to` = GREATEST(`object_count_summary`.`date_to`, VALUES(`date_to`));", [$new_object_id, $object_type, $old_object_id]);
            Dba::write("DELETE FROM `object_count_summary` WHERE `object_type` = ? AND `object_id` = ?;", [$object_type, $old_object_id]);
            // the archive has no unique key, so the detail rows just move across
            Dba::write("UPDATE `object_count_archive` SET `object_id` = ? WHERE `object_type` = ? AND `object_id` = ?;", [$new_object_id, $object_type, $old_object_id]);
        }
    }

    /**
     * restore
     *
     * Put every archived detail row back into `object_count`, rebuild the DERIVED_TYPES rows that consolidation
     * dropped, and subtract what was restored from `object_count_summary`.
     *
     * The subtraction is exact rather than a truncate: a summary row can hold counts from a consolidation run that
     * predates the archive, and those have no detail to restore, so they have to survive.
     * Counters are not rebuilt here, the caller runs Catalog::update_counts afterwards.
     * @return array{rows: int, derived: int, executed: bool}
     */
    public static function restore(bool $dry_run = true): array
    {
        $db_results = Dba::read("SELECT COUNT(*) AS `rows` FROM `object_count_archive`;");
        $row        = Dba::fetch_assoc($db_results);
        $rows       = (int) ($row['rows'] ?? 0);

        if ($dry_run || $rows === 0) {
            return ['rows' => $rows, 'derived' => 0, 'executed' => false];
        }

        $columns = "(`object_type`, `object_id`, `count_type`, `date`, `user`, `agent`, `geo_latitude`, `geo_longitude`, `geo_name`)";
        $dbh     = Dba::dbh();

        $in_transaction = ($dbh !== null && $dbh->beginTransaction());
        // INSERT IGNORE throughout: the unique key makes a repeated restore a no-op instead of a duplicate
        $restore = Dba::write("INSERT IGNORE INTO `object_count` " . $columns . " SELECT `object_type`, `object_id`, `count_type`, `date`, `user`, `agent`, `geo_latitude`, `geo_longitude`, `geo_name` FROM `object_count_archive`;");
        $derived = 0;
        foreach (self::derivedSelects() as $select) {
            $result = ($restore !== null)
                ? Dba::write("INSERT IGNORE INTO `object_count` " . $columns . " " . $select . ";")
                : null;
            if ($result === null) {
                $restore = null;
                break;
            }

            $derived += $result->rowCount();
        }

        // subtract the restored detail from the summary, then drop any row that is fully accounted for
        $subtract = ($restore !== null)
            ? Dba::write("UPDATE `object_count_summary` AS `summary` INNER JOIN (SELECT `object_type`, `object_id`, `user`, `count_type`, COUNT(*) AS `restored` FROM `object_count_archive` GROUP BY `object_type`, `object_id`, `user`, `count_type` UNION ALL " . self::derivedAggregateSelect() . ") AS `restored` ON `restored`.`object_type` = `summary`.`object_type` AND `restored`.`object_id` = `summary`.`object_id` AND `restored`.`user` = `summary`.`user` AND `restored`.`count_type` = `summary`.`count_type` SET `summary`.`count` = GREATEST(0, CAST(`summary`.`count` AS SIGNED) - CAST(`restored`.`restored` AS SIGNED));")
            : null;
        $cleanup = ($subtract !== null)
            ? Dba::write("DELETE FROM `object_count_summary` WHERE `count` <= 0;")
            : null;
        // the archive only holds rows that are currently consolidated out, so empty it once they are back.
        // DELETE not TRUNCATE: TRUNCATE is DDL and would implicitly commit the transaction
        $purge = ($cleanup !== null)
            ? Dba::write("DELETE FROM `object_count_archive`;")
            : null;

        if ($purge === null) {
            if ($in_transaction && $dbh->inTransaction()) {
                $dbh->rollBack();
            }

            throw new RuntimeException('Stats restore failed and was rolled back');
        }

        if ($in_transaction && $dbh->inTransaction()) {
            $dbh->commit();
        }

        return ['rows' => $rows, 'derived' => $derived, 'executed' => true];
    }

    /**
     * shift_last_play
     * When you play or pause the song, shift the start time to allow better skip recording
     */
    public static function shift_last_play(int $user_id, string $agent, int $original_date, int $new_date): void
    {
        // update the object_count table
        $sql = "UPDATE `object_count` SET `object_count`.`date` = ? WHERE `object_count`.`user` = ? AND `object_count`.`agent` = ? AND `object_count`.`date` = ?";
        Dba::write($sql, [$new_date, $user_id, $agent, $original_date]);

        // update the user_activity table
        $sql = "UPDATE `user_activity` SET `user_activity`.`activity_date` = ? WHERE `user_activity`.`user` = ? AND `user_activity`.`activity_date` = ?";
        Dba::write($sql, [$new_date, $user_id, $original_date]);
    }

    /**
     * skip_last_play
     * this sets the object_counts count type to skipped
     * Gets called when the next song is played in quick succession
     */
    public static function skip_last_play(int $date, string $agent, int $user_id, int $object_id, string $object_type): void
    {
        // change from a stream to a skip
        $sql = "UPDATE `object_count` SET `count_type` = 'skip' WHERE `date` = ? AND `agent` = ? AND `user` = ? AND `object_count`.`object_type` = ? ORDER BY `object_count`.`date` DESC";
        if (!Dba::write($sql, [$date, $agent, $user_id, $object_type], true) instanceof PDOStatement) {
            // this is probably a duplicate / mass insert so delete it instead
            $sql = "DELETE FROM `object_count` WHERE `date` = ? AND `agent` = ? AND `user` = ? AND `object_type` = ?";
            Dba::write($sql, [$date, $agent, $user_id, $object_type]);
        }

        // update the total counts (and total_skip counts) as well
        if ($user_id > 0 && $agent !== 'debug') {
            self::count($object_type, $object_id, 'down');
            if ($object_type === 'song') {
                $song = new Song($object_id);
                self::count('album', $song->album, 'down');
                self::count('album_disk', $song->album_disk, 'down');
                $artists = array_unique(array_merge(Song::get_parent_array($song->id), Song::get_parent_array($song->album, 'album')));
                foreach ($artists as $artist_id) {
                    self::count('artist', (int) $artist_id, 'down');
                }
            }

            if ($object_type === 'podcast_episode') {
                $podcast_episode = new Podcast_Episode($object_id);
                self::count('podcast', $podcast_episode->podcast, 'down');
            }

            if (in_array($object_type, ['song', 'video', 'podcast_episode'], true)) {
                $sql = sprintf("UPDATE `user_data`, (SELECT `%s`.`size` FROM `%s` WHERE `%s`.`id` = ?) AS `%s` SET `value` = `value` - `%s`.`size` WHERE `user` = ? AND `value` = 'play_size'", $object_type, $object_type, $object_type, $object_type, $object_type);
                Dba::write($sql, [$object_id, $object_id]);
            }
        }

        // To remove associated album and artist entries
        $sql = "DELETE FROM `object_count` WHERE `object_type` IN ('album', 'artist', 'podcast') AND `date` = ? AND `agent` = ? AND `user` = ? ";

        Dba::write($sql, [$date, $agent, $user_id]);
    }

    /**
     * validate_type
     * This function takes a type and returns only those
     * which are allowed, ensures good data gets put into the db
     */
    public static function validate_type(string $type): string
    {
        return match ($type) {
            'artist', 'album', 'album_disk', 'tag', 'song', 'video', 'playlist', 'podcast', 'podcast_episode', 'live_stream', 'collection' => $type,
            'album_artist', 'song_artist' => 'artist',
            'genre' => 'tag',
            default => 'song',
        };
    }

    /**
     * Per-type aggregate shaped for the summary subtraction. Deliberately archive-based, not object_count-based:
     * the summary must only lose what consolidation put into it, never the extra rows the repair pass creates.
     */
    private static function derivedAggregateSelect(): string
    {
        $selects = [];
        foreach (self::derivedSources('object_count_archive', 'archive') as $type => $source) {
            $selects[] = "SELECT '" . $type . "', `derived`.`derived_id`, `derived`.`user`, `derived`.`count_type`, COUNT(*) FROM (" . $source . ") AS `derived` GROUP BY `derived`.`derived_id`, `derived`.`user`, `derived`.`count_type`";
        }

        return implode(' UNION ALL ', $selects);
    }

    /**
     * SELECT statements rebuilding the DERIVED_TYPES rows. These read `object_count` itself, so a restore also
     * repairs parent rows that went missing for any other reason, not only the ones consolidation removed.
     * The NOT EXISTS guard keeps that idempotent: INSERT IGNORE alone would not dedupe rows with a NULL `agent`,
     * because a unique index treats NULLs as distinct. Column order matches the insert list in Stats::restore().
     * @return string[]
     */
    private static function derivedSelects(): array
    {
        $selects = [];
        foreach (self::derivedSources('object_count', 'media') as $type => $source) {
            $selects[] = "SELECT '" . $type . "', `derived`.`derived_id`, `derived`.`count_type`, `derived`.`date`, `derived`.`user`, `derived`.`agent`, `derived`.`geo_latitude`, `derived`.`geo_longitude`, `derived`.`geo_name` FROM (" . $source . ") AS `derived` WHERE NOT EXISTS (SELECT 1 FROM `object_count` AS `existing` WHERE `existing`.`object_type` = '" . $type . "' AND `existing`.`object_id` = `derived`.`derived_id` AND `existing`.`date` = `derived`.`date` AND `existing`.`user` = `derived`.`user` AND `existing`.`agent` <=> `derived`.`agent` AND `existing`.`count_type` = `derived`.`count_type`)";
        }

        return $selects;
    }

    /**
     * Maps each derived type to the media rows that produced it, mirroring the Song/Podcast_Episode set_played
     * fan-out. DISTINCT because a song artist can also be the album artist, and because two songs from one album
     * can share a second, so the original insert collapsed them too.
     * @return array<string, string>
     */
    private static function derivedSources(string $table, string $alias): array
    {
        // the fan-out writes 'stream' rows for the parents. Stats::skip_last_play then DELETES the album/artist/podcast
        // rows when a play is skipped, so those three must be rebuilt from 'stream' media only or a restore resurrects
        // parent plays that were deliberately removed. album_disk is left in place by a skip, so it is rebuilt from
        // both 'stream' and 'skip' media to match what actually survives.
        $columns  = "'stream' AS `count_type`, `" . $alias . "`.`date`, `" . $alias . "`.`user`, `" . $alias . "`.`agent`, `" . $alias . "`.`geo_latitude`, `" . $alias . "`.`geo_longitude`, `" . $alias . "`.`geo_name`";
        $streamed = "`" . $alias . "`.`count_type` = 'stream'";
        $or_skip  = "`" . $alias . "`.`count_type` IN ('stream', 'skip')";
        // resolve the parent through the joins rather than a stored id: merges and re-tagging move a media item
        // between parents, so only a lookup at restore time gives the parent that is true now
        $song    = "FROM `" . $table . "` AS `" . $alias . "` INNER JOIN `song` ON `song`.`id` = `" . $alias . "`.`object_id`";
        $episode = "FROM `" . $table . "` AS `" . $alias . "` INNER JOIN `podcast_episode` ON `podcast_episode`.`id` = `" . $alias . "`.`object_id`";
        $is_song = "`" . $alias . "`.`object_type` = 'song'";

        return [
            'album' => "SELECT DISTINCT `song`.`album` AS `derived_id`, " . $columns . " " . $song . " WHERE " . $is_song . " AND `song`.`album` > 0 AND " . $streamed,
            'album_disk' => "SELECT DISTINCT `album_disk`.`id` AS `derived_id`, " . $columns . " " . $song . " INNER JOIN `album_disk` ON `album_disk`.`album_id` = `song`.`album` AND `album_disk`.`disk` = `song`.`disk` WHERE " . $is_song . " AND `song`.`album` > 0 AND " . $or_skip,
            'artist' => "SELECT DISTINCT `artist_map`.`artist_id` AS `derived_id`, " . $columns . " " . $song . " INNER JOIN `artist_map` ON (`artist_map`.`object_type` = 'song' AND `artist_map`.`object_id` = `song`.`id`) OR (`artist_map`.`object_type` = 'album' AND `artist_map`.`object_id` = `song`.`album`) WHERE " . $is_song . " AND `artist_map`.`artist_id` > 0 AND " . $streamed,
            'podcast' => "SELECT DISTINCT `podcast_episode`.`podcast` AS `derived_id`, " . $columns . " " . $episode . " WHERE `" . $alias . "`.`object_type` = 'podcast_episode' AND `podcast_episode`.`podcast` > 0 AND " . $streamed,
        ];
    }

    /**
     * DERIVED_TYPES as a quoted SQL list
     */
    private static function derivedTypeList(): string
    {
        return "'" . implode("', '", self::DERIVED_TYPES) . "'";
    }

    /**
     * Collect the folder an object lives in as well as every ancestor folder above it.
     * `folder`.`path` is the comma separated chain of parent ids, so the tree is resolved without recursing.
     *
     * @return list<int>
     */
    private static function getFolderTree(string $type, int $object_id): array
    {
        $sql        = "SELECT DISTINCT `folder`.`id`, `folder`.`path` FROM `folder_map` INNER JOIN `folder` ON `folder`.`id` = `folder_map`.`folder_id` WHERE `folder_map`.`object_id` = ? AND `folder_map`.`object_type` = ?;";
        $db_results = Dba::read($sql, [$object_id, $type]);

        $results = [];
        while ($row = Dba::fetch_assoc($db_results)) {
            $results[] = (int) $row['id'];
            foreach (explode(',', (string) $row['path']) as $parent_id) {
                if (ctype_digit($parent_id)) {
                    $results[] = (int) $parent_id;
                }
            }
        }

        return array_values(array_unique($results));
    }

    /**
     * clear
     *
     * This clears all stats for play history  for the site or an individual user
     */
    public function clear(int $user_id = 0): void
    {
        // the archive goes too, or Stats::restore() would resurrect history that was deliberately cleared
        if ($user_id > 0) {
            Dba::write("DELETE FROM `object_count` WHERE `user` = ?;", [$user_id]);
            Dba::write("DELETE FROM `object_count_summary` WHERE `user` = ?;", [$user_id]);
            Dba::write("DELETE FROM `object_count_archive` WHERE `user` = ?;", [$user_id]);
        } else {
            Dba::write("TRUNCATE `object_count`;");
            Dba::write("TRUNCATE `object_count_summary`;");
            Dba::write("TRUNCATE `object_count_archive`;");
        }

        // a rebuild only reaches rows that still have history, so the counters left behind are zeroed first
        $songRepository = $this->songRepository;
        $songRepository->resetCountsWithoutHistory();
        $songRepository->updateAllCounts();

        $this->videoRepository->updateAllCounts();
        $this->podcastEpisodeRepository->updateAllCounts();
        $this->podcastRepository->updateAllCounts();

        // album, album_disk and artist roll up from the songs, so they follow once those are right
        $this->albumRepository->updateAllCounts();
        $this->albumRepository->updateAllSkipCounts();
        $this->artistRepository->updateAllCounts();
        $this->artistRepository->updateAllSkipCounts();
    }

    /**
     * has_played_history
     * this checks to see if the current object has been played recently by the user
     */
    public function has_played_history(string $object_type, Podcast_Episode|Video|Song $object, int $user_id, string $agent, int $date): bool
    {
        // if it's already recorded (but from a different agent), don't do it again
        if (self::is_already_inserted($object_type, $object->id, $user_id, '', $date, true)) {
            return false;
        }

        $previous = self::get_last_play($user_id, $agent, $date);
        // no previous data?
        if (!$previous['object_id'] || !$previous['object_type']) {
            return true;
        }

        $last_time = self::get_time($previous['object_id'], $previous['object_type']);
        $diff      = $date - (int) $previous['date'];
        $item_time = $object->time;
        $skip_time = AmpConfig::get_skip_timer($last_time);

        // if your last song is 30 seconds and your skip timer is 40 you don't want to keep skipping it.
        if ($last_time > 0 && $last_time < $skip_time) {
            return true;
        }

        // this object was your last play and the length between plays is too short.
        if ($previous['object_id'] == $object->id && $diff < ($item_time)) {
            $this->logger->warning('Repeated the same ' . $object::class . ' too quickly (' . $diff . '/' . ($item_time) . 's), not recording stats for {' . $object->id . '}', [LegacyLogger::CONTEXT_TYPE => self::class]);

            return false;
        }

        // when the difference between recordings is too short, the previous object has been skipped, so note that
        if (($diff < $skip_time || ($diff < $skip_time && $last_time > $skip_time))) {
            $this->logger->warning('Last ' . $previous['object_type'] . ' played within skip limit (' . $diff . '/' . $skip_time . 's). Skipping {' . $previous['object_id'] . '}', [LegacyLogger::CONTEXT_TYPE => self::class]);
            self::skip_last_play($previous['date'], $previous['agent'], $previous['user'], $previous['object_id'], $previous['object_type']);
            // delete song, podcast_episode and video from user_activity to keep stats in line
            $this->userActivityRepository->deleteByDate($previous['date'], 'play', (int) $previous['user']);
        }

        return true;
    }

    /**
     * insert
     * This inserts a new record for the specified object
     * with the specified information, amazing!
     * @param array{latitude?: float|string, longitude?: float|string, name?: string,} $location
     */
    public function insert(
        string $input_type,
        int $object_id,
        int $user_id,
        string $agent = '',
        array $location = [],
        string $count_type = 'stream',
        ?int $date = null,
    ): bool {
        if (AmpConfig::get('use_auth') && $user_id < User::INTERNAL_SYSTEM_USER_ID) {
            $this->logger->warning('Invalid user given ' . $user_id, [LegacyLogger::CONTEXT_TYPE => self::class]);

            return false;
        }

        if ($date == null) {
            $date = time();
        }

        $type = self::validate_type($input_type);
        if (self::is_already_inserted($type, $object_id, $user_id, $agent, $date)) {
            return false;
        }

        $latitude   = (isset($location['latitude'])) ? (float) $location['latitude'] : null;
        $longitude  = (isset($location['longitude'])) ? (float) $location['longitude'] : null;
        $geoname    = $location['name'] ?? null;
        $sql        = "INSERT IGNORE INTO `object_count` (`object_type`, `object_id`, `count_type`, `date`, `user`, `agent`, `geo_latitude`, `geo_longitude`, `geo_name`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $db_results = Dba::write($sql, [$type, $object_id, $count_type, $date, $user_id, $agent, $latitude, $longitude, $geoname]);

        // the count was inserted
        if ($db_results instanceof PDOStatement) {
            if (
                in_array($type, ['song', 'album', 'album_disk', 'artist', 'video', 'podcast', 'podcast_episode'], true)
                && $count_type === 'stream' && $user_id !== 0
                && $agent !== 'debug'
            ) {
                self::count($type, $object_id, 'up', $date);
                // don't register activity for album or artist plays
                if (!in_array($type, ['album', 'album_disk', 'artist', 'podcast'], true)) {
                    $this->userActivityPoster->post($user_id, 'play', $type, $object_id, (int) $date);
                }
            }

            return true;
        }

        $this->logger->warning('Unable to insert statistics for ' . $user_id . ':' . $object_id, [LegacyLogger::CONTEXT_TYPE => self::class]);

        return false;
    }
}
