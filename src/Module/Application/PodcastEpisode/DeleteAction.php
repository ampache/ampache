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

declare(strict_types=0);

namespace Ampache\Module\Application\PodcastEpisode;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Module\Application\ApplicationActionInterface;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\Util\UiInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class DeleteAction implements ApplicationActionInterface
{
    public const REQUEST_KEY = 'delete';

    private ConfigContainerInterface $configContainer;

    private UiInterface $ui;

    public function __construct(
        ConfigContainerInterface $configContainer,
        UiInterface $ui
    ) {
        $this->configContainer = $configContainer;
        $this->ui              = $ui;
    }

    public function run(ServerRequestInterface $request, GuiGatekeeperInterface $gatekeeper): ?ResponseInterface
    {
        if ($this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::DEMO_MODE) === true) {
            return null;
        }

        $this->ui->showHeader();

        $episode_id = (string) scrub_in($_REQUEST['podcast_episode_id']);
        $this->ui->showConfirmation(
            T_('Are You Sure?'),
            T_('The Podcast Episode will be deleted'),
            sprintf(
                '%s/podcast_episode.php?action=confirm_delete&podcast_episode_id=%s',
                $this->configContainer->getWebPath(),
                $episode_id
            ),
            1,
            'delete_podcast_episode'
        );

        $this->ui->showQueryStats();
        $this->ui->showFooter();

        return null;
    }
}
