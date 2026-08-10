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

namespace Ampache\Module\Application\Podcast;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Module\Application\ApplicationActionInterface;
use Ampache\Module\Application\Exception\AccessDeniedException;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\Podcast\Feed\Exception\FeedLoadingException;
use Ampache\Module\Podcast\PodcastUpdaterInterface;
use Ampache\Module\Util\UiInterface;
use Ampache\Repository\PodcastRepositoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Overwrites the podcast details with the ones its feed advertises
 */
final readonly class UpdateFromFeedAction implements ApplicationActionInterface
{
    public const string REQUEST_KEY = 'update_from_feed';

    public function __construct(
        private ConfigContainerInterface $configContainer,
        private UiInterface $ui,
        private PodcastRepositoryInterface $podcastRepository,
        private PodcastUpdaterInterface $podcastUpdater,
    ) {}

    public function run(ServerRequestInterface $request, GuiGatekeeperInterface $gatekeeper): ?ResponseInterface
    {
        if ($this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::PODCAST) === false) {
            return null;
        }

        if (
            $gatekeeper->mayAccess(AccessTypeEnum::INTERFACE, AccessLevelEnum::CONTENT_MANAGER) === false
            || $this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::DEMO_MODE)
        ) {
            throw new AccessDeniedException();
        }

        $podcastId = (int) ($request->getQueryParams()['podcast_id'] ?? 0);
        $webPath   = $this->configContainer->getWebPath();

        $this->ui->showHeader();

        $podcast = $this->podcastRepository->findById($podcastId);
        if ($podcast === null) {
            echo T_('You have requested an object that does not exist');
        } else {
            $returnUrl = sprintf('%s/podcast.php?action=show&podcast=%d', $webPath, $podcastId);

            try {
                $updated = $this->podcastUpdater->update($podcast);
            } catch (FeedLoadingException) {
                $updated = false;
            }

            if ($updated) {
                $this->ui->showContinue(
                    T_('No Problem'),
                    T_('Podcast information updated from the feed'),
                    $returnUrl
                );
            } else {
                $this->ui->showContinue(
                    T_('There Was a Problem'),
                    T_('The feed could not be read'),
                    $returnUrl
                );
            }
        }

        $this->ui->showQueryStats();
        $this->ui->showFooter();

        return null;
    }
}
