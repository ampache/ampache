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
use Ampache\Module\Podcast\PodcastDeleterInterface;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\PodcastRepositoryInterface;

/**
 * Delete an existing podcast channel
 * Takes the podcast id in parameter.
 */
final class DeletePodcastChannelMethod implements SubsonicApiMethodInterface
{
    private PodcastDeleterInterface $podcastDeleter;

    private ModelFactoryInterface $modelFactory;

    private PodcastRepositoryInterface $podcastRepository;

    public function __construct(
        PodcastDeleterInterface $podcastDeleter,
        ModelFactoryInterface $modelFactory,
        PodcastRepositoryInterface $podcastRepository
    ) {
        $this->podcastDeleter    = $podcastDeleter;
        $this->modelFactory      = $modelFactory;
        $this->podcastRepository = $podcastRepository;
    }

    public function handle(array $input): void
    {
        $podcast_id = (int) Subsonic_Api::check_parameter($input, 'id');

        if (AmpConfig::get('podcast') && Access::check('interface', 75)) {
            $podcast = $this->podcastRepository->findById((int) Subsonic_Xml_Data::getAmpacheId($podcast_id));
            if ($podcast !== null) {
                if ($this->podcastDeleter->delete($podcast)) {
                    $response = Subsonic_Xml_Data::createSuccessResponse('deletepodcastchannel');
                } else {
                    $response = Subsonic_Xml_Data::createError(Subsonic_Xml_Data::SSERROR_GENERIC, '',
                        'deletepodcastchannel');
                }
            } else {
                $response = Subsonic_Xml_Data::createError(Subsonic_Xml_Data::SSERROR_DATA_NOTFOUND, '',
                    'deletepodcastchannel');
            }
        } else {
            $response = Subsonic_Xml_Data::createError(Subsonic_Xml_Data::SSERROR_UNAUTHORIZED, '',
                'deletepodcastchannel');
        }
        Subsonic_Api::apiOutput($input, $response);
    }
}
