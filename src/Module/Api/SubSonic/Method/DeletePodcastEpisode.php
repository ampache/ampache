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
use Ampache\Module\Podcast\PodcastEpisodeDeleterInterface;
use Ampache\Repository\PodcastEpisodeRepositoryInterface;

/**
 * Delete a podcast episode
 * Takes the podcast episode id in parameter.
 */
final class DeletePodcastEpisode implements SubsonicApiMethodInterface
{
    private PodcastEpisodeDeleterInterface $podcastEpisodeDeleter;

    private PodcastEpisodeRepositoryInterface $podcastEpisodeRepository;

    public function __construct(
        PodcastEpisodeDeleterInterface $podcastEpisodeDeleter,
        PodcastEpisodeRepositoryInterface $podcastEpisodeRepository
    ) {
        $this->podcastEpisodeDeleter    = $podcastEpisodeDeleter;
        $this->podcastEpisodeRepository = $podcastEpisodeRepository;
    }

    public function handle(array $input): void
    {
        $id = Subsonic_Api::check_parameter($input, 'id');

        if (AmpConfig::get('podcast') && Access::check('interface', 75)) {
            $episode = $this->podcastEpisodeRepository->findById(
                (int) Subsonic_Xml_Data::getAmpacheId($id)
            );
            if ($episode !== null) {
                if ($this->podcastEpisodeDeleter->delete($episode)) {
                    $response = Subsonic_Xml_Data::createSuccessResponse('deletepodcastepisode');
                } else {
                    $response = Subsonic_Xml_Data::createError(
                        Subsonic_Xml_Data::SSERROR_GENERIC,
                        '',
                        'deletepodcastepisode'
                    );
                }
            } else {
                $response = Subsonic_Xml_Data::createError(
                    Subsonic_Xml_Data::SSERROR_DATA_NOTFOUND,
                    '',
                    'deletepodcastepisode'
                );
            }
        } else {
            $response = Subsonic_Xml_Data::createError(
                Subsonic_Xml_Data::SSERROR_UNAUTHORIZED,
                '',
                'deletepodcastepisode'
            );
        }
        Subsonic_Api::apiOutput($input, $response);
    }
}
