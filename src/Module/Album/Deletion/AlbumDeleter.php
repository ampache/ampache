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
use Ampache\Model\Shoutbox;
use Ampache\Model\Useractivity;
use Ampache\Model\Userflag;
use Ampache\Module\Album\Deletion\Exception\AlbumDeletionException;
use Ampache\Module\System\LegacyLogger;
use Ampache\Repository\AlbumRepositoryInterface;
use Psr\Log\LoggerInterface;

/**
 * Deletes an album including all songs
 */
final class AlbumDeleter implements AlbumDeleterInterface
{
    private AlbumRepositoryInterface $albumRepository;

    private ModelFactoryInterface $modelFactory;

    private LoggerInterface $logger;

    public function __construct(
        AlbumRepositoryInterface $albumRepository,
        ModelFactoryInterface $modelFactory,
        LoggerInterface $logger
    ) {
        $this->albumRepository = $albumRepository;
        $this->modelFactory    = $modelFactory;
        $this->logger          = $logger;
    }

    /**
     * @throws AlbumDeletionException
     */
    public function delete(
        Album $album
    ): void {
        $songIds = $album->get_songs();
        foreach ($songIds as $songId) {
            $song    = $this->modelFactory->createSong($songId);
            $deleted = $song->remove();
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
        Shoutbox::garbage_collection('album', $album->id);
        Useractivity::garbage_collection('album', $album->id);
    }
}
