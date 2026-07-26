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

namespace Ampache\Module\Util;

use Ampache\Config\AmpConfig;
use Ampache\Module\System\Core;
use Ampache\Module\System\Dba;
use Ampache\Module\System\Plugin\PluginTypeEnum;
use Ampache\Plugin\PluginLocationInterface;
use Ampache\Repository\Model\Catalog;
use Ampache\Repository\Model\Plugin;
use Ampache\Repository\Model\User;
use Ampache\Repository\UserRepositoryInterface;
use DateTimeImmutable;
use Goat1000\SVGGraph\SVGGraph;

class Graph
{
    // y-axis label formats
    private const string FORMAT_BYTES  = 'bytes';
    private const string FORMAT_METRIC = 'metric';

    private const int MAX_POINTS = 3000;

    /**
     */
    public function display_map(
        int    $user_id,
        string $object_type,
        int    $object_id,
        int    $start_date,
        int    $end_date,
        string $zoom,
    ): bool {
        $pts  = $this->get_geolocation_pts($user_id, $object_type, $object_id, $start_date, $end_date, $zoom);
        $user = Core::get_global('user');
        if (!$user instanceof User) {
            return false;
        }

        foreach (Plugin::get_plugins(PluginTypeEnum::GEO_MAP) as $plugin_name) {
            $plugin = new Plugin($plugin_name);
            if ($plugin->_plugin instanceof PluginLocationInterface && $plugin->load($user) && $plugin->_plugin->display_map($pts)) {
                return true;
            }
        }

        return false;
    }

    /**
     */
    public function get_total_bandwidth(int $user_id = 0, ?int $start_date = null, ?int $end_date = null): int
    {
        $total  = 0;
        $values = $this->get_all_type_pts('get_user_bandwidth_pts', $user_id, null, 0, $start_date, $end_date, 'month');
        foreach ($values as $value) {
            $total += $value;
        }

        return (int) $total;
    }

    /**
     */
    public function get_total_hits(int $user_id = 0, ?int $start_date = null, ?int $end_date = null): int
    {
        $total  = 0;
        $values = $this->get_all_type_pts('get_user_hits_pts', $user_id, null, 0, $start_date, $end_date, 'month');
        foreach ($values as $value) {
            $total += $value;
        }

        return (int) $total;
    }

    /**
     */
    public function get_total_time(int $user_id = 0, ?int $start_date = null, ?int $end_date = null): int
    {
        $total  = 0;
        $values = $this->get_all_type_pts('get_user_time_pts', $user_id, null, 0, $start_date, $end_date, 'month');
        foreach ($values as $value) {
            $total += $value;
        }

        return (int) $total;
    }

    /**
     */
    public function render_catalog_files(
        int     $catalog_id = 0,
        ?string $object_type = null,
        int     $object_id = 0,
        ?int    $start_date = null,
        ?int    $end_date = null,
        string  $zoom = 'day',
        int     $width = 0,
        int     $height = 0,
    ): void {
        $series = [];
        $this->get_catalog_all_pts(
            'get_catalog_files_pts',
            $series,
            $catalog_id,
            $object_type,
            $object_id,
            $start_date,
            $end_date,
            $zoom
        );

        $this->render_graph('Files', $series, self::FORMAT_METRIC, '', $zoom, $width, $height);
    }

    /**
     */
    public function render_catalog_size(
        int     $catalog_id = 0,
        ?string $object_type = null,
        int     $object_id = 0,
        ?int    $start_date = null,
        ?int    $end_date = null,
        string  $zoom = 'day',
        int     $width = 0,
        int     $height = 0,
    ): void {
        $series = [];
        $this->get_catalog_all_pts(
            'get_catalog_size_pts',
            $series,
            $catalog_id,
            $object_type,
            $object_id,
            $start_date,
            $end_date,
            $zoom
        );

        $this->render_graph('Size', $series, self::FORMAT_BYTES, 'Size', $zoom, $width, $height);
    }

    /**
     */
    public function render_user_bandwidth(
        int     $user_id = 0,
        ?string $object_type = null,
        int     $object_id = 0,
        ?int    $start_date = null,
        ?int    $end_date = null,
        string  $zoom = 'day',
        int     $width = 0,
        int     $height = 0,
    ): void {
        $series = [];
        $this->get_user_all_pts('get_user_bandwidth_pts', $series, $user_id, $object_type, $object_id, $start_date, $end_date, $zoom);

        $this->render_graph('Bandwidth', $series, self::FORMAT_BYTES, 'Bandwidth', $zoom, $width, $height);
    }

    /**
     */
    public function render_user_hits(
        int     $user_id,
        ?string $object_type,
        int     $object_id,
        ?int    $start_date = null,
        ?int    $end_date = null,
        string  $zoom = 'day',
        int     $width = 0,
        int     $height = 0,
    ): void {
        $series = [];
        $this->get_user_all_pts(
            'get_user_hits_pts',
            $series,
            $user_id,
            $object_type,
            $object_id,
            $start_date,
            $end_date,
            $zoom
        );

        $this->render_graph('Hits', $series, self::FORMAT_METRIC, 'Hits', $zoom, $width, $height);
    }

    /**
     * @param array<string, array<int, int|float>> $series series name => [unix timestamp => value]
     * @return array<int, int|float>
     */
    protected function get_all_pts(
        string  $fct,
        array   &$series,
        int     $user_id = 0,
        ?string $object_type = null,
        int     $object_id = 0,
        ?int    $start_date = null,
        ?int    $end_date = null,
        string  $zoom = 'day',
        bool    $show_total = true,
    ): array {
        $values = $this->get_all_type_pts($fct, $user_id, $object_type, $object_id, $start_date, $end_date, $zoom);
        if ($show_total) {
            $series['Total'] = $values;
        }

        return $values;
    }

    /**
     * @return array<int, int|float> unix timestamp => value
     */
    protected function get_all_type_pts(
        string  $fct,
        int     $id = 0,
        ?string $object_type = null,
        int     $object_id = 0,
        ?int    $start_date = null,
        ?int    $end_date = null,
        string  $zoom = 'day',
    ): array {
        $type = $object_type;
        if ($object_type === null) {
            $type = 'song';
        }

        $song_values  = $this->$fct($id, $type, $object_id, $start_date, $end_date, $zoom);
        $video_values = [];
        if ($object_type === null && AmpConfig::get('allow_video')) {
            $video_values = $this->$fct($id, 'video', $object_id, $start_date, $end_date, $zoom);
        }

        $values = $song_values;
        foreach ($video_values as $date => $value) {
            if (array_key_exists((string) $date, $values)) {
                $values[$date] += $value;
            } else {
                $values[$date] = $value;
            }
        }

        ksort($values, SORT_NUMERIC);

        return $values;
    }

    /**
     * Read a per-bucket total, with a zero for every bucket the query returned nothing for.
     *
     * A quiet day is a real zero rather than a gap, and without one a library that was only
     * listened to on a single day drew a single point no matter how wide the range was.
     *
     * @param int[] $buckets
     * @return array<int, int>
     */
    protected function get_bucketed_pts(string $sql, array $buckets): array
    {
        $values     = array_fill_keys($buckets, 0);
        $db_results = Dba::read($sql);
        while ($results = Dba::fetch_assoc($db_results)) {
            // the database groups in its own timezone, so snap the bucket it reports onto ours
            // rather than assuming the two line up
            $values[$this->find_bucket($buckets, (int) $results['zoom_date'])] += (int) $results['value'];
        }

        return $values;
    }

    /**
     * @param array<string, array<int, int|float>> $series series name => [unix timestamp => value]
     */
    protected function get_catalog_all_pts(
        string  $fct,
        array   &$series,
        int     $catalog_id = 0,
        ?string $object_type = null,
        int     $object_id = 0,
        ?int    $start_date = null,
        ?int    $end_date = null,
        string  $zoom = 'day',
    ): void {
        $values = $this->get_all_pts($fct, $series, $catalog_id, $object_type, $object_id, $start_date, $end_date, $zoom, false);

        // Only display other users if the graph is not for a specific catalog
        if ($catalog_id === 0) {
            $catalogs = Catalog::get_all_catalogs();
            foreach ($catalogs as $catalog_id) {
                $catalog = Catalog::create_from_id($catalog_id);
                if ($catalog === null) {
                    break;
                }

                $catalog_values = $this->get_all_type_pts($fct, $catalog_id, $object_type, $object_id, $start_date, $end_date, $zoom);
                $points         = [];
                foreach (array_keys($values) as $date) {
                    $points[$date] = $catalog_values[$date] ?? 0;
                }

                $series[(string) $catalog->name] = $points;
            }
        }
    }

    /**
     */
    protected function get_catalog_files_pts(
        int    $catalog_id = 0,
        string $object_type = 'song',
        int    $object_id = 0,
        ?int   $start_date = null,
        ?int   $end_date = null,
        string $zoom = 'day',
    ): array {
        $end_date ??= time();
        $start_date ??= $end_date - 864000;

        $buckets = $this->get_zoom_buckets($start_date, $end_date, $zoom);
        $source  = $this->get_catalog_media_source($object_type, $object_id);
        if ($buckets === [] || $source === null) {
            return [];
        }

        [$table, $restrict] = $source;

        $start_date = $buckets[0];
        $dateformat = $this->get_sql_date_format("`" . $table . "`.`addition_time`", $zoom);
        $filter     = $restrict . $this->get_catalog_sql_filter($table, $catalog_id);
        $where      = $this->get_catalog_sql_where($table, $start_date, $end_date) . $filter;

        $sql      = "SELECT " . $dateformat . " AS `zoom_date`, COUNT(`" . $table . "`.`id`) AS `value` FROM `" . $table . "` " . $where . " GROUP BY " . $dateformat;
        $baseline = "SELECT COUNT(`" . $table . "`.`id`) FROM `" . $table . "` WHERE `" . $table . "`.`addition_time` < " . $start_date . $filter;

        return $this->get_running_total_pts($sql, $baseline, $buckets);
    }

    /**
     * Resolve the requested object type to the media table the catalog graphs can measure.
     *
     * Only song, video and podcast_episode are files on disk with a size and an addition time of
     * their own. Everything else a library item can be is a container, so an album or an artist is
     * measured through the songs it holds. Reading `size` off those tables was a fatal query and
     * counting their rows by their own `addition_time` reported zero, because an artist row is
     * created with `addition_time` 0.
     *
     * @return array{0: string, 1: string}|null media table and the restriction for it, null when
     *                                          the type holds no media the graphs can measure
     */
    protected function get_catalog_media_source(?string $object_type, int $object_id): ?array
    {
        if (in_array($object_type, ['song', 'video', 'podcast_episode'], true)) {
            return [$object_type, ($object_id > 0) ? " AND `" . $object_type . "`.`id` = " . $object_id : ''];
        }

        // without an id the container is just "everything", which is every song
        if ($object_id < 1) {
            return ['song', ''];
        }

        return match ($object_type) {
            'album' => ['song', " AND `song`.`album` = " . $object_id],
            'album_disk' => ['song', " AND `song`.`album_disk` = " . $object_id],
            'artist' => ['song', " AND `song`.`id` IN (SELECT `object_id` FROM `artist_map` WHERE `object_type` = 'song' AND `artist_id` = " . $object_id . ")"],
            default => null,
        };
    }

    /**
     */
    protected function get_catalog_size_pts(
        int    $catalog_id = 0,
        string $object_type = 'song',
        int    $object_id = 0,
        ?int   $start_date = null,
        ?int   $end_date = null,
        string $zoom = 'day',
    ): array {
        $end_date ??= time();
        $start_date ??= $end_date - 864000;

        $buckets = $this->get_zoom_buckets($start_date, $end_date, $zoom);
        $source  = $this->get_catalog_media_source($object_type, $object_id);
        if ($buckets === [] || $source === null) {
            return [];
        }

        [$table, $restrict] = $source;

        $start_date = $buckets[0];
        $dateformat = $this->get_sql_date_format("`" . $table . "`.`addition_time`", $zoom);
        $filter     = $restrict . $this->get_catalog_sql_filter($table, $catalog_id);
        $where      = $this->get_catalog_sql_where($table, $start_date, $end_date) . $filter;

        $sql      = "SELECT " . $dateformat . " AS `zoom_date`, IFNULL(SUM(`" . $table . "`.`size`), 0) AS `value` FROM `" . $table . "` " . $where . " GROUP BY " . $dateformat;
        $baseline = "SELECT IFNULL(SUM(`" . $table . "`.`size`), 0) FROM `" . $table . "` WHERE `" . $table . "`.`addition_time` < " . $start_date . $filter;

        return $this->get_running_total_pts($sql, $baseline, $buckets);
    }

    /**
     * The catalog part of the where clause on its own, so the running total that seeds a catalog
     * graph is restricted the same way the buckets inside the window are.
     */
    protected function get_catalog_sql_filter(
        string $object_type = 'song',
        int    $catalog_id = 0,
    ): string {
        return ($catalog_id > 0)
            ? " AND `" . $object_type . "`.`catalog` = " . $catalog_id
            : '';
    }

    /**
     */
    protected function get_catalog_sql_where(
        string $object_type = 'song',
        ?int   $start_date = null,
        ?int   $end_date = null,
    ): string {
        $start_date = (int) ($start_date);
        $end_date   = (int) ($end_date);
        if ($end_date === 0) {
            $end_date = time();
        }

        if ($start_date === 0) {
            $start_date = $end_date - 864000;
        }

        return "WHERE `" . $object_type . "`.`addition_time` >= " . $start_date . " AND `" . $object_type . "`.`addition_time` <= " . $end_date;
    }

    /**
     */
    protected function get_geolocation_pts(
        int    $user_id = 0,
        string $object_type = '',
        int    $object_id = 0,
        ?int   $start_date = null,
        ?int   $end_date = null,
        string $zoom = 'day',
    ): array {
        $pts = [];

        $where = $this->get_user_sql_where($user_id, $object_type, $object_id, $start_date, $end_date);
        if ($object_type === '') {
            $where .= " AND `object_type` IN ('song', 'video')";
        }

        $sql        = sprintf('SELECT `geo_latitude`, `geo_longitude`, `geo_name`, MAX(`date`) AS `last_date`, COUNT(`id`) AS `hits` FROM `object_count` %s AND `geo_latitude` IS NOT NULL AND `geo_longitude` IS NOT NULL GROUP BY `geo_latitude`, `geo_longitude`, `geo_name` ORDER BY `last_date`, `geo_name` DESC', $where);
        $db_results = Dba::read($sql);
        while ($results = Dba::fetch_assoc($db_results)) {
            $pts[] = [
                'latitude' => $results['geo_latitude'],
                'longitude' => $results['geo_longitude'],
                'name' => $results['geo_name'],
                'last_date' => $results['last_date'],
                'hits' => $results['hits']
            ];
        }

        return $pts;
    }

    /**
     * Turn per-bucket additions into a running total across every bucket.
     *
     * @param int[] $buckets
     * @return array<int, int>
     */
    protected function get_running_total_pts(string $sql, string $baseline, array $buckets): array
    {
        $deltas     = array_fill_keys($buckets, 0);
        $db_results = Dba::read($sql);
        while ($results = Dba::fetch_assoc($db_results)) {
            // the database groups in its own timezone, so snap the bucket it reports onto ours
            // rather than assuming the two line up
            $deltas[$this->find_bucket($buckets, (int) $results['zoom_date'])] += (int) $results['value'];
        }

        $row     = Dba::fetch_row(Dba::read($baseline));
        $running = (int) ($row[0] ?? 0);

        $values = [];
        foreach ($buckets as $bucket) {
            $running += $deltas[$bucket];
            $values[$bucket] = $running;
        }

        return $values;
    }

    /**
     */
    protected function get_sql_date_format(string $field, string $zoom): string
    {
        $dateformat = match ($zoom) {
            'hour' => "DATE_FORMAT(FROM_UNIXTIME(" . $field . "), '%Y-%m-%d %H:00:00')",
            'year' => "DATE_FORMAT(FROM_UNIXTIME(" . $field . "), '%Y-01-01')",
            'month' => "DATE_FORMAT(FROM_UNIXTIME(" . $field . "), '%Y-%m-01')",
            default => "DATE_FORMAT(FROM_UNIXTIME(" . $field . "), '%Y-%m-%d')",
        };

        return "UNIX_TIMESTAMP(" . $dateformat . ")";
    }

    /**
     * get_user_all_pts
     *
     * @param array<string, array<int, int|float>> $series series name => [unix timestamp => value]
     */
    protected function get_user_all_pts(
        string  $fct,
        array   &$series,
        int     $user_id = 0,
        ?string $object_type = null,
        int     $object_id = 0,
        ?int    $start_date = null,
        ?int    $end_date = null,
        string  $zoom = 'day',
    ): void {
        $userRepository = $this->getUserRepository();

        $values = $this->get_all_pts($fct, $series, $user_id, $object_type, $object_id, $start_date, $end_date, $zoom);
        $ustats = $userRepository->getStatistics();
        // Only display other users if the graph is not for a specific user and user count is small
        if ($user_id < 1 && $ustats['users'] < 10) {
            $userArray = $userRepository->getValidArray();
            foreach ($userArray as $userId => $userName) {
                $user_values = $this->get_all_type_pts(
                    $fct,
                    $userId,
                    $object_type,
                    $object_id,
                    $start_date,
                    $end_date,
                    $zoom
                );
                $points = [];
                foreach (array_keys($values) as $date) {
                    $points[$date] = $user_values[$date] ?? 0;
                }

                $series[$userName] = $points;
            }
        }
    }

    /**
     */
    protected function get_user_bandwidth_pts(
        int    $user_id = 0,
        string $object_type = 'song',
        int    $object_id = 0,
        ?int   $start_date = null,
        ?int   $end_date = null,
        string $zoom = 'day',
    ): array {
        return $this->get_user_object_count_pts($user_id, $object_type, $object_id, $start_date, $end_date, $zoom);
    }

    /**
     */
    protected function get_user_hits_pts(
        int    $user_id = 0,
        string $object_type = 'song',
        int    $object_id = 0,
        ?int   $start_date = null,
        ?int   $end_date = null,
        string $zoom = 'day',
    ): array {
        $end_date ??= time();
        $start_date ??= $end_date - 864000;

        $buckets = $this->get_zoom_buckets($start_date, $end_date, $zoom);
        if ($buckets === []) {
            return [];
        }

        $start_date = $buckets[0];
        $dateformat = $this->get_sql_date_format("`object_count`.`date`", $zoom);
        $where      = $this->get_user_sql_where($user_id, $object_type, $object_id, $start_date, $end_date);
        $sql        = "SELECT " . $dateformat . " AS `zoom_date`, COUNT(`object_count`.`id`) AS `value` FROM `object_count` " . $where . " GROUP BY " . $dateformat;

        return $this->get_bucketed_pts($sql, $buckets);
    }

    /**
     */
    protected function get_user_object_count_pts(
        int    $user_id = 0,
        string $object_type = 'song',
        int    $object_id = 0,
        ?int   $start_date = null,
        ?int   $end_date = null,
        string $zoom = 'day',
        string $column = 'size',
    ): array {
        $end_date ??= time();
        $start_date ??= $end_date - 864000;

        $buckets = $this->get_zoom_buckets($start_date, $end_date, $zoom);
        if ($buckets === []) {
            return [];
        }

        $start_date = $buckets[0];
        $dateformat = $this->get_sql_date_format("`object_count`.`date`", $zoom);
        $where      = $this->get_user_sql_where($user_id, $object_type, $object_id, $start_date, $end_date);
        $sql        = "SELECT " . $dateformat . " AS `zoom_date`, IFNULL(SUM(`" . $object_type . "`.`" . $column . "`), 0) AS `value` FROM `object_count` JOIN `" . $object_type . "` ON `" . $object_type . "`.`id` = `object_count`.`object_id` " . $where . " GROUP BY " . $dateformat;

        return $this->get_bucketed_pts($sql, $buckets);
    }

    /**
     */
    protected function get_user_sql_where(
        int     $user_id = 0,
        ?string $object_type = null,
        int     $object_id = 0,
        ?int    $start_date = null,
        ?int    $end_date = null,
    ): string {
        $start_date = (int) ($start_date);
        $end_date   = (int) ($end_date);
        if ($end_date === 0) {
            $end_date = time();
        }

        if ($start_date === 0) {
            $start_date = $end_date - 864000;
        }

        $sql = "WHERE `object_count`.`date` >= " . $start_date . " AND `object_count`.`date` <= " . $end_date;
        if ($user_id > 0) {
            $sql .= " AND `object_count`.`user` = " . $user_id;
        }

        if (InterfaceImplementationChecker::is_library_item((string) $object_type)) {
            $sql .= " AND `object_count`.`object_type` = '" . $object_type . "'";
            if ($object_id > 0) {
                $sql .= " AND `object_count`.`object_id` = '" . $object_id . "'";
            }
        }

        return $sql;
    }

    /**
     */
    protected function get_user_time_pts(
        int    $user_id = 0,
        string $object_type = 'song',
        int    $object_id = 0,
        ?int   $start_date = null,
        ?int   $end_date = null,
        string $zoom = 'day',
    ): array {
        return $this->get_user_object_count_pts($user_id, $object_type, $object_id, $start_date, $end_date, $zoom, 'time');
    }

    /**
     * Every time bucket between the two dates, oldest first.
     *
     * The catalog graphs are cumulative, so they need a point for every bucket in the range and not
     * just the buckets that happened to gain a file. A library added in one scan is a single bucket,
     * which is why those graphs used to draw a lone point no matter how wide the range was.
     *
     * @return int[]
     */
    protected function get_zoom_buckets(int $start_date, int $end_date, string $zoom): array
    {
        [$truncate, $step] = match ($zoom) {
            'hour' => ['Y-m-d H:00:00', '-1 hour'],
            'year' => ['Y-01-01 00:00:00', '-1 year'],
            'month' => ['Y-m-01 00:00:00', '-1 month'],
            default => ['Y-m-d 00:00:00', '-1 day'],
        };

        $first  = new DateTimeImmutable(date($truncate, $start_date));
        $cursor = new DateTimeImmutable(date($truncate, $end_date));

        // walk back from the end so an oversized range keeps the most recent buckets
        $buckets = [];
        while ($cursor >= $first && count($buckets) < self::MAX_POINTS) {
            $buckets[] = $cursor->getTimestamp();
            $cursor    = $cursor->modify($step);
        }

        return array_reverse($buckets);
    }

    /**
     * Render a multi-series time chart as SVG.
     *
     * @param array<string, array<int, int|float>> $series series name => [unix timestamp => value]
     */
    protected function render_graph(
        string $title,
        array  $series,
        string $format,
        string $label_y,
        string $zoom = 'day',
        int    $width = 0,
        int    $height = 0,
    ): void {
        if ($width <= 50 || $width > 4096) {
            $width = 700;
        }

        if ($height <= 60 || $height > 4096) {
            $height = 260;
        }

        // Each point is a time bucket the SQL already grouped, so the x axis is a list of labelled
        // buckets rather than a continuous scale. Labelling the keys here instead of using a datetime
        // axis keeps one bucket and fifty behaving the same way, and sidesteps the empty range a
        // datetime axis produces when everything falls in the same bucket.
        $series = array_map(
            fn(array $set): array => $this->format_series_keys($set, $zoom),
            $series
        );

        // a line needs two points to join. With a single bucket draw just the markers, so the chart still
        // shows the value and looks like the same chart waiting for its second point rather than a bar.
        $points = 0;
        foreach ($series as $set) {
            $points = max($points, count($set));
        }

        $type = ($points > 1)
            ? 'MultiLineGraph'
            : 'MultiScatterGraph';

        $settings = [
            'graph_title' => $title,
            'label_y' => $label_y,
            // php casts a numeric-looking key like "2026" to an int, which would otherwise be read as a
            // position on a numeric axis instead of a bucket label
            'force_assoc' => true,
            'axis_text_callback_y' => fn($value): string => $this->format_axis_value($value, $format),
            'minimum_grid_spacing_h' => 80,
            'show_legend' => count($series) > 1,
            'legend_entries' => array_keys($series),
            // every 'outside' position draws past the edge of the canvas, SVGGraph does not grow it to fit
            'legend_position' => 'top right',
            'legend_columns' => 2,
            'legend_font_size' => 9,
            'legend_entry_height' => 11,
            'legend_padding' => 4,
            'back_colour' => '#f8f8f8',
            'back_stroke_width' => 1,
            'back_stroke_colour' => '#000',
            'grid_colour' => '#ccc',
            // a lone point sits on the y axis with nothing to give it scale, so draw it bigger
            'marker_size' => ($points > 1) ? 3 : 6,
            'line_stroke_width' => 2,
            'pad_right' => 10,
            // leave the javascript features off, they are the only part of SVGGraph that still emits
            // php deprecations and nothing here needs interactivity
            'show_tooltips' => false,
        ];

        $graph = new SVGGraph($width, $height, $settings);
        $graph->values(array_values($series));

        header('Content-Disposition: filename="ampache-graph.svg"');
        $graph->render($type);
    }

    /**
     * The last bucket that starts at or before the given date, clamped to the first bucket.
     *
     * @param int[] $buckets
     */
    private function find_bucket(array $buckets, int $date): int
    {
        $low  = 0;
        $high = count($buckets) - 1;
        while ($low < $high) {
            $mid = intdiv($low + $high + 1, 2);
            if ($buckets[$mid] <= $date) {
                $low = $mid;
            } else {
                $high = $mid - 1;
            }
        }

        return $buckets[$low];
    }

    /**
     * Format a y-axis value for display
     */
    private function format_axis_value(int|float $value, string $format): string
    {
        if ($format === self::FORMAT_BYTES) {
            // Ui::format_bytes() returns an empty string for zero, but the axis still needs a label
            return ($value == 0)
                ? '0'
                : Ui::format_bytes($value);
        }

        return $this->format_metric_value($value);
    }

    /**
     * Format a count for a y-axis label with a short K/M/B/T suffix, so a busy axis reads as a clear
     * "65M" rather than the "6.5E+7" scientific notation SVGGraph falls back to for large numbers
     */
    private function format_metric_value(int|float $value): string
    {
        $abs = abs($value);
        if ($abs < 1000) {
            return (string) (int) round($value);
        }

        $units  = ['K', 'M', 'B', 'T'];
        $power  = min((int) floor(log($abs, 1000)), count($units));
        $scaled = $value / (1000 ** $power);

        // keep one decimal only when it carries information, so 65M stays 65M while 1.5M keeps its half
        return (fmod($scaled, 1.0) === 0.0)
            ? number_format($scaled) . $units[$power - 1]
            : number_format($scaled, 1) . $units[$power - 1];
    }

    /**
     * Replace unix timestamp keys with a readable bucket label at the requested zoom level
     *
     * @param array<int, int|float> $set
     * @return array<int|string, int|float> php casts a numeric label like "2026" back to an int key
     */
    private function format_series_keys(array $set, string $zoom): array
    {
        $format = match ($zoom) {
            'hour' => 'Y-m-d H:i',
            'year' => 'Y',
            'month' => 'Y-m',
            default => 'Y-m-d',
        };

        $labelled = [];
        foreach ($set as $date => $value) {
            $labelled[date($format, $date)] = $value;
        }

        return $labelled;
    }

    private function getUserRepository(): UserRepositoryInterface
    {
        global $dic;

        return $dic->get(UserRepositoryInterface::class);
    }
}
