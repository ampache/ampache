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

namespace Ampache\Module\Album\Deletion;

use Ampache\Module\Album\Deletion\Exception\AlbumDeletionException;
use Ampache\Module\Art\ArtCleanupInterface;
use Ampache\Module\Catalog\CatalogCounterInterface;
use Ampache\Module\Catalog\CountableTableEnum;
use Ampache\Module\Statistics\Rating;
use Ampache\Module\Statistics\Userflag;
use Ampache\Module\System\LegacyLogger;
use Ampache\Repository\AlbumRepositoryInterface;
use Ampache\Repository\FolderRepositoryInterface;
use Ampache\Repository\Model\Album;
use Ampache\Repository\ShoutRepositoryInterface;
use Ampache\Repository\SongRepositoryInterface;
use Ampache\Repository\UserActivityRepositoryInterface;
use Psr\Log\LoggerInterface;

/**
 * Deletes an album including all songs
 */
final readonly class AlbumDeleter implements AlbumDeleterInterface
{
    public function __construct(
        private AlbumRepositoryInterface $albumRepository,
        private LoggerInterface $logger,
        private SongRepositoryInterface $songRepository,
        private ShoutRepositoryInterface $shoutRepository,
        private UserActivityRepositoryInterface $userActivityRepository,
        private ArtCleanupInterface $artCleanup,
        private FolderRepositoryInterface $folderRepository,
        private CatalogCounterInterface $catalogCounter,
    ) {}

    /**
     * @throws AlbumDeletionException
     */
    public function delete(
        Album $album,
        bool $parent = false,
    ): void {
        $albumId = $album->getId();
        $songIds = $this->songRepository->getByAlbum($albumId);

        // one query for every file path and folder stat instead of hydrating a Song model per id
        $songStats = [];
        foreach ($this->songRepository->getRowsByIds($songIds, false) as $row) {
            $file = $row['file'];
            if (!in_array($file, [null, '', '0'], true) && file_exists($file) && !unlink($file)) {
                $this->logger->critical(
                    sprintf(
                        'Error when deleting the song `%d`. Please check permissions.',
                        $row['id']
                    ),
                    [LegacyLogger::CONTEXT_TYPE => self::class]
                );

                throw new AlbumDeletionException();
            }

            $songStats[(int) $row['id']] = [
                'total_count' => (int) $row['total_count'],
                'total_skip' => (int) $row['total_skip'],
                'time' => (int) $row['time'],
            ];
        }

        $this->songRepository->deleteByIdsWithArchive($songIds);
        $this->artCleanup->collectGarbageForObjects('song', $songIds);

        $this->albumRepository->delete($album);

        $this->artCleanup->collectGarbageForObject('album', $albumId);

        if (!$parent) {
            // every song in this album is gone, so clean up their map table rows
            $this->songRepository->collectGarbageForSongs($songIds);
            $this->albumRepository->collectGarbageForAlbums([$albumId]);
            $this->folderRepository->collectGarbageForObjects('song', $songStats);

            // collect song garbage once
            Userflag::garbage_collection('song');
            Rating::garbage_collection('song');
            $this->shoutRepository->collectGarbage('song');
            $this->userActivityRepository->collectGarbage('song');

            Userflag::garbage_collection('album', $albumId);
            Rating::garbage_collection('album', $albumId);
            $this->shoutRepository->collectGarbage('album', $albumId);
            $this->userActivityRepository->collectGarbage('album', $albumId);

            $this->catalogCounter->count(CountableTableEnum::ALBUM);
            $this->catalogCounter->count(CountableTableEnum::ALBUM_DISK);
            $this->catalogCounter->count(CountableTableEnum::SONG);
        }
    }
}
