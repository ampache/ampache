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

use Ampache\Module\System\LegacyLogger;
use Ampache\Repository\AlbumRepositoryInterface;
use Ampache\Repository\Model\Album;
use Ampache\Repository\SongRepositoryInterface;
use Psr\Log\LoggerInterface;

final class AlbumArtistUpdater implements AlbumArtistUpdaterInterface
{
    private LoggerInterface $logger;

    private AlbumRepositoryInterface $albumRepository;

    private SongRepositoryInterface $songRepository;

    public function __construct(
        LoggerInterface $logger,
        AlbumRepositoryInterface $albumRepository,
        SongRepositoryInterface $songRepository
    ) {
        $this->logger          = $logger;
        $this->albumRepository = $albumRepository;
        $this->songRepository  = $songRepository;
    }

    /**
     * find albums that are missing an album_artist and generate one.
     */
    public function update(): void
    {
        $albumIds = $this->albumRepository->getHavingEmptyAlbumArtist();

        foreach ($albumIds as $albumId) {
            $artistId = $this->songRepository->findArtistByAlbum($albumId);

            // Update the album
            if ($artistId !== null) {
                $this->logger->debug(
                    'Found album_artist {' . $artistId . '} for: ' . $albumId,
                    [LegacyLogger::class => __CLASS__]
                );
                Album::update_field('album_artist', $artistId, $albumId);
            }
        }
    }
}
