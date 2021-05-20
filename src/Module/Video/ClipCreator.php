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

declare(strict_types=0);

namespace Ampache\Module\Video;

use Ampache\Module\Artist\ArtistFinderInterface;
use Ampache\Module\System\Dba;
use Ampache\Repository\Model\Catalog;
use Ampache\Repository\SongRepositoryInterface;

final class ClipCreator implements ClipCreatorInterface
{
    private SongRepositoryInterface $songRepository;

    private ArtistFinderInterface $artistFinder;

    public function __construct(
        SongRepositoryInterface $songRepository,
        ArtistFinderInterface $artistFinder
    ) {
        $this->songRepository = $songRepository;
        $this->artistFinder   = $artistFinder;
    }

    /**
     * This takes a key'd array of data as input and inserts a new clip entry, it returns the record id
     *
     * @param array<string, mixed> $data
     */
    public function create(array $data): int
    {
        debug_event(self::class, 'insert ' . print_r($data,true) , 5);
        $artist_id = $this->getArtistId($data);
        $song_id   = $this->songRepository->findBy($data);
        if (empty($song_id)) {
            $song_id = null;
        }
        if ($artist_id || $song_id) {
            debug_event(__CLASS__, 'insert ' . print_r(['artist_id' => $artist_id, 'song_id' => $song_id], true), 5);
            $sql = "INSERT INTO `clip` (`id`, `artist`, `song`) VALUES (?, ?, ?)";

            Dba::write($sql, array($data['id'], $artist_id, $song_id));
        }

        return (int) $data['id'];
    }

    /**
     * Look-up an artist id from artist tag data... creates one if it doesn't exist already
     *
     * @param array<string, mixed> $data
     */
    private function getArtistId(array $data): ?int
    {
        if (isset($data['artist_id']) && !empty($data['artist_id'])) {
            return $data['artist_id'];
        }
        if (!isset($data['artist']) || empty($data['artist'])) {
            return null;
        }
        $artist_mbid = isset($data['mbid_artistid']) ? $data['mbid_artistid'] : null;
        if ($artist_mbid) {
            $artist_mbid = Catalog::trim_slashed_list($artist_mbid);
        }
        /** @var null|string $artist_mbid */

        return $this->artistFinder->find($data['artist'], $artist_mbid);
    }
}
