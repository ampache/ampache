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

declare(strict_types=1);

namespace Ampache\Module\Album\Deletion;

use Ampache\Model\Album;
use Ampache\Model\Art;
use Ampache\Model\ModelFactoryInterface;
use Ampache\Model\Rating;
use Ampache\Model\Useractivity;
use Ampache\Model\Userflag;
use Ampache\Module\Album\Deletion\Exception\AlbumDeletionException;
use Ampache\Module\Song\Deletion\SongDeleterInterface;
use Ampache\Module\System\LegacyLogger;
use Ampache\Repository\AlbumRepositoryInterface;
use Ampache\Repository\ShoutRepositoryInterface;
use Ampache\Repository\SongRepositoryInterface;
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

    public function __construct(
        AlbumRepositoryInterface $albumRepository,
        ModelFactoryInterface $modelFactory,
        LoggerInterface $logger,
        SongRepositoryInterface $songRepository,
        ShoutRepositoryInterface $shoutRepository,
        SongDeleterInterface $songDeleter
    ) {
        $this->albumRepository = $albumRepository;
        $this->modelFactory    = $modelFactory;
        $this->logger          = $logger;
        $this->songRepository  = $songRepository;
        $this->shoutRepository = $shoutRepository;
        $this->songDeleter     = $songDeleter;
    }

    /**
     * @throws AlbumDeletionException
     */
    public function delete(
        Album $album
    ): void {
        $songIds = $this->songRepository->getByAlbum($album->id);
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

        $deleted = $this->albumRepository->delete(
            $album->id
        );

        if ($deleted === false) {
            $this->logger->critical(
                sprintf('Error when deleting the album `%d`.', $album->id),
                [LegacyLogger::CONTEXT_TYPE => __CLASS__]
            );

            throw new AlbumDeletionException();
        }

        Art::garbage_collection('album', $album->id);
        Userflag::garbage_collection('album', $album->id);
        Rating::garbage_collection('album', $album->id);
        $this->shoutRepository->collectGarbage('album', $album->id);
        Useractivity::garbage_collection('album', $album->id);
    }
}
