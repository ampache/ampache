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

namespace Ampache\Module\Api\Ajax\Handler\Podcast;

use Ampache\Module\Api\Ajax\Handler\ActionInterface;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\Check\PrivilegeCheckerInterface;
use Ampache\Module\Podcast\PodcastEpisodeDownloaderInterface;
use Ampache\Module\Podcast\PodcastSyncerInterface;
use Ampache\Module\System\LegacyLogger;
use Ampache\Repository\Model\User;
use Ampache\Repository\PodcastEpisodeRepositoryInterface;
use Ampache\Repository\PodcastRepositoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;

final class SyncAction implements ActionInterface
{
    private PodcastRepositoryInterface $podcastRepository;

    private PodcastSyncerInterface $podcastSyncer;

    private PodcastEpisodeRepositoryInterface $podcastEpisodeRepository;

    private PodcastEpisodeDownloaderInterface $podcastEpisodeDownloader;

    private PrivilegeCheckerInterface $privilegeChecker;

    private LoggerInterface $logger;

    public function __construct(
        PodcastRepositoryInterface $podcastRepository,
        PodcastSyncerInterface $podcastSyncer,
        PodcastEpisodeRepositoryInterface $podcastEpisodeRepository,
        PodcastEpisodeDownloaderInterface $podcastEpisodeDownloader,
        PrivilegeCheckerInterface $privilegeChecker,
        LoggerInterface $logger
    ) {
        $this->podcastRepository        = $podcastRepository;
        $this->podcastSyncer            = $podcastSyncer;
        $this->podcastEpisodeRepository = $podcastEpisodeRepository;
        $this->podcastEpisodeDownloader = $podcastEpisodeDownloader;
        $this->privilegeChecker         = $privilegeChecker;
        $this->logger                   = $logger;
    }

    public function handle(
        ServerRequestInterface $request,
        ResponseInterface $response,
        User $user
    ): array {
        if (!$this->privilegeChecker->check(AccessLevelEnum::TYPE_INTERFACE, AccessLevelEnum::LEVEL_MANAGER)) {
            $this->logger->critical(
                $user->username . ' attempted to sync podcast',
                [LegacyLogger::CONTEXT_TYPE => __CLASS__]
            );

            return [];
        }

        if (isset($_REQUEST['podcast_id'])) {
            $podcast = $this->podcastRepository->findById((int) $_REQUEST['podcast_id']);
            if ($podcast !== null) {
                $this->podcastSyncer->sync($podcast, true);
            } else {
                $this->logger->critical(
                    'Cannot find podcast',
                    [LegacyLogger::CONTEXT_TYPE => __CLASS__]
                );
            }
        } elseif (isset($_REQUEST['podcast_episode_id'])) {
            $episode = $this->podcastEpisodeRepository->findById(
                (int) $_REQUEST['podcast_episode_id']
            );
            if ($episode === null) {
                $this->logger->critical(
                    'Cannot find podcast episode',
                    [LegacyLogger::CONTEXT_TYPE => __CLASS__]
                );
            } else {
                $this->podcastEpisodeDownloader->download($episode);
            }
        }

        return ['rfc3514' => '0x1'];
    }
}
