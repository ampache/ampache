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

namespace Ampache\Module\Podcast;

use Ampache\Repository\Model\PodcastInterface;
use Ampache\Repository\PodcastEpisodeRepositoryInterface;
use Ampache\Repository\PodcastRepositoryInterface;

/**
 * Deletes a Podcast including its episodes
 */
final class PodcastDeleter implements PodcastDeleterInterface
{
    private PodcastRepositoryInterface $podcastRepository;

    private PodcastEpisodeRepositoryInterface $podcastEpisodeRepository;

    private PodcastEpisodeDeleterInterface $podcastEpisodeDeleter;

    public function __construct(
        PodcastRepositoryInterface $podcastRepository,
        PodcastEpisodeRepositoryInterface $podcastEpisodeRepository,
        PodcastEpisodeDeleterInterface $podcastEpisodeDeleter
    ) {
        $this->podcastRepository        = $podcastRepository;
        $this->podcastEpisodeRepository = $podcastEpisodeRepository;
        $this->podcastEpisodeDeleter    = $podcastEpisodeDeleter;
    }

    public function delete(
        PodcastInterface $podcast
    ): bool {
        $episodeIds = $this->podcastEpisodeRepository->getEpisodeIds($podcast);

        foreach ($episodeIds as $episodeId) {
            $this->podcastEpisodeDeleter->delete(
                $this->podcastEpisodeRepository->findById($episodeId)
            );
        }

        return $this->podcastRepository->remove($podcast);
    }
}
