
    /**
     * Recomputes the cached totals on one artist, after something mapped to it changed
     */
    public function updateCounts(int $artistId): void
    {
        $statements = [
            // artist.time
            ["UPDATE `artist`, (SELECT SUM(`song`.`time`) AS `time`, `artist_map`.`artist_id` FROM `song` LEFT JOIN `artist_map` ON `song`.`id` = `artist_map`.`object_id` AND `artist_map`.`object_type` = 'song' WHERE `artist_map`.`artist_id` = ? GROUP BY `artist_map`.`artist_id`) AS `song` SET `artist`.`time` = `song`.`time` WHERE (`artist`.`time` IS NULL OR `artist`.`time` != `song`.`time`) AND `artist`.`id` = `song`.`artist_id`;", [$artistId]],
            // artist.total_count
            ["UPDATE `artist`, (SELECT SUM(`total`) AS `total_count`, `object_id` FROM (SELECT COUNT(`object_count`.`object_id`) AS `total`, `object_id` FROM `object_count` WHERE `object_count`.`object_id` = ? AND `object_count`.`object_type` = 'artist' AND `object_count`.`count_type` = 'stream' GROUP BY `object_count`.`object_id` UNION ALL SELECT `count` AS `total`, `object_id` FROM `object_count_summary` WHERE `object_id` = ? AND `object_type` = 'artist' AND `count_type` = 'stream') AS `combined_count` GROUP BY `object_id`) AS `object_count` SET `artist`.`total_count` = `object_count`.`total_count` WHERE `artist`.`total_count` != `object_count`.`total_count` AND `artist`.`id` = `object_count`.`object_id`;", [$artistId, $artistId]],
            // object count = 0
            ["UPDATE `artist`, (SELECT 0 AS `total_count`, `artist`.`id` FROM `artist` WHERE `id` = ? AND `id` NOT IN (SELECT `object_id` FROM `object_count` WHERE `object_count`.`object_id` = ? AND `object_type` = 'artist' AND `count_type` = 'stream' UNION SELECT `object_id` FROM `object_count_summary` WHERE `object_id` = ? AND `object_type` = 'artist' AND `count_type` = 'stream')) AS `object_count` SET `artist`.`total_count` = `object_count`.`total_count` WHERE `artist`.`total_count` != `object_count`.`total_count` AND `artist`.`id` = `object_count`.`id`;", [$artistId, $artistId, $artistId]],
            // artist.album_count
            ["UPDATE `artist`, (SELECT COUNT(DISTINCT `album`.`id`) AS `album_count`, `artist_map`.`artist_id` FROM `artist_map` LEFT JOIN `album` ON `album`.`id` = `artist_map`.`object_id` AND `artist_map`.`object_type` = 'album' LEFT JOIN `catalog` ON `catalog`.`id` = `album`.`catalog` WHERE `artist_map`.`artist_id` = ? AND `catalog`.`enabled` = '1' GROUP BY `artist_map`.`artist_id`) AS `album` SET `artist`.`album_count` = `album`.`album_count` WHERE `artist`.`album_count` != `album`.`album_count` AND `artist`.`id` = `album`.`artist_id`;", [$artistId]],
            // artist.album_disk_count
            ["UPDATE `artist`, (SELECT COUNT(DISTINCT `album_disk`.`id`) AS `album_disk_count`, `artist_map`.`artist_id` FROM `artist_map` LEFT JOIN `album` ON `album`.`id` = `artist_map`.`object_id` AND `artist_map`.`object_type` = 'album' LEFT JOIN `album_disk` ON `album_disk`.`album_id` = `album`.`id` LEFT JOIN `catalog` ON `catalog`.`id` = `album`.`catalog` WHERE `artist_map`.`artist_id` = ? AND `catalog`.`enabled` = '1' GROUP BY `artist_map`.`artist_id`) AS `album_disk` SET `artist`.`album_disk_count` = `album_disk`.`album_disk_count` WHERE `artist`.`album_disk_count` != `album_disk`.`album_disk_count` AND `artist`.`id` = `album_disk`.`artist_id`;", [$artistId]],
            // empty artist.album_count and artist.album_disk_count
            ["UPDATE `artist` SET `album_count` = 0, `album_disk_count` = 0 WHERE `artist`.`id` = ? AND (`album_count` > 0 OR `album_disk_count` > 0) AND `id` NOT IN (SELECT `artist_id` FROM `artist_map` WHERE `object_type` = 'album');", [$artistId]],
            // artist.song_count
            ["UPDATE `artist`, (SELECT COUNT(`song`.`id`) AS `song_count`, `artist_map`.`artist_id` FROM `artist_map` LEFT JOIN `song` ON `song`.`id` = `artist_map`.`object_id` AND `artist_map`.`object_type` = 'song' LEFT JOIN `catalog` ON `catalog`.`id` = `song`.`catalog` WHERE `artist_map`.`artist_id` = ? AND `catalog`.`enabled` = '1' GROUP BY `artist_map`.`artist_id`) AS `song` SET `artist`.`song_count` = `song`.`song_count` WHERE `artist`.`song_count` != `song`.`song_count` AND `artist`.`id` = `song`.`artist_id`;", [$artistId]],
            // empty artist.song_count
            ["UPDATE `artist` SET `song_count` = 0 WHERE `id` = ? AND `song_count` > 0 AND `id` NOT IN (SELECT `artist_id` FROM `artist_map` WHERE `object_type` = 'song');", [$artistId]],
        ];

        foreach ($statements as [$sql, $params]) {
            $this->runMaintenance($sql, $params);
        }
    }

    /**
     * Recomputes the cached totals on every artist
     */
    public function updateAllCounts(): void
    {
        $statements = [
            // artist.time
            "UPDATE `artist`, (SELECT SUM(`song`.`time`) AS `time`, `artist_map`.`artist_id` FROM `song` LEFT JOIN `artist_map` ON `song`.`id` = `artist_map`.`object_id` AND `artist_map`.`object_type` = 'song' GROUP BY `artist_map`.`artist_id`) AS `song` SET `artist`.`time` = `song`.`time` WHERE (`artist`.`time` IS NULL OR `artist`.`time` != `song`.`time`) AND `artist`.`id` = `song`.`artist_id`;",
            // artist.total_count
            "UPDATE `artist`, (SELECT SUM(`total`) AS `total_count`, `object_id` FROM (SELECT COUNT(`object_count`.`object_id`) AS `total`, `object_id` FROM `object_count` WHERE `object_count`.`object_type` = 'artist' AND `object_count`.`count_type` = 'stream' GROUP BY `object_count`.`object_id` UNION ALL SELECT `count` AS `total`, `object_id` FROM `object_count_summary` WHERE `object_type` = 'artist' AND `count_type` = 'stream') AS `combined_count` GROUP BY `object_id`) AS `object_count` SET `artist`.`total_count` = `object_count`.`total_count` WHERE `artist`.`total_count` != `object_count`.`total_count` AND `artist`.`id` = `object_count`.`object_id`;",
            // object count = 0
            "UPDATE `artist`, (SELECT 0 AS `total_count`, `artist`.`id` FROM `artist` WHERE `id` NOT IN (SELECT `object_id` FROM `object_count` WHERE `object_type` = 'artist' AND `count_type` = 'stream' UNION SELECT `object_id` FROM `object_count_summary` WHERE `object_type` = 'artist' AND `count_type` = 'stream')) AS `object_count` SET `artist`.`total_count` = `object_count`.`total_count` WHERE `artist`.`total_count` != `object_count`.`total_count` AND `artist`.`id` = `object_count`.`id`;",
            // artist.album_count
            "UPDATE `artist`, (SELECT COUNT(DISTINCT `album`.`id`) AS `album_count`, `artist_map`.`artist_id` FROM `artist_map` LEFT JOIN `album` ON `album`.`id` = `artist_map`.`object_id` AND `artist_map`.`object_type` = 'album' LEFT JOIN `catalog` ON `catalog`.`id` = `album`.`catalog` WHERE `catalog`.`enabled` = '1' GROUP BY `artist_map`.`artist_id`) AS `album` SET `artist`.`album_count` = `album`.`album_count` WHERE `artist`.`album_count` != `album`.`album_count` AND `artist`.`id` = `album`.`artist_id`;",
            // artist.album_disk_count
            "UPDATE `artist`, (SELECT COUNT(DISTINCT `album_disk`.`id`) AS `album_disk_count`, `artist_map`.`artist_id` FROM `artist_map` LEFT JOIN `album` ON `album`.`id` = `artist_map`.`object_id` AND `artist_map`.`object_type` = 'album' LEFT JOIN `album_disk` ON `album_disk`.`album_id` = `album`.`id` LEFT JOIN `catalog` ON `catalog`.`id` = `album`.`catalog` WHERE `catalog`.`enabled` = '1' GROUP BY `artist_map`.`artist_id`) AS `album_disk` SET `artist`.`album_disk_count` = `album_disk`.`album_disk_count` WHERE `artist`.`album_disk_count` != `album_disk`.`album_disk_count` AND `artist`.`id` = `album_disk`.`artist_id`;",
            // empty artist.album_count and artist.album_disk_count
            "UPDATE `artist` SET `album_count` = 0, `album_disk_count` = 0 WHERE (`album_count` > 0 OR `album_disk_count` > 0) AND `id` NOT IN (SELECT `artist_id` FROM `artist_map` WHERE `object_type` = 'album');",
            // artist.song_count
            "UPDATE `artist`, (SELECT COUNT(`song`.`id`) AS `song_count`, `artist_map`.`artist_id` FROM `artist_map` LEFT JOIN `song` ON `song`.`id` = `artist_map`.`object_id` AND `artist_map`.`object_type` = 'song' LEFT JOIN `catalog` ON `catalog`.`id` = `song`.`catalog` WHERE `catalog`.`enabled` = '1' GROUP BY `artist_map`.`artist_id`) AS `song` SET `artist`.`song_count` = `song`.`song_count` WHERE `artist`.`song_count` != `song`.`song_count` AND `artist`.`id` = `song`.`artist_id`;",
            // empty artist.song_count
            "UPDATE `artist` SET `song_count` = 0 WHERE `song_count` > 0 AND `id` NOT IN (SELECT `artist_id` FROM `artist_map` WHERE `object_type` = 'song');",
        ];

        foreach ($statements as $sql) {
            $this->runMaintenance($sql);
        }
    }

    /**
     * Runs one count-maintenance statement, where a failure must not take the rest of the sweep down with it
     *
     * @param list<mixed> $params
     */
    private function runMaintenance(string $sql, array $params = []): void
    {
        try {
            $this->connection->query($sql, $params);
        } catch (DatabaseException) {
            debug_event(self::class, 'count maintenance failed: ' . $sql, 3);
        }
    }
