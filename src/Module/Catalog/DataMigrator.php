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

namespace Ampache\Module\Catalog;

use Ampache\Module\System\LegacyLogger;
use Ampache\Repository\ArtRepositoryInterface;
use Ampache\Repository\RatingRepositoryInterface;
use Ampache\Repository\RecommendationRepositoryInterface;
use Ampache\Repository\ShareRepositoryInterface;
use Ampache\Repository\ShoutRepositoryInterface;
use Ampache\Repository\StatsRepositoryInterface;
use Ampache\Repository\TagRepositoryInterface;
use Ampache\Repository\UserActivityRepositoryInterface;
use Ampache\Repository\UserflagRepositoryInterface;
use Psr\Log\LoggerInterface;

final class DataMigrator implements DataMigratorInterface
{
    private UserActivityRepositoryInterface $userActivityRepository;

    private LoggerInterface $logger;

    private ShoutRepositoryInterface $shoutRepository;

    private TagRepositoryInterface $tagRepository;

    private ShareRepositoryInterface $shareRepository;

    private RecommendationRepositoryInterface $recommendationRepository;

    private StatsRepositoryInterface $statsRepository;

    private UserflagRepositoryInterface $userflagRepository;

    private RatingRepositoryInterface $ratingRepository;

    private ArtRepositoryInterface $artRepository;

    public function __construct(
        UserActivityRepositoryInterface $userActivityRepository,
        LoggerInterface $logger,
        ShoutRepositoryInterface $shoutRepository,
        TagRepositoryInterface $tagRepository,
        ShareRepositoryInterface $shareRepository,
        RecommendationRepositoryInterface $recommendationRepository,
        StatsRepositoryInterface $statsRepository,
        UserflagRepositoryInterface $userflagRepository,
        RatingRepositoryInterface $ratingRepository,
        ArtRepositoryInterface $artRepository
    ) {
        $this->userActivityRepository   = $userActivityRepository;
        $this->logger                   = $logger;
        $this->shoutRepository          = $shoutRepository;
        $this->tagRepository            = $tagRepository;
        $this->shareRepository          = $shareRepository;
        $this->recommendationRepository = $recommendationRepository;
        $this->statsRepository          = $statsRepository;
        $this->userflagRepository       = $userflagRepository;
        $this->ratingRepository         = $ratingRepository;
        $this->artRepository            = $artRepository;
    }

    /**
     * Migrate an object associate data to a new object
     */
    public function migrate(string $objectType, int $oldObjectId, int $newObjectId): bool
    {
        if ($oldObjectId != $newObjectId) {
            $this->logger->warning(
                sprintf(
                    'migrate %s from %d to %d',
                    $objectType,
                    $oldObjectId,
                    $newObjectId
                ),
                [LegacyLogger::CONTEXT_TYPE => __CLASS__]
            );

            $this->statsRepository->migrate($objectType, $oldObjectId, $newObjectId);
            $this->userActivityRepository->migrate($objectType, $oldObjectId, $newObjectId);
            $this->recommendationRepository->migrate($objectType, $oldObjectId, $newObjectId);
            $this->shareRepository->migrate($objectType, $oldObjectId, $newObjectId);
            $this->shoutRepository->migrate($objectType, $oldObjectId, $newObjectId);
            $this->tagRepository->migrate($objectType, $oldObjectId, $newObjectId);
            $this->userflagRepository->migrate($objectType, $oldObjectId, $newObjectId);
            $this->ratingRepository->migrate($objectType, $oldObjectId, $newObjectId);
            $this->artRepository->migrate($objectType, $oldObjectId, $newObjectId);

            return true;
        }

        return false;
    }
}
