<?php
/*
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
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Module\Application\ApplicationActionInterface;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\Util\UiInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class ShowAction implements ApplicationActionInterface
{
    public const REQUEST_KEY = 'show';

    private ConfigContainerInterface $configContainer;

    private UiInterface $ui;

    private ModelFactoryInterface $modelFactory;

    public function __construct(
        ConfigContainerInterface $configContainer,
        UiInterface $ui,
        ModelFactoryInterface $modelFactory
    ) {
        $this->configContainer = $configContainer;
        $this->ui              = $ui;
        $this->modelFactory    = $modelFactory;
    }

    public function run(ServerRequestInterface $request, GuiGatekeeperInterface $gatekeeper): ?ResponseInterface
    {
        if ($this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::PODCAST) === false) {
            return null;
        }

        $this->ui->showHeader();

        $podcast_id = (int) filter_input(INPUT_GET, 'podcast', FILTER_SANITIZE_NUMBER_INT);
        if ($podcast_id > 0) {
            $podcast = $this->modelFactory->createPodcast($podcast_id);
            $podcast->format();
            $object_ids  = $podcast->get_episodes();
            $object_type = 'podcast_episode';
            $this->ui->show(
                'show_podcast.inc.php',
                [
                    'podcast' => $podcast,
                    'object_ids' => $object_ids,
                    'object_type' => $object_type
                ]
            );
        }

        $this->ui->showQueryStats();
        $this->ui->showFooter();

        return null;
    }
}
