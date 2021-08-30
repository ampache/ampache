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

namespace Ampache\Module\Artist\Deletion;

use Ampache\Repository\Model\Art;
use Ampache\Repository\Model\Artist;
use Ampache\Repository\Model\Label;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\Model\Rating;
use Ampache\Repository\Model\Useractivity;
use Ampache\Repository\Model\Userflag;
use Ampache\Module\Album\Deletion\AlbumDeleterInterface;
use Ampache\Module\Album\Deletion\Exception\AlbumDeletionException;
use Ampache\Module\Artist\Deletion\Exception\ArtistDeletionException;
use Ampache\Module\System\LegacyLogger;
use Ampache\Repository\AlbumRepositoryInterface;
use Ampache\Repository\ArtistRepositoryInterface;
use Ampache\Repository\ShoutRepositoryInterface;
use Ampache\Repository\UserActivityRepositoryInterface;
use Psr\Log\LoggerInterface;

final class ArtistDeleter implements ArtistDeleterInterface
{
    private AlbumDeleterInterface $albumDeleter;

    private ArtistRepositoryInterface $artistRepository;

    private AlbumRepositoryInterface $albumRepository;

    private ModelFactoryInterface $modelFactory;

    private LoggerInterface $logger;

    private ShoutRepositoryInterface $shoutRepository;

    private UserActivityRepositoryInterface $useractivityRepository;

    public function __construct(
        AlbumDeleterInterface $albumDeleter,
        ArtistRepositoryInterface $artistRepository,
        AlbumRepositoryInterface $albumRepository,
        ModelFactoryInterface $modelFactory,
        LoggerInterface $logger,
        ShoutRepositoryInterface $shoutRepository,
        UserActivityRepositoryInterface $useractivityRepository
    ) {
        $this->albumDeleter           = $albumDeleter;
        $this->artistRepository       = $artistRepository;
        $this->albumRepository        = $albumRepository;
        $this->modelFactory           = $modelFactory;
        $this->logger                 = $logger;
        $this->shoutRepository        = $shoutRepository;
        $this->useractivityRepository = $useractivityRepository;
    }

    /**
     * @throws ArtistDeletionException
     */
    public function remove(
        Artist $artist
    ): void {
        $album_ids = $this->albumRepository->getByArtist($artist->id);

        foreach ($album_ids as $albumId) {
            $album = $this->modelFactory->createAlbum($albumId);

            try {
                $this->albumDeleter->delete($album);
            } catch (AlbumDeletionException $e) {
                $this->logger->critical(
                    sprintf(
                        "Error when deleting the album `%d`.",
                        $albumId
                    ),
                    [LegacyLogger::CONTEXT_TYPE => __CLASS__]
                );

                throw new ArtistDeletionException();
            }
        }

        $artistId = $artist->getId();

        $deleted = $this->artistRepository->delete($artistId);
        if ($deleted) {
            Art::garbage_collection('artist', $artistId);
            Userflag::garbage_collection('artist', $artistId);
            Rating::garbage_collection('artist', $artistId);
            Label::garbage_collection();
            $this->shoutRepository->collectGarbage('artist', $artistId);
            $this->useractivityRepository->collectGarbage('artist', $artistId);
        }
    }
}
