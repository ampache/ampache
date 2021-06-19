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

namespace Ampache\Module\Catalog\GarbageCollector;

use Ampache\Module\Statistics\Stats;
use Ampache\Repository\AlbumRepositoryInterface;
use Ampache\Repository\ArtistRepositoryInterface;
use Ampache\Repository\CatalogRepositoryInterface;
use Ampache\Repository\MetadataRepositoryInterface;
use Ampache\Repository\Model\Art;
use Ampache\Repository\Model\Playlist;
use Ampache\Repository\Model\Tmp_Playlist;
use Ampache\Repository\Model\Userflag;
use Ampache\Repository\Model\Video;
use Ampache\Repository\RatingRepositoryInterface;
use Ampache\Repository\ShoutRepositoryInterface;
use Ampache\Repository\SongRepositoryInterface;
use Ampache\Repository\TagRepositoryInterface;
use Ampache\Repository\UserActivityRepositoryInterface;

/**
 * This is a wrapper for all of the different database cleaning
 * functions, it runs them in an order that resembles correctness.
 *
 * @todo use InnoDB with foreign keys and on delete cascade to get rid of garbage collection
 */
final class CatalogGarbageCollector implements CatalogGarbageCollectorInterface
{
    private AlbumRepositoryInterface $albumRepository;

    private ShoutRepositoryInterface $shoutRepository;

    private UserActivityRepositoryInterface $useractivityRepository;

    private ArtistRepositoryInterface $artistRepository;

    private SongRepositoryInterface $songRepository;

    private CatalogRepositoryInterface $catalogRepository;

    private RatingRepositoryInterface $ratingRepository;

    private TagRepositoryInterface $tagRepository;

    private MetadataRepositoryInterface $metadataRepository;

    public function __construct(
        AlbumRepositoryInterface $albumRepository,
        ShoutRepositoryInterface $shoutRepository,
        UserActivityRepositoryInterface $useractivityRepository,
        ArtistRepositoryInterface $artistRepository,
        SongRepositoryInterface $songRepository,
        CatalogRepositoryInterface $catalogRepository,
        RatingRepositoryInterface $ratingRepository,
        TagRepositoryInterface $tagRepository,
        MetadataRepositoryInterface $metadataRepository
    ) {
        $this->albumRepository        = $albumRepository;
        $this->shoutRepository        = $shoutRepository;
        $this->useractivityRepository = $useractivityRepository;
        $this->artistRepository       = $artistRepository;
        $this->songRepository         = $songRepository;
        $this->catalogRepository      = $catalogRepository;
        $this->ratingRepository       = $ratingRepository;
        $this->tagRepository          = $tagRepository;
        $this->metadataRepository     = $metadataRepository;
    }

    public function collect(): void
    {
        $this->songRepository->collectGarbage();
        $this->albumRepository->collectGarbage();
        $this->artistRepository->collectGarbage();
        Video::garbage_collection();
        Art::garbage_collection();
        Stats::garbage_collection();
        $this->ratingRepository->collectGarbage();
        Userflag::garbage_collection();
        $this->useractivityRepository->collectGarbage();
        Playlist::garbage_collection();
        Tmp_Playlist::garbage_collection();
        $this->shoutRepository->collectGarbage();
        $this->tagRepository->collectGarbage();

        $this->metadataRepository->collectGarbage();

        $this->catalogRepository->collectMappingGarbage();
    }
}
