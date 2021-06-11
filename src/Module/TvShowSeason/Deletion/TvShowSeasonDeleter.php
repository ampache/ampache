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

namespace Ampache\Module\TvShowSeason\Deletion;

use Ampache\Module\System\LegacyLogger;
use Ampache\Module\Video\VideoLoaderInterface;
use Ampache\Repository\Model\Art;
use Ampache\Repository\Model\TvShowSeasonInterface;
use Ampache\Repository\Model\Userflag;
use Ampache\Repository\RatingRepositoryInterface;
use Ampache\Repository\ShoutRepositoryInterface;
use Ampache\Repository\TvShowSeasonRepositoryInterface;
use Ampache\Repository\UserActivityRepositoryInterface;
use Psr\Log\LoggerInterface;

final class TvShowSeasonDeleter implements TvShowSeasonDeleterInterface
{
    private TvShowSeasonRepositoryInterface $tvShowSeasonRepository;

    private RatingRepositoryInterface $ratingRepository;

    private ShoutRepositoryInterface $shoutRepository;

    private UserActivityRepositoryInterface $userActivityRepository;

    private VideoLoaderInterface $videoLoader;

    private LoggerInterface $logger;

    public function __construct(
        TvShowSeasonRepositoryInterface $tvShowSeasonRepository,
        RatingRepositoryInterface $ratingRepository,
        ShoutRepositoryInterface $shoutRepository,
        UserActivityRepositoryInterface $userActivityRepository,
        VideoLoaderInterface $videoLoader,
        LoggerInterface $logger
    ) {
        $this->tvShowSeasonRepository = $tvShowSeasonRepository;
        $this->ratingRepository       = $ratingRepository;
        $this->shoutRepository        = $shoutRepository;
        $this->userActivityRepository = $userActivityRepository;
        $this->videoLoader            = $videoLoader;
        $this->logger                 = $logger;
    }

    public function delete(
        TvShowSeasonInterface $tvShowSeason
    ): bool {
        $tvShowSeasonId = $tvShowSeason->getId();
        $deleted        = true;

        foreach ($tvShowSeason->getEpisodeIds() as $videoId) {
            $video   = $this->videoLoader->load($videoId);
            $deleted = $video->remove();
            if (!$deleted) {
                $this->logger->critical(
                    sprintf('Error when deleting the video `%d`.', $videoId),
                    [LegacyLogger::CONTEXT_TYPE => __CLASS__]
                );
                break;
            }
        }

        if ($deleted) {
            $this->tvShowSeasonRepository->delete($tvShowSeasonId);

            Art::garbage_collection('tvshow_season', $tvShowSeasonId);
            Userflag::garbage_collection('tvshow_season', $tvShowSeasonId);
            $this->ratingRepository->collectGarbage('tvshow_season', $tvShowSeasonId);
            $this->shoutRepository->collectGarbage('tvshow_season', $tvShowSeasonId);
            $this->userActivityRepository->collectGarbage('tvshow_season', $tvShowSeasonId);
        }

        return $deleted;
    }
}
