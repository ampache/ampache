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

namespace Ampache\Module\Application\Podcast;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Module\Application\ApplicationActionInterface;
use Ampache\Module\Application\Exception\AccessDeniedException;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\Podcast\PodcastDeleterInterface;
use Ampache\Module\Util\UiInterface;
use Ampache\Repository\PodcastRepositoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class ConfirmDeleteAction implements ApplicationActionInterface
{
    public const REQUEST_KEY = 'confirm_delete';

    private ConfigContainerInterface $configContainer;

    private UiInterface $ui;

    private PodcastDeleterInterface $podcastDeleter;

    private PodcastRepositoryInterface $podcastRepository;

    public function __construct(
        ConfigContainerInterface $configContainer,
        UiInterface $ui,
        PodcastDeleterInterface $podcastDeleter,
        PodcastRepositoryInterface $podcastRepository
    ) {
        $this->configContainer   = $configContainer;
        $this->ui                = $ui;
        $this->podcastDeleter    = $podcastDeleter;
        $this->podcastRepository = $podcastRepository;
    }

    public function run(ServerRequestInterface $request, GuiGatekeeperInterface $gatekeeper): ?ResponseInterface
    {
        if ($this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::PODCAST) === false) {
            return null;
        }

        if (
            $gatekeeper->mayAccess(AccessLevelEnum::TYPE_INTERFACE, AccessLevelEnum::LEVEL_MANAGER) === false ||
            $this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::DEMO_MODE) === true
        ) {
            throw new AccessDeniedException();
        }

        $podcast = $this->podcastRepository->findById(
            (int) ($request->getQueryParams()['podcast_id'] ?? 0)
        );

        $this->ui->showHeader();

        if ($this->podcastDeleter->delete($podcast)) {
            $this->ui->showConfirmation(
                T_('No Problem'),
                T_('Podcast has been deleted'),
                sprintf(
                    '%s/browse.php?action=podcast',
                    $this->configContainer->getWebPath()
                )
            );
        } else {
            $this->ui->showConfirmation(
                T_('There Was a Problem'),
                T_('Couldn\'t delete this Podcast.'),
                sprintf(
                    '%s/browse.php?action=podcast',
                    $this->configContainer->getWebPath()
                )
            );
        }

        $this->ui->showQueryStats();
        $this->ui->showFooter();

        return null;
    }
}
