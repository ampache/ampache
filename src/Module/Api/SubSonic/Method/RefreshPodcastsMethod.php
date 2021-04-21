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
use Ampache\Module\Api\SubSonic\Subsonic_Api;
use Ampache\Module\Api\SubSonic\Subsonic_Xml_Data;
use Ampache\Module\Authorization\Access;
use Ampache\Module\Podcast\PodcastByCatalogLoaderInterface;
use Ampache\Module\Podcast\PodcastSyncerInterface;

/**
 * Request the server to check for new podcast episodes.
 * Takes no parameters.
 */
final class RefreshPodcastsMethod implements SubsonicApiMethodInterface
{
    private PodcastByCatalogLoaderInterface $podcastByCatalogLoader;

    private PodcastSyncerInterface $podcastSyncer;

    public function __construct(
        PodcastByCatalogLoaderInterface $podcastByCatalogLoader,
        PodcastSyncerInterface $podcastSyncer
    ) {
        $this->podcastByCatalogLoader = $podcastByCatalogLoader;
        $this->podcastSyncer          = $podcastSyncer;
    }

    public function handle(array $input): void
    {
        if (AmpConfig::get('podcast') && Access::check('interface', 75)) {
            $podcasts = $this->podcastByCatalogLoader->load();
            foreach ($podcasts as $podcast) {
                $this->podcastSyncer->sync($podcast, true);
            }
            $response = Subsonic_Xml_Data::createSuccessResponse('refreshpodcasts');
        } else {
            $response = Subsonic_Xml_Data::createError(Subsonic_Xml_Data::SSERROR_UNAUTHORIZED, '', 'refreshpodcasts');
        }
        Subsonic_Api::apiOutput($input, $response);
    }
}
