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

namespace Ampache\Module\Application\PodcastEpisode;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Module\Application\ApplicationActionInterface;
use Ampache\Module\Application\Exception\AccessDeniedException;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\Catalog\MediaDeletionCheckerInterface;
use Ampache\Module\Podcast\PodcastEpisodeDeleterInterface;
use Ampache\Module\System\LegacyLogger;
use Ampache\Module\Util\UiInterface;
use Ampache\Repository\PodcastEpisodeRepositoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;

final class ConfirmDeleteAction implements ApplicationActionInterface
{
    public const REQUEST_KEY = 'confirm_delete';

    private ConfigContainerInterface $configContainer;

    private UiInterface $ui;

    private LoggerInterface $logger;

    private MediaDeletionCheckerInterface $mediaDeletionChecker;

    private PodcastEpisodeDeleterInterface $podcastEpisodeDeleter;

    private PodcastEpisodeRepositoryInterface $podcastEpisodeRepository;

    public function __construct(
        ConfigContainerInterface $configContainer,
        UiInterface $ui,
        LoggerInterface $logger,
        MediaDeletionCheckerInterface $mediaDeletionChecker,
        PodcastEpisodeDeleterInterface $podcastEpisodeDeleter,
        PodcastEpisodeRepositoryInterface $podcastEpisodeRepository
    ) {
        $this->configContainer          = $configContainer;
        $this->ui                       = $ui;
        $this->logger                   = $logger;
        $this->mediaDeletionChecker     = $mediaDeletionChecker;
        $this->podcastEpisodeDeleter    = $podcastEpisodeDeleter;
        $this->podcastEpisodeRepository = $podcastEpisodeRepository;
    }

    public function run(ServerRequestInterface $request, GuiGatekeeperInterface $gatekeeper): ?ResponseInterface
    {
        if ($this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::DEMO_MODE) === true) {
            return null;
        }

        $episodeId = (int) ($request->getQueryParams()['podcast_episode_id'] ?? 0);

        $episode = $this->podcastEpisodeRepository->findById($episodeId);

        if ($this->mediaDeletionChecker->mayDelete($episode, $gatekeeper->getUserId()) === false) {
            $this->logger->warning(
                sprintf('Unauthorized to remove the episode `%s`', $episodeId),
                [LegacyLogger::CONTEXT_TYPE => __CLASS__]
            );
            throw new AccessDeniedException();
        }

        $this->ui->showHeader();

        if ($this->podcastEpisodeDeleter->delete($episode)) {
            $this->ui->showConfirmation(
                T_('No Problem'),
                T_('Podcast Episode has been deleted'),
                $this->configContainer->getWebPath()
            );
        } else {
            $this->ui->showConfirmation(
                T_('There Was a Problem'),
                T_('Couldn\'t delete this Podcast Episode'),
                $this->configContainer->getWebPath()
            );
        }

        $this->ui->showQueryStats();
        $this->ui->showFooter();

        return null;
    }
}
