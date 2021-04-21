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

namespace Ampache\Module\Api\SubSonic\Method;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Module\Api\SubSonic\Subsonic_Api;
use Ampache\Module\Api\SubSonic\Subsonic_Xml_Data;
use Ampache\Repository\CatalogRepositoryInterface;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\PodcastEpisodeRepositoryInterface;

/**
 * Get the most recently published podcast episodes.
 * Takes the optional count in parameters
 */
final class GetNewestPodcastsMethod implements SubsonicApiMethodInterface
{
    private CatalogRepositoryInterface $catalogRepository;

    private PodcastEpisodeRepositoryInterface $podcastEpisodeRepository;

    private ModelFactoryInterface $modelFactory;

    private ConfigContainerInterface $configContainer;

    public function __construct(
        CatalogRepositoryInterface $catalogRepository,
        PodcastEpisodeRepositoryInterface $podcastEpisodeRepository,
        ModelFactoryInterface $modelFactory,
        ConfigContainerInterface $configContainer
    ) {
        $this->catalogRepository        = $catalogRepository;
        $this->podcastEpisodeRepository = $podcastEpisodeRepository;
        $this->modelFactory             = $modelFactory;
        $this->configContainer          = $configContainer;
    }

    /**
     * Get the most recently published podcast episodes.
     * Takes the optional count in parameters
     */
    public function handle(array $input): void
    {
        if ($this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::PODCAST)) {
            $count = (int) ($input['count'] ?: $this->configContainer->get(ConfigurationKeyEnum::PODCAST_NEW_DOWNLOAD));

            $response = Subsonic_Xml_Data::createSuccessResponse('getnewestpodcasts');

            $catalogIds = $this->catalogRepository->getList('podcast');
            $episodes   = [];

            foreach ($catalogIds as $catalogId) {
                $newestEpisodes = $this->podcastEpisodeRepository->getNewestPodcastEpisodes(
                    (int) $catalogId,
                    $count
                );
                foreach ($newestEpisodes as $episode) {
                    $episodes[] = $episode;
                }
            }

            Subsonic_Xml_Data::addNewestPodcastEpisodes($response, $episodes);
        } else {
            $response = Subsonic_Xml_Data::createError(
                Subsonic_Xml_Data::SSERROR_UNAUTHORIZED,
                '',
                'getnewestpodcasts'
            );
        }
        Subsonic_Api::apiOutput($input, $response);
    }
}
