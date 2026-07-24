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

namespace Ampache\Module\Application\PodcastEpisode;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Module\Application\ApplicationActionInterface;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\Util\DeletionUrlResolverInterface;
use Ampache\Module\Util\UiInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final readonly class DeleteAction implements ApplicationActionInterface
{
    public const string REQUEST_KEY = 'delete';

    public function __construct(
        private ConfigContainerInterface $configContainer,
        private UiInterface $ui,
        private DeletionUrlResolverInterface $deletionUrlResolver,
    ) {}

    public function run(ServerRequestInterface $request, GuiGatekeeperInterface $gatekeeper): ?ResponseInterface
    {
        if ($this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::DEMO_MODE)) {
            return null;
        }

        $queryParams = $request->getQueryParams();
        $episode_id  = (int) ($queryParams['podcast_episode_id'] ?? 0);
        $burlParam   = (string) ($queryParams['burl'] ?? '');
        $webPath     = $this->configContainer->getWebPath();

        $this->ui->showHeader();
        $this->ui->showConfirmationWithReturn(
            T_('Are You Sure?'),
            T_('The Podcast Episode will be deleted'),
            sprintf(
                '%s/podcast_episode.php?action=confirm_delete&podcast_episode_id=%d&burl=%s',
                $webPath,
                $episode_id,
                rawurlencode($burlParam)
            ),
            $this->deletionUrlResolver->resolveBurl($burlParam) ?: sprintf(
                '%s/podcast_episode.php?action=show&podcast_episode=%d',
                $webPath,
                $episode_id
            ),
            'delete_podcast_episode'
        );

        $this->ui->showQueryStats();
        $this->ui->showFooter();

        return null;
    }
}
