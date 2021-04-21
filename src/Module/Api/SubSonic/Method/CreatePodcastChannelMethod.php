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
use Ampache\Module\Podcast\PodcastCreatorInterface;
use Ampache\Repository\CatalogRepositoryInterface;

/**
 * Add a new podcast channel.
 * Takes the podcast url in parameter.
 */
final class CreatePodcastChannelMethod implements SubsonicApiMethodInterface
{
    private CatalogRepositoryInterface $catalogRepository;

    private PodcastCreatorInterface $podcastCreator;

    public function __construct(
        CatalogRepositoryInterface $catalogRepository,
        PodcastCreatorInterface $podcastCreator
    ) {
        $this->catalogRepository = $catalogRepository;
        $this->podcastCreator    = $podcastCreator;
    }

    public function handle(array $input): void
    {
        $url = Subsonic_Api::check_parameter($input, 'url');

        if (AmpConfig::get('podcast') && Access::check('interface', 75)) {
            $catalogs = $this->catalogRepository->getList('podcast');
            if (count($catalogs) > 0) {
                if ($this->podcastCreator->create($url, $catalogs[0])) {
                    $response = Subsonic_Xml_Data::createSuccessResponse('createpodcastchannel');
                } else {
                    $response = Subsonic_Xml_Data::createError(
                        Subsonic_Xml_Data::SSERROR_GENERIC,
                        '',
                        'createpodcastchannel'
                    );
                }
            } else {
                $response = Subsonic_Xml_Data::createError(
                    Subsonic_Xml_Data::SSERROR_UNAUTHORIZED,
                    '',
                    'createpodcastchannel'
                );
            }
        } else {
            $response = Subsonic_Xml_Data::createError(
                Subsonic_Xml_Data::SSERROR_UNAUTHORIZED,
                '',
                'createpodcastchannel'
            );
        }
        Subsonic_Api::apiOutput($input, $response);
    }
}
