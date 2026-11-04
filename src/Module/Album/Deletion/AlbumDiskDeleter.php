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
use Ampache\Module\Album\Deletion\Exception\AlbumDiskDeletionException;
use Ampache\Module\Catalog\CatalogCounterInterface;
use Ampache\Module\Catalog\CountableTableEnum;
use Ampache\Module\Song\Deletion\SongDeleterInterface;
use Ampache\Module\Statistics\Rating;
use Ampache\Module\Statistics\Userflag;
use Ampache\Module\System\LegacyLogger;
use Ampache\Repository\AlbumDiskRepositoryInterface;
use Ampache\Repository\AlbumRepositoryInterface;
use Ampache\Repository\FolderRepositoryInterface;
use Ampache\Repository\Model\AlbumDisk;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\ShoutRepositoryInterface;
use Ampache\Repository\SongRepositoryInterface;
use Ampache\Repository\UserActivityRepositoryInterface;
use Psr\Log\LoggerInterface;

/**
 * Deletes one disk of an album, including its songs, leaving the rest of the album untouched
 */
final readonly class AlbumDiskDeleter implements AlbumDiskDeleterInterface
{
    public function __construct(
        private AlbumDeleterInterface $albumDeleter,
        private AlbumDiskRepositoryInterface $albumDiskRepository,
        private AlbumRepositoryInterface $albumRepository,
        private ModelFactoryInterface $modelFactory,
        private LoggerInterface $logger,
        private SongRepositoryInterface $songRepository,
        private ShoutRepositoryInterface $shoutRepository,
        private SongDeleterInterface $songDeleter,
        private UserActivityRepositoryInterface $userActivityRepository,
        private FolderRepositoryInterface $folderRepository,
        private CatalogCounterInterface $catalogCounter,
    ) {}

    /**
     * @throws AlbumDiskDeletionException
     */
    public function delete(AlbumDisk $albumDisk): bool
    {
        $albumDiskId = $albumDisk->getId();
        $albumId     = $albumDisk->getAlbumId();

        $songIds = $this->songRepository->getByAlbumDisk($albumDiskId);
        foreach ($songIds as $songId) {
            $song    = $this->modelFactory->createSong($songId);
            $deleted = $this->songDeleter->delete($song, true);
            if (!$deleted) {
                $this->logger->critical(
                    sprintf(
                        'Error when deleting the song `%d`.',
                        $songId
                    ),
                    [LegacyLogger::CONTEXT_TYPE => self::class]
                );

                throw new AlbumDiskDeletionException();
            }
        }

        $this->albumDiskRepository->delete($albumDisk);

        // every song on this disk is gone, so clean up their map table rows
        $this->songRepository->collectGarbageForSongs($songIds);
        $this->folderRepository->collectGarbage();
        Userflag::garbage_collection('song');
        Rating::garbage_collection('song');
        $this->shoutRepository->collectGarbage('song');
        $this->userActivityRepository->collectGarbage('song');

        Userflag::garbage_collection('album_disk', $albumDiskId);
        Rating::garbage_collection('album_disk', $albumDiskId);
        $this->shoutRepository->collectGarbage('album_disk', $albumDiskId);
        $this->userActivityRepository->collectGarbage('album_disk', $albumDiskId);

        $album = $this->modelFactory->createAlbum($albumId);

        // no disk left on the album means no song left either, so the empty shell goes with it
        if ($this->albumDiskRepository->getByAlbum($album) === []) {
            try {
                $this->albumDeleter->delete($album);
            } catch (AlbumDeletionException) {
                $this->logger->critical(
                    sprintf(
                        'Error when deleting the now-empty album `%d`.',
                        $albumId
                    ),
                    [LegacyLogger::CONTEXT_TYPE => self::class]
                );

                throw new AlbumDiskDeletionException();
            }

            return true;
        }

        // the album survives with fewer songs, so its cached totals need to catch up
        $this->albumRepository->updateCounts($albumId);
        $this->catalogCounter->count(CountableTableEnum::ALBUM_DISK);
        $this->catalogCounter->count(CountableTableEnum::SONG);

        return false;
    }
}
