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

namespace Ampache\Module\Album;

use Ampache\Module\System\Dba;
use Ampache\Module\System\LegacyLogger;
use Ampache\Repository\Model\Album;
use Psr\Log\LoggerInterface;

final class AlbumArtistUpdater implements AlbumArtistUpdaterInterface
{
    private LoggerInterface $logger;

    public function __construct(
        LoggerInterface $logger
    ) {
        $this->logger = $logger;
    }

    /**
     * find albums that are missing an album_artist and generate one.
     *
     * @param int[] $albumIds
     */
    public function update(array $albumIds = []): void
    {
        $results = $albumIds;
        if (empty($results)) {
            // Find all albums that are missing an album artist
            $sql        = "SELECT `id` FROM `album` WHERE `album_artist` IS NULL AND `name` != 'Unknown (Orphaned)'";
            $db_results = Dba::read($sql);
            while ($row = Dba::fetch_assoc($db_results)) {
                $results[] = (int) $row['id'];
            }
        }
        foreach ($results as $album_id) {
            $artists    = [];
            $sql        = 'SELECT `artist` FROM `song` WHERE `album` = ? GROUP BY `artist` HAVING COUNT(DISTINCT `artist`) = 1 LIMIT 1';
            $db_results = Dba::read($sql, array($album_id));

            // these are albums that only have 1 artist
            while ($row = Dba::fetch_assoc($db_results)) {
                $artists[] = (int) $row['artist'];
            }

            // if there isn't a distinct artist, sort by the count with another fall back to id order
            if (empty($artists)) {
                $sql        = 'SELECT `artist` FROM `song` WHERE `album` = ? GROUP BY `artist`, `id` ORDER BY COUNT(`id`) DESC, `id` ASC LIMIT 1';
                $db_results = Dba::read($sql, array($album_id));

                // these are album pick the artist by majority count
                while ($row = Dba::fetch_assoc($db_results)) {
                    $artists[] = (int) $row['artist'];
                }
            }
            // Update the album
            if (!empty($artists)) {
                $this->logger->debug(
                    'Found album_artist {' . $artists[0] . '} for: ' . $album_id,
                    [LegacyLogger::class => __CLASS__]
                );
                Album::update_field('album_artist', $artists[0], $album_id);
            }
        }
    }
}
