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

use Ampache\Config\AmpConfig;
use Ampache\Config\ConfigContainerInterface;
use Ampache\Module\Api\SubSonic\Subsonic_Api;
use Ampache\Module\Api\SubSonic\Subsonic_Xml_Data;
use Ampache\Module\Podcast\PodcastByCatalogLoaderInterface;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\PodcastRepositoryInterface;

/**
 * Get all podcast channels.
 * Takes the optional includeEpisodes and channel id in parameters
 */
final class GetPodcastsMethod implements SubsonicApiMethodInterface
{
    private ConfigContainerInterface $configContainer;

    private ModelFactoryInterface $modelFactory;

    private PodcastByCatalogLoaderInterface $podcastByCatalogLoader;

    private PodcastRepositoryInterface $podcastRepository;

    public function __construct(
        ConfigContainerInterface $configContainer,
        ModelFactoryInterface $modelFactory,
        PodcastByCatalogLoaderInterface $podcastByCatalogLoader,
        PodcastRepositoryInterface $podcastRepository
    ) {
        $this->configContainer        = $configContainer;
        $this->modelFactory           = $modelFactory;
        $this->podcastByCatalogLoader = $podcastByCatalogLoader;
        $this->podcastRepository      = $podcastRepository;
    }

    public function handle(array $input): void
    {
        $podcastId       = $input['id'];
        $includeEpisodes = !isset($input['includeEpisodes']) || $input['includeEpisodes'] === "true";

        if (AmpConfig::get('podcast')) {
            if ($podcastId) {
                $podcast = $this->podcastRepository->findById((int) Subsonic_Xml_Data::getAmpacheId($podcastId));

                if ($podcast !== null) {
                    $response = Subsonic_Xml_Data::createSuccessResponse('getpodcasts');
                    Subsonic_Xml_Data::addPodcasts($response, [$podcast], $includeEpisodes);
                } else {
                    $response = Subsonic_Xml_Data::createError(
                        Subsonic_Xml_Data::SSERROR_DATA_NOTFOUND,
                        '',
                        'getpodcasts'
                    );
                }
            } else {
                $response = Subsonic_Xml_Data::createSuccessResponse('getpodcasts');
                Subsonic_Xml_Data::addPodcasts(
                    $response,
                    $this->podcastByCatalogLoader->load(),
                    $includeEpisodes
                );
            }
        } else {
            $response = Subsonic_Xml_Data::createError(Subsonic_Xml_Data::SSERROR_UNAUTHORIZED, '', 'getpodcasts');
        }
        Subsonic_Api::apiOutput($input, $response);
    }
}
