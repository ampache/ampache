<?php

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2023
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

namespace Ampache\Module\Application\Podcast;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Module\System\LegacyLogger;
use Ampache\Module\Application\ApplicationActionInterface;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\Util\UiInterface;
use Ampache\Repository\PodcastRepositoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;

/**
 * Renders the podcast overview
 */
final class ShowAction implements ApplicationActionInterface
{
    public const REQUEST_KEY = 'show';

    private ConfigContainerInterface $configContainer;

    private UiInterface $ui;

    private LoggerInterface $logger;

    private PodcastRepositoryInterface $podcastRepository;

    public function __construct(
        ConfigContainerInterface $configContainer,
        UiInterface $ui,
        LoggerInterface $logger,
        PodcastRepositoryInterface $podcastRepository
    ) {
        $this->configContainer   = $configContainer;
        $this->ui                = $ui;
        $this->logger            = $logger;
        $this->podcastRepository = $podcastRepository;
    }

    public function run(ServerRequestInterface $request, GuiGatekeeperInterface $gatekeeper): ?ResponseInterface
    {
        if ($this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::PODCAST) === false) {
            return null;
        }

        $this->ui->showHeader();

        $podcastId = (int) ($request->getQueryParams()['podcast'] ?? 0);
        $podcast   = $this->podcastRepository->findById($podcastId);
        if ($podcast === null) {
            $this->logger->warning(
                'Requested a podcast that does not exist',
                [LegacyLogger::CONTEXT_TYPE => __CLASS__]
            );
            echo T_('You have requested an object that does not exist');
        } else {
            $this->ui->showBoxTop((string)$podcast->get_fullname(), 'info-box');
            $this->ui->show(
                'show_podcast.inc.php',
                [
                    'podcast' => $podcast,
                    'object_ids' => $this->podcastRepository->getEpisodes($podcast),
                    'object_type' => 'podcast_episode'
                ]
            );
        }

        $this->ui->showQueryStats();
        $this->ui->showFooter();

        return null;
    }
}
