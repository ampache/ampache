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
 *
 */

declare(strict_types=0);

namespace Ampache\Module\Artist;

use Ampache\Module\System\Dba;
use Ampache\Module\System\LegacyLogger;
use Ampache\Module\Util\VaInfo;
use Ampache\Repository\CatalogRepositoryInterface;
use Ampache\Repository\Model\Catalog;
use Psr\Log\LoggerInterface;

final class ArtistFinder implements ArtistFinderInterface
{
    private LoggerInterface $logger;

    private CatalogRepositoryInterface $catalogRepository;

    public function __construct(
        LoggerInterface $logger,
        CatalogRepositoryInterface $catalogRepository
    ) {
        $this->logger            = $logger;
        $this->catalogRepository = $catalogRepository;
    }

    /**
     * Looks for an existing artist; if none exists, insert one.
     */
    public function find(string $name, ?string $mbid = '', bool $readonly = false): ?int
    {
        $trimmed = Catalog::trim_prefix(trim((string)$name));
        $name    = $trimmed['string'];
        $prefix  = $trimmed['prefix'];
        // If Ampache support multiple artists per song one day, we should also handle other artists here
        $trimmed = Catalog::trim_featuring($name);
        $name    = $trimmed[0];

        // If Ampache support multiple artists per song one day, we should also handle other artists here
        $mbid = Catalog::trim_slashed_list($mbid);

        if (!$name) {
            $name   = T_('Unknown (Orphaned)');
            $prefix = null;
        }
        if ($name == 'Various Artists') {
            $mbid = '';
        }

        $artist_id = 0;
        $exists    = false;
        $matches   = [];

        // check for artists by mbid and split-mbid
        if ($mbid !== '') {
            $sql     = 'SELECT `id` FROM `artist` WHERE `mbid` = ?';
            $matches = VaInfo::get_mbid_array($mbid);
            foreach ($matches as $mbid_string) {
                $db_results = Dba::read($sql, array($mbid_string));

                if (!$exists) {
                    $row       = Dba::fetch_assoc($db_results);
                    $artist_id = (int)$row['id'];
                    $exists    = ($artist_id > 0);
                    $mbid      = ($exists)
                        ? $mbid_string
                        : $mbid;
                }
            }
            // try the whole string if it didn't work
            if (!$exists) {
                $db_results = Dba::read($sql, array($mbid));

                if ($row = Dba::fetch_assoc($db_results)) {
                    $artist_id = (int)$row['id'];
                    $exists    = ($artist_id > 0);
                }
            }
        }
        // search by the artist name and build an array
        if (!$exists) {
            $sql        = 'SELECT `id`, `mbid` FROM `artist` WHERE `name` LIKE ?';
            $db_results = Dba::read($sql, array($name));
            $id_array   = array();
            while ($row = Dba::fetch_assoc($db_results)) {
                $key            = $row['mbid'] ?: 'null';
                $id_array[$key] = $row['id'];
            }
            if (count($id_array)) {
                if ($mbid !== '') {
                    $matches = VaInfo::get_mbid_array($mbid);
                    foreach ($matches as $mbid_string) {
                        // reverse search artist id if it's still not found for some reason
                        if (isset($id_array[$mbid_string]) && !$exists) {
                            $artist_id = (int)$id_array[$mbid_string];
                            $exists    = ($artist_id > 0);
                            $mbid      = ($exists)
                                ? $mbid_string
                                : $mbid;
                        }
                        // update empty artists that match names
                        if (isset($id_array['null']) && !$readonly) {
                            $sql = 'UPDATE `artist` SET `mbid` = ? WHERE `id` = ?';
                            Dba::write($sql, array($mbid_string, $id_array['null']));
                        }
                    }
                    if (isset($id_array['null'])) {
                        if (!$readonly) {
                            $sql = 'UPDATE `artist` SET `mbid` = ? WHERE `id` = ?';
                            Dba::write($sql, array($mbid, $id_array['null']));
                        }
                        $artist_id = (int)$id_array['null'];
                        $exists    = true;
                    }
                } else {
                    // Pick one at random
                    $artist_id = array_shift($id_array);
                    $exists    = true;
                }
            }
        }
        // cache and return the result
        if ($exists) {
            return (int)$artist_id;
        }
        // if all else fails, insert a new artist, cache it and return the id
        $sql  = 'INSERT INTO `artist` (`name`, `prefix`, `mbid`) ' . 'VALUES(?, ?, ?)';
        $mbid = (!empty($matches)) ? $matches[0] : $mbid; // TODO only use primary mbid until multi-artist is ready

        $db_results = Dba::write($sql, array($name, $prefix, $mbid));
        if (!$db_results) {
            return null;
        }

        $artist_id = (int) Dba::insert_id();
        $this->logger->info(
            sprintf('Artist check created new artist id `%d`.', $artist_id),
            [LegacyLogger::CONTEXT_TYPE => __CLASS__]
        );

        // map the new id
        $this->catalogRepository->updateMapping(0, 'artist', $artist_id);

        return $artist_id;
    }
}
