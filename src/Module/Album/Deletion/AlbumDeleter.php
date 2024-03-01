<?php

declare(strict_types=1);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2023
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

use Ampache\Module\Art\ArtCleanupInterface;
use Ampache\Repository\Model\Album;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\Model\Rating;
use Ampache\Repository\Model\Userflag;
use Ampache\Module\Album\Deletion\Exception\AlbumDeletionException;
use Ampache\Module\Song\Deletion\SongDeleterInterface;
use Ampache\Module\System\LegacyLogger;
use Ampache\Repository\AlbumRepositoryInterface;
use Ampache\Repository\ShoutRepositoryInterface;
use Ampache\Repository\SongRepositoryInterface;
use Ampache\Repository\UserActivityRepositoryInterface;
use Psr\Log\LoggerInterface;

/**
 * Deletes an album including all songs
 */
final class AlbumDeleter implements AlbumDeleterInterface
{
    private AlbumRepositoryInterface $albumRepository;

    private ModelFactoryInterface $modelFactory;

    private LoggerInterface $logger;

    private SongRepositoryInterface $songRepository;

    private ShoutRepositoryInterface $shoutRepository;

    private SongDeleterInterface $songDeleter;

    private UserActivityRepositoryInterface $useractivityRepository;

    private ArtCleanupInterface $artCleanup;

    public function __construct(
        AlbumRepositoryInterface $albumRepository,
        ModelFactoryInterface $modelFactory,
        LoggerInterface $logger,
        SongRepositoryInterface $songRepository,
        ShoutRepositoryInterface $shoutRepository,
        SongDeleterInterface $songDeleter,
        UserActivityRepositoryInterface $useractivityRepository,
        ArtCleanupInterface $artCleanup
    ) {
        $this->albumRepository        = $albumRepository;
        $this->modelFactory           = $modelFactory;
        $this->logger                 = $logger;
        $this->songRepository         = $songRepository;
        $this->shoutRepository        = $shoutRepository;
        $this->songDeleter            = $songDeleter;
        $this->useractivityRepository = $useractivityRepository;
        $this->artCleanup             = $artCleanup;
    }

    /**
     * @throws AlbumDeletionException
     */
    public function delete(
        Album $album
    ): void {
        $albumId = $album->getId();
        $songIds = $this->songRepository->getByAlbum($albumId);
        foreach ($songIds as $songId) {
            $song    = $this->modelFactory->createSong($songId);
            $deleted = $this->songDeleter->delete($song);
            if (!$deleted) {
                $this->logger->critical(
                    sprintf('Error when deleting the song `%d`.', $songId),
                    [LegacyLogger::CONTEXT_TYPE => __CLASS__]
                );

                throw new AlbumDeletionException();
            }
        }

        $this->albumRepository->delete($album);

        $this->artCleanup->collectGarbageForObject('album', $albumId);
        Userflag::garbage_collection('album', $albumId);
        Rating::garbage_collection('album', $albumId);
        $this->shoutRepository->collectGarbage('album', $albumId);
        $this->useractivityRepository->collectGarbage('album', $albumId);
    }
}
