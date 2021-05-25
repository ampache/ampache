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
use Ampache\Gui\TalFactoryInterface;
use Ampache\Module\Application\ApplicationActionInterface;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\Podcast\Gui\PodcastGuiFactoryInterface;
use Ampache\Module\Util\UiInterface;
use Ampache\Repository\PodcastRepositoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class ShowAction implements ApplicationActionInterface
{
    public const REQUEST_KEY = 'show';

    private ConfigContainerInterface $configContainer;

    private UiInterface $ui;

    private TalFactoryInterface $talFactory;

    private PodcastGuiFactoryInterface $podcastGuiFactory;

    private PodcastRepositoryInterface $podcastRepository;

    public function __construct(
        ConfigContainerInterface $configContainer,
        UiInterface $ui,
        TalFactoryInterface $talFactory,
        PodcastGuiFactoryInterface $podcastGuiFactory,
        PodcastRepositoryInterface $podcastRepository
    ) {
        $this->configContainer   = $configContainer;
        $this->ui                = $ui;
        $this->talFactory        = $talFactory;
        $this->podcastGuiFactory = $podcastGuiFactory;
        $this->podcastRepository = $podcastRepository;
    }

    public function run(ServerRequestInterface $request, GuiGatekeeperInterface $gatekeeper): ?ResponseInterface
    {
        if ($this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::PODCAST) === false) {
            return null;
        }
        $podcastId = (int) ($request->getQueryParams()['podcast'] ?? 0);

        $this->ui->showHeader();
        if ($podcastId > 0) {
            $podcast = $this->podcastRepository->findById($podcastId);

            echo $this->talFactory
                ->createTalView()
                ->setTemplate('podcast/podcast.xhtml')
                ->setContext('PODCAST', $this->podcastGuiFactory->createPodcastViewAdapter($podcast))
                ->setContext('PODCAST_ID', $podcastId)
                ->setContext('WEB_PATH', $this->configContainer->getWebPath())
                ->render();
        }

        $this->ui->showQueryStats();
        $this->ui->showFooter();

        return null;
    }
}
