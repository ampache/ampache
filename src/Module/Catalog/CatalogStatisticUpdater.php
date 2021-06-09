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
 */

declare(strict_types=1);

namespace Ampache\Module\Catalog;

use Ampache\Config\AmpConfig;
use Ampache\Module\System\Dba;
use Ampache\Module\System\LegacyLogger;
use Ampache\Repository\UpdateInfoRepositoryInterface;
use Psr\Log\LoggerInterface;

final class CatalogStatisticUpdater implements CatalogStatisticUpdaterInterface
{
    private UpdateInfoRepositoryInterface $updateInfoRepository;

    private LoggerInterface $logger;

    public function __construct(
        UpdateInfoRepositoryInterface $updateInfoRepository,
        LoggerInterface $logger
    ) {
        $this->updateInfoRepository = $updateInfoRepository;
        $this->logger               = $logger;
    }

    /**
     * update the artist or album counts on catalog changes
     */
    public function update(string $type = ''): void
    {
        $this->logger->debug(
            'Updating counts after catalog changes',
            [LegacyLogger::CONTEXT_TYPE => __CLASS__]
        );

        // object_count might need some migration help
        if (empty($type)) {
            // object_count.album
            $sql = "UPDATE `object_count`, (SELECT `song_count`.`date`, `song`.`id` as `songid`, `song`.`album`, `album_count`.`object_id` as `albumid`, `album_count`.`user`, `album_count`.`agent`, `album_count`.`count_type` FROM `song` LEFT JOIN `object_count` as `song_count` on `song_count`.`object_type` = 'song' and `song_count`.`count_type` = 'stream' and `song_count`.`object_id` = `song`.`id` LEFT JOIN `object_count` as `album_count` on `album_count`.`object_type` = 'album' and `album_count`.`count_type` = 'stream' and `album_count`.`date` = `song_count`.`date` WHERE `song_count`.`date` IS NOT NULL AND `song`.`album` != `album_count`.`object_id` AND `album_count`.`count_type` = 'stream') AS `album_check` SET `object_count`.`object_id` = `album_check`.`album` WHERE `object_count`.`object_id` != `album_check`.`album` AND `object_count`.`object_type` = 'album' AND `object_count`.`date` = `album_check`.`date` AND `object_count`.`user` = `album_check`.`user` AND `object_count`.`agent` = `album_check`.`agent` AND `object_count`.`count_type` = `album_check`.`count_type`;";
            Dba::write($sql);
            // object_count.artist
            $sql = "UPDATE `object_count`, (SELECT song_count.date, MIN(song.id) as songid, MIN(song.artist) AS artist, `artist_count`.`object_id` as `artistid`, `artist_count`.`user`, `artist_count`.`agent`, `artist_count`.`count_type` FROM song LEFT JOIN `object_count` as `song_count` on `song_count`.`object_type` = 'song' and `song_count`.`count_type` = 'stream' and `song_count`.`object_id` = `song`.`id` LEFT JOIN object_count as `artist_count` on `artist_count`.`object_type` = 'artist' and `artist_count`.`count_type` = 'stream' and `artist_count`.`date` = `song_count`.`date` WHERE `song_count`.`date` IS NOT NULL AND `song`.`artist` != `artist_count`.`object_id` AND `artist_count`.`count_type` = 'stream' GROUP BY `artist_count`.`object_id`, `date`,`user`,`agent`,`count_type`) AS `artist_check` SET `object_count`.`object_id` = `artist_check`.`artist` WHERE `object_count`.`object_id` != `artist_check`.`artist` AND `object_count`.`object_type` = 'artist' AND `object_count`.`date` = `artist_check`.`date` AND `object_count`.`user` = `artist_check`.`user` AND `object_count`.`agent` = `artist_check`.`agent` AND `object_count`.`count_type` = `artist_check`.`count_type`;";
            Dba::write($sql);
            // song.played might have had issues
            $sql = "UPDATE `song` SET `song`.`played` = 0 WHERE `song`.`played` = 1 AND `song`.`id` NOT IN (SELECT `object_id` FROM `object_count` WHERE `object_type` = 'song' AND `count_type` = 'stream');";
            Dba::write($sql);
            $sql = "UPDATE `song` SET `song`.`played` = 1 WHERE `song`.`played` = 0 AND `song`.`id` IN (SELECT `object_id` FROM `object_count` WHERE `object_type` = 'song' AND `count_type` = 'stream');";
            Dba::write($sql);
        }
        // updating an artist
        if (empty($type) || $type == 'artist') {
            // artist.album_count
            $sql = "UPDATE `artist`, (SELECT COUNT(DISTINCT `album`.`id`) AS `album_count`, `album_artist` FROM `album` LEFT JOIN `catalog` ON `catalog`.`id` = `album`.`catalog` WHERE `catalog`.`enabled` = '1' GROUP BY `album_artist`) AS `album` SET `artist`.`album_count` = `album`.`album_count` WHERE `artist`.`album_count` != `album`.`album_count` AND `artist`.`id` = `album`.`album_artist`;";
            Dba::write($sql);
            // artist.album_group_count
            $sql = "UPDATE `artist`, (SELECT COUNT(DISTINCT CONCAT(COALESCE(`album`.`prefix`, ''), `album`.`name`, COALESCE(`album`.`album_artist`, ''), COALESCE(`album`.`mbid`, ''), COALESCE(`album`.`year`, ''))) AS `album_group_count`, `album_artist` FROM `album` LEFT JOIN `catalog` ON `catalog`.`id` = `album`.`catalog` WHERE `catalog`.`enabled` = '1' GROUP BY `album_artist`) AS `album` SET `artist`.`album_group_count` = `album`.`album_group_count` WHERE `artist`.`album_group_count` != `album`.`album_group_count` AND `artist`.`id` = `album`.`album_artist`;";
            Dba::write($sql);
            // artist.song_count
            $sql = "UPDATE `artist`, (SELECT COUNT(`song`.`id`) AS `song_count`, `artist` FROM `song` LEFT JOIN `catalog` ON `catalog`.`id` = `song`.`catalog` WHERE `catalog`.`enabled` = '1' GROUP BY `artist`) AS `song` SET `artist`.`song_count` = `song`.`song_count` WHERE `artist`.`song_count` != `song`.`song_count` AND `artist`.`id` = `song`.`artist`;";
            Dba::write($sql);
            // artist.total_count
            $sql = "UPDATE `artist`, (SELECT COUNT(`object_count`.`object_id`) AS `total_count`, `object_id` FROM `object_count` WHERE `object_count`.`object_type` = 'artist' AND `object_count`.`count_type` = 'stream' GROUP BY `object_count`.`object_id`) AS `object_count` SET `artist`.`total_count` = `object_count`.`total_count` WHERE `artist`.`total_count` != `object_count`.`total_count` AND `artist`.`id` = `object_count`.`object_id`;";
            Dba::write($sql);
            // artist.time
            $sql = "UPDATE `artist`, (SELECT sum(`song`.`time`) as `time`, `song`.`artist` FROM `song` GROUP BY `song`.`artist`) AS `song` SET `artist`.`time` = `song`.`time` WHERE `artist`.`id` = `song`.`artist` AND `artist`.`time` != `song`.`time`;";
            Dba::write($sql);
        }
        // album.time
        $sql = "UPDATE `album`, (SELECT sum(`song`.`time`) as `time`, `song`.`album` FROM `song` GROUP BY `song`.`album`) AS `song` SET `album`.`time` = `song`.`time` WHERE `album`.`id` = `song`.`album` AND `album`.`time` != `song`.`time`;";
        Dba::write($sql);
        // album.addition_time
        $sql = "UPDATE `album`, (SELECT MIN(`song`.`addition_time`) AS `addition_time`, `song`.`album` FROM `song` GROUP BY `song`.`album`) AS `song` SET `album`.`addition_time` = `song`.`addition_time` WHERE `album`.`addition_time` != `song`.`addition_time` AND `song`.`album` = `album`.`id`;";
        Dba::write($sql);
        // album.total_count
        $sql = "UPDATE `album`, (SELECT COUNT(`object_count`.`object_id`) AS `total_count`, `object_id` FROM `object_count` WHERE `object_count`.`object_type` = 'album' AND `object_count`.`count_type` = 'stream' GROUP BY `object_count`.`object_id`) AS `object_count` SET `album`.`total_count` = `object_count`.`total_count` WHERE `album`.`total_count` != `object_count`.`total_count` AND `album`.`id` = `object_count`.`object_id`;";
        Dba::write($sql);
        // album.song_count
        $sql = "UPDATE `album`, (SELECT COUNT(`song`.`id`) AS `song_count`, `album` FROM `song` LEFT JOIN `catalog` ON `catalog`.`id` = `song`.`catalog` WHERE `catalog`.`enabled` = '1' GROUP BY `album`) AS `song` SET `album`.`song_count` = `song`.`song_count` WHERE `album`.`song_count` != `song`.`song_count` AND `album`.`id` = `song`.`album`;";
        Dba::write($sql);
        // album.artist_count
        $sql = "UPDATE `album`, (SELECT COUNT(DISTINCT(`song`.`artist`)) AS `artist_count`, `album` FROM `song` LEFT JOIN `catalog` ON `catalog`.`id` = `song`.`catalog` WHERE `catalog`.`enabled` = '1' GROUP BY `album`) AS `song` SET `album`.`artist_count` = `song`.`artist_count` WHERE `album`.`artist_count` != `song`.`artist_count` AND `album`.`id` = `song`.`album`;";
        Dba::write($sql);

        // update server total counts
        $catalog_disable = AmpConfig::get('catalog_disable');
        // tables with media items to count, song-related tables and the rest
        $media_tables = array('song', 'video', 'podcast_episode');
        $items        = 0;
        $time         = 0;
        $size         = 0;
        foreach ($media_tables as $table) {
            $enabled_sql = ($catalog_disable && $table !== 'podcast_episode') ? " WHERE `$table`.`enabled`='1'" : '';
            $sql         = "SELECT COUNT(`id`), IFNULL(SUM(`time`), 0), IFNULL(SUM(`size`), 0) FROM `$table`" . $enabled_sql;
            $db_results  = Dba::read($sql);
            $data        = Dba::fetch_row($db_results);
            // save the object and add to the current size
            $items += $data[0];
            $time += $data[1];
            $size += $data[2];

            $this->updateInfoRepository->setCount($table, (int) $data[0]);
        }
        $this->updateInfoRepository->setCount('items', (int) $items);
        $this->updateInfoRepository->setCount('time', (int) $time);
        $this->updateInfoRepository->setCount('size', (int) $size);

        $song_tables = array('artist', 'album');
        foreach ($song_tables as $table) {
            $sql        = "SELECT COUNT(DISTINCT(`$table`)) FROM `song`";
            $db_results = Dba::read($sql);
            $data       = Dba::fetch_row($db_results);

            $this->updateInfoRepository->setCount($table, (int) $data[0]);
        }
        // grouped album counts
        $sql        = "SELECT COUNT(DISTINCT(`album`.`id`)) AS `count` FROM `album` WHERE `id` in (SELECT MIN(`id`) from `album` GROUP BY `album`.`prefix`, `album`.`name`, `album`.`album_artist`, `album`.`release_type`, `album`.`release_status`, `album`.`mbid`, `album`.`year`);";
        $db_results = Dba::read($sql);
        $data       = Dba::fetch_row($db_results);

        $this->updateInfoRepository->setCount('album_group', (int) $data[0]);

        $list_tables = array('search', 'playlist', 'live_stream', 'podcast', 'user', 'catalog', 'label', 'tag', 'share', 'license');
        foreach ($list_tables as $table) {
            $sql        = "SELECT COUNT(`id`) FROM `$table`";
            $db_results = Dba::read($sql);
            $data       = Dba::fetch_row($db_results);

            $this->updateInfoRepository->setCount($table, (int) $data[0]);
        }
    }
}
