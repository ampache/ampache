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

namespace Ampache\Module\Podcast\Gui;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\Model\PodcastEpisodeInterface;
use Ampache\Repository\Model\PodcastInterface;
use Ampache\Repository\PodcastEpisodeRepositoryInterface;

/**
 * Creates podcast related gui views
 */
final class PodcastGuiFactory implements PodcastGuiFactoryInterface
{
    private ModelFactoryInterface $modelFactory;

    private PodcastEpisodeRepositoryInterface $podcastEpisodeRepository;

    private ConfigContainerInterface $configContainer;

    public function __construct(
        ModelFactoryInterface $modelFactory,
        PodcastEpisodeRepositoryInterface $podcastEpisodeRepository,
        ConfigContainerInterface $configContainer
    ) {
        $this->modelFactory             = $modelFactory;
        $this->podcastEpisodeRepository = $podcastEpisodeRepository;
        $this->configContainer          = $configContainer;
    }

    public function createPodcastViewAdapter(
        PodcastInterface $podcast
    ): PodcastViewAdapterInterface {
        return new PodcastViewAdapter(
            $this->podcastEpisodeRepository,
            $this->modelFactory,
            $podcast
        );
    }

    public function createPodcastEpisodeViewAdapter(
        PodcastEpisodeInterface $podcastEpisode
    ): PodcastEpisodeViewAdapterInterface {
        return new PodcastEpisodeViewAdapter(
            $this->configContainer,
            $podcastEpisode
        );
    }
}
