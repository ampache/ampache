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

namespace Ampache\Module\Catalog\GarbageCollector;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Module\Art\ArtCleanupInterface;
use Ampache\Module\Catalog\Catalog;
use Ampache\Module\Catalog\CatalogCounterInterface;
use Ampache\Module\Label\LabelGarbageCollectorInterface;
use Ampache\Module\Metadata\MetadataManagerInterface;
use Ampache\Module\Statistics\Rating;
use Ampache\Module\Statistics\Stats;
use Ampache\Module\Statistics\Userflag;
use Ampache\Module\Util\Recommendation;
use Ampache\Repository\AlbumRepositoryInterface;
use Ampache\Repository\ArtistRepositoryInterface;
use Ampache\Repository\BookmarkRepositoryInterface;
use Ampache\Repository\BroadcastRepositoryInterface;
use Ampache\Repository\FolderRepositoryInterface;
use Ampache\Repository\LabelRepositoryInterface;
use Ampache\Repository\Model\Song;
use Ampache\Repository\Model\Tag;
use Ampache\Repository\Model\User;
use Ampache\Repository\PlaylistFolderRepositoryInterface;
use Ampache\Repository\PlaylistRepositoryInterface;
use Ampache\Repository\PodcastEpisodeRepositoryInterface;
use Ampache\Repository\PodcastRepositoryInterface;
use Ampache\Repository\SearchRepositoryInterface;
use Ampache\Repository\ShoutRepositoryInterface;
use Ampache\Repository\SongRepositoryInterface;
use Ampache\Repository\UserActivityRepositoryInterface;
use Ampache\Repository\UserRepositoryInterface;
use Ampache\Repository\VideoRepositoryInterface;
use Ampache\Repository\WantedRepositoryInterface;

/**
 * This is a wrapper for all of the different database cleaning
 * functions, it runs them in an order that resembles correctness.
 */
final readonly class CatalogGarbageCollector implements CatalogGarbageCollectorInterface
{
    public function __construct(
        private AlbumRepositoryInterface $albumRepository,
        private BookmarkRepositoryInterface $bookmarkRepository,
        private BroadcastRepositoryInterface $broadcastRepository,
        private ShoutRepositoryInterface $shoutRepository,
        private UserActivityRepositoryInterface $userActivityRepository,
        private UserRepositoryInterface $userRepository,
        private MetadataManagerInterface $metadataManager,
        private PodcastEpisodeRepositoryInterface $podcastEpisodeRepository,
        private WantedRepositoryInterface $wantedRepository,
        private LabelRepositoryInterface $labelRepository,
        private ArtCleanupInterface $artCleanup,
        private ArtistRepositoryInterface $artistRepository,
        private FolderRepositoryInterface $folderRepository,
        private VideoRepositoryInterface $videoRepository,
        private PlaylistRepositoryInterface $playlistRepository,
        private PlaylistFolderRepositoryInterface $playlistFolderRepository,
        private SearchRepositoryInterface $searchRepository,
        private LabelGarbageCollectorInterface $labelGarbageCollector,
        private SongRepositoryInterface $songRepository,
        private PodcastRepositoryInterface $podcastRepository,
        private CatalogCounterInterface $catalogCounter,
        private ConfigContainerInterface $configContainer,
    ) {}

    public function collect(): void
    {
        Song::garbage_collection();
        $this->artistRepository->collectGarbage();
        $this->albumRepository->collectGarbage();
        $this->videoRepository->collectGarbage();
        $this->bookmarkRepository->collectGarbage();
        $this->broadcastRepository->collectGarbage();
        $this->wantedRepository->collectGarbage();
        $this->artCleanup->collectGarbage();
        Stats::garbage_collection();
        Rating::garbage_collection();
        Userflag::garbage_collection();
        // placeholder labels go first so the sweep below picks up the associations they leave behind
        $this->labelGarbageCollector->collect();
        $this->labelRepository->collectGarbage();
        Recommendation::garbage_collection();
        $this->userActivityRepository->collectGarbage();
        $this->userRepository->collectGarbage();
        // dead playlist entries, plus collaborator rows that outlived their list and would otherwise
        // be inherited by a later list handed the freed id
        $this->playlistRepository->collectGarbage();
        $this->searchRepository->collectGarbage();
        $this->playlistFolderRepository->collectGarbage();
        $this->shoutRepository->collectGarbage();
        Tag::garbage_collection();
        Catalog::clear_catalog_cache();
        User::garbage_collection();
        $this->folderRepository->collectGarbage();

        $this->metadataManager->collectGarbage();
        $this->podcastEpisodeRepository->collectGarbage();

        $this->recount();
    }

    /**
     * Puts every stored count back in step with the rows that are left
     *
     * The live paths keep these right as things are played and deleted; this is the repair for a database
     * that drifted, which is why it belongs to garbage collection rather than to a periodic sweep.
     */
    private function recount(): void
    {
        // a rebuild is a join, so rows that lost their last play are zeroed first or they keep the old total
        $this->songRepository->resetCountsWithoutHistory();
        $this->songRepository->updateAllCounts();
        $this->videoRepository->updateAllCounts();
        $this->podcastEpisodeRepository->updateAllCounts();
        $this->podcastRepository->updateAllCounts();

        $this->albumRepository->updateAllCounts();
        $this->albumRepository->updateAllSkipCounts();
        $this->artistRepository->updateAllCounts();
        $this->artistRepository->updateAllSkipCounts();
        $this->folderRepository->update_folder_counts();

        $this->catalogCounter->refreshServerCounts(
            $this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::CATALOG_DISABLE)
        );
        User::update_counts();
    }
}
