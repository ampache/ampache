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

use Ampache\Config\AmpConfig;
use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Gui\Podcast\PodcastView;
use Ampache\Module\Application\ApplicationActionInterface;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\Database\Query\BrowseFactoryInterface;
use Ampache\Module\Playback\Stream_Playlist;
use Ampache\Module\System\LegacyLogger;
use Ampache\Module\Util\Ui;
use Ampache\Module\Util\UiInterface;
use Ampache\Repository\Model\User;
use Ampache\Repository\PodcastRepositoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;

/**
 * Renders the podcast overview
 */
final readonly class ShowAction implements ApplicationActionInterface
{
    public const string REQUEST_KEY = 'show';

    public function __construct(
        private ConfigContainerInterface $configContainer,
        private UiInterface $ui,
        private LoggerInterface $logger,
        private PodcastRepositoryInterface $podcastRepository,
        private BrowseFactoryInterface $browseFactory,
    ) {}

    public function run(ServerRequestInterface $request, GuiGatekeeperInterface $gatekeeper): ?ResponseInterface
    {
        if ($this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::PODCAST) === false) {
            return null;
        }

        $this->ui->showHeader();

        $user      = $gatekeeper->getUser() ?? new User(-1);
        $catalogs  = $user->catalogs['podcast'] ?? User::get_user_catalogs($user->id);
        $podcastId = (int) ($request->getQueryParams()['podcast'] ?? 0);
        $podcast   = $this->podcastRepository->findById($podcastId);
        if ($podcast === null || !in_array($podcast->getCatalogId(), $catalogs)) {
            $this->logger->warning(
                'Requested a podcast that does not exist',
                [LegacyLogger::CONTEXT_TYPE => self::class]
            );
            echo T_('You have requested an object that does not exist');
        } else {
            // the episode list is a browse, so it is configured here rather than inside the view
            $browse = $this->browseFactory->create();
            $browse->set_type('podcast_episode');
            $browse->set_use_filters(false);
            $browse->set_skip_catalog_check(true);

            $mayDelete = $gatekeeper->mayAccess(AccessTypeEnum::INTERFACE, AccessLevelEnum::MANAGER);
            $mayManage = $mayDelete || $gatekeeper->mayAccess(AccessTypeEnum::INTERFACE, AccessLevelEnum::CONTENT_MANAGER);

            echo (new PodcastView(
                AmpConfig::get_web_path(),
                $podcast,
                $browse,
                $podcast->getEpisodeIds(),
                $user,
                Ui::is_grid_view('album'),
                (bool) AmpConfig::get('directplay'),
                Stream_Playlist::check_autoplay_next(),
                Stream_Playlist::check_autoplay_append(),
                User::is_registered() && (bool) AmpConfig::get('ratings'),
                $gatekeeper->mayAccess(AccessTypeEnum::INTERFACE, AccessLevelEnum::USER),
                $mayManage,
                $mayDelete,
                (bool) AmpConfig::get('statistical_graphs'),
                (bool) AmpConfig::get('use_rss')
            ))->render();
        }

        $this->ui->showQueryStats();
        $this->ui->showFooter();

        return null;
    }
}
