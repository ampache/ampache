<?php

declare(strict_types=1);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2024
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

namespace Ampache\Application\Api\Ajax\Handler;

use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Module\Authorization\Check\PrivilegeCheckerInterface;
use Ampache\Module\Podcast\PodcastSyncerInterface;
use Ampache\Module\System\LegacyLogger;
use Ampache\Module\Util\RequestParserInterface;
use Ampache\Repository\Model\User;
use Ampache\Repository\PodcastEpisodeRepositoryInterface;
use Ampache\Repository\PodcastRepositoryInterface;
use Psr\Log\LoggerInterface;

/**
 * Ajax actions related to podcast syncing
 */
final readonly class PodcastAjaxHandler implements AjaxHandlerInterface
{
    public function __construct(
        private RequestParserInterface $requestParser,
        private PodcastSyncerInterface $podcastSyncer,
        private PodcastRepositoryInterface $podcastRepository,
        private PodcastEpisodeRepositoryInterface $podcastEpisodeRepository,
        private PrivilegeCheckerInterface $privilegeChecker,
        private LoggerInterface $logger,
    ) {
    }

    public function handle(User $user): void
    {
        if (!$this->privilegeChecker->check(AccessTypeEnum::INTERFACE, AccessLevelEnum::MANAGER)) {
            $this->logger->warning(
                sprintf('User `%s` attempted to sync podcast', $user->getUsername()),
                [LegacyLogger::CONTEXT_TYPE => self::class]
            );

            return;
        }

        $action  = $this->requestParser->getFromRequest('action');

        switch ($action) {
            case 'syncPodcastEpisode':
                $episodeId = (int) $this->requestParser->getFromRequest('podcast_episode_id');

                $episode = $this->podcastEpisodeRepository->findById($episodeId);
                if ($episode === null) {
                    $this->logger->warning(
                        sprintf('Cannot find podcast episode `%d`', $episodeId),
                        [LegacyLogger::CONTEXT_TYPE => self::class]
                    );
                } else {
                    $this->podcastSyncer->syncEpisode($episode);
                }

                break;
            case 'syncPodcast':
                $podcastId = (int) $this->requestParser->getFromRequest('podcast_id');

                $podcast = $this->podcastRepository->findById($podcastId);
                if ($podcast === null) {
                    $this->logger->warning(
                        sprintf('Cannot find podcast `%d`', $podcast),
                        [LegacyLogger::CONTEXT_TYPE => self::class]
                    );
                } else {
                    $this->podcastSyncer->sync($podcast, true);
                }
                break;
        }
    }
}
