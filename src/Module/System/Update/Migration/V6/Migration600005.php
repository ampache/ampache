<?php

declare(strict_types=1);

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
 */

namespace Ampache\Module\System\Update\Migration\V6;

use Ampache\Module\Database\DatabaseConnectionInterface;
use Ampache\Module\System\Dba;
use Ampache\Module\System\LegacyLogger;
use Ampache\Module\System\Update\Migration\AbstractMigration;
use Ampache\Repository\Model\Album;
use Ampache\Repository\Model\Artist;
use Ampache\Repository\Model\Catalog;
use Ampache\Repository\Model\Song;
use Psr\Log\LoggerInterface;

/**
 * Migrate multi-disk albums to single album id's
 */
final class Migration600005 extends AbstractMigration
{
    protected array $changelog = ['Migrate multi-disk albums to single album id\'s'];

    protected bool $warning = true;

    private LoggerInterface $logger;

    public function __construct(
        LoggerInterface $logger,
        DatabaseConnectionInterface $connection
    ) {
        parent::__construct(
            $connection
        );

        $this->logger = $logger;
    }

    public function migrate(): void
    {
        $sql        = "SELECT MIN(`id`) AS `id` FROM `album` GROUP BY `album`.`prefix`, `album`.`name`, `album`.`album_artist`, `album`.`release_type`, `album`.`release_status`, `album`.`mbid`, `album`.`year`, `album`.`original_year`, `album`.`mbid_group` HAVING COUNT(`id`) > 1;";
        $db_results = Dba::read($sql);
        $album_list = [];
        $migrate    = [];
        // get the base album you will migrate into
        while ($row = Dba::fetch_assoc($db_results)) {
            $album_list[] = $row['id'];
        }
        // get all matching albums that will migrate into the base albums
        foreach ($album_list as $album_id) {
            $album  = new Album((int)$album_id);
            $f_name = trim(trim($album->prefix ?? '') . ' ' . trim($album->name ?? ''));
            $where  = " WHERE (`album`.`name` = ? OR LTRIM(CONCAT(COALESCE(`album`.`prefix`, ''), ' ', `album`.`name`)) = ? ) ";
            $params = [$f_name, $f_name];
            if ($album->mbid) {
                $where .= 'AND `album`.`mbid` = ? ';
                $params[] = $album->mbid;
            } else {
                $where .= 'AND `album`.`mbid` IS NULL ';
            }
            if ($album->mbid_group) {
                $where .= 'AND `album`.`mbid_group` = ? ';
                $params[] = $album->mbid_group;
            } else {
                $where .= 'AND `album`.`mbid_group` IS NULL ';
            }
            if ($album->prefix) {
                $where .= 'AND `album`.`prefix` = ? ';
                $params[] = $album->prefix;
            }
            if ($album->album_artist) {
                $where .= 'AND `album`.`album_artist` = ? ';
                $params[] = $album->album_artist;
            }
            if ($album->original_year) {
                $where .= 'AND `album`.`original_year` = ? ';
                $params[] = $album->original_year;
            }
            if ($album->release_type) {
                $where .= 'AND `album`.`release_type` = ? ';
                $params[] = $album->release_type;
            }
            if ($album->release_status) {
                $where .= 'AND `album`.`release_status` = ? ';
                $params[] = $album->release_status;
            }

            $sql        = "SELECT DISTINCT `album`.`id`, MAX(`album`.`disk`) AS `disk` FROM `album` LEFT JOIN `song` ON `song`.`album`=`album`.`id` $where GROUP BY `album`.`id` ORDER BY `disk` ASC";
            $db_results = Dba::read($sql, $params);

            while ($row = Dba::fetch_assoc($db_results)) {
                if ($row['id'] !== $album_id) {
                    $migrate[] = [
                        'old' => $row['id'],
                        'new' => $album_id
                    ];
                }
            }
        }

        $this->logger->notice(
            'update_600005: migrate {' . count($migrate) . '} albums',
            [LegacyLogger::CONTEXT_TYPE => self::class]
        );

        // get the songs for these id's and migrate to the base id
        foreach ($migrate as $albums) {
            $this->logger->notice(
                'update_600005: migrate album: ' . $albums['old'] . ' => ' . $albums['new'],
                [LegacyLogger::CONTEXT_TYPE => self::class]
            );

            $sql = "UPDATE `song` SET `album` = ? WHERE `album` = ?;";
            $this->updateDatabase($sql, [$albums['new'], $albums['old']]);

            // bulk migrate by album only (0 will let us migrate everything below)
            Song::migrate_album($albums['new'], 0, $albums['old']);
        }
        // check that the migration is finished
        $sql        = "SELECT MAX(`id`) AS `id` FROM `album` WHERE `id` IN (SELECT `album` FROM `song`) GROUP BY `album`.`prefix`, `album`.`name`, `album`.`album_artist`, `album`.`release_type`, `album`.`release_status`, `album`.`mbid`, `album`.`year`, `album`.`original_year`, `album`.`mbid_group` HAVING COUNT(`id`) > 1;";
        $db_results = Dba::read($sql);
        if (Dba::fetch_assoc($db_results)) {
            $this->logger->emergency(
                'update_600005: FAIL',
                [LegacyLogger::CONTEXT_TYPE => self::class]
            );

            return;
        }
        // clean up this mess
        Catalog::clean_empty_albums();
        Song::clear_cache();
        Artist::clear_cache();
        Album::clear_cache();

        $this->logger->debug(
            'update_600005: SUCCESS',
            [LegacyLogger::CONTEXT_TYPE => self::class]
        );
    }
}
