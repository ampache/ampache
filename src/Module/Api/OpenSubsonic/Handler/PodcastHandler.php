<?php

declare(strict_types=1);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2026
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

namespace Ampache\Module\Api\OpenSubsonic\Handler;

use Ampache\Config\AmpConfig;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Module\Api\OpenSubsonic\OpenSubsonicResponseHandlerInterface;
use Ampache\Module\Api\OpenSubsonic_Api;
use Ampache\Module\Api\OpenSubsonic_Json_Data;
use Ampache\Module\Api\OpenSubsonic_Xml_Data;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Catalog\Catalog;
use Ampache\Module\Catalog\CountableTableEnum;
use Ampache\Module\Podcast\Exception\PodcastCreationException;
use Ampache\Module\Podcast\PodcastCreatorInterface;
use Ampache\Module\Podcast\PodcastDeleterInterface;
use Ampache\Module\Podcast\PodcastSyncerInterface;
use Ampache\Repository\Model\Podcast_Episode;
use Ampache\Repository\Model\User;
use Ampache\Repository\PodcastRepositoryInterface;

final class PodcastHandler implements PodcastHandlerInterface
{
    public function __construct(
        private readonly PodcastCreatorInterface $podcastCreator,
        private readonly PodcastDeleterInterface $podcastDeleter,
        private readonly PodcastRepositoryInterface $podcastRepository,
        private readonly PodcastSyncerInterface $podcastSyncer,
        private readonly OpenSubsonicResponseHandlerInterface $responseHandler,
        private readonly OpenSubsonic_Json_Data $openSubsonicJsonData,
        private readonly OpenSubsonic_Xml_Data $openSubsonicXmlData,
    ) {}

    /**
     * createPodcastChannel
     *
     * Adds a new Podcast channel.
     * https://opensubsonic.netlify.app/docs/endpoints/createpodcastchannel/
     * @param array<string, mixed> $input
     */
    public function createpodcastchannel(array $input, User $user): void
    {
        $url = $this->responseHandler->checkParameter($input, 'url', __FUNCTION__);
        if ($url === false) {
            return;
        }

        if (AmpConfig::get('podcast') && $user->access >= 75) {
            $catalogs = $user->get_catalogs('podcast');
            if (count($catalogs) > 0) {
                $catalog = Catalog::create_from_id($catalogs[0]);
                if (!$catalog instanceof Catalog) {
                    $this->responseHandler->errorOutput($input, OpenSubsonic_Api::SSERROR_DATA_NOTFOUND, __FUNCTION__);

                    return;
                }

                try {
                    $this->podcastCreator->create($url, $catalog);

                    $this->responseHandler->responseOutput($input, __FUNCTION__);
                } catch (PodcastCreationException) {
                    $this->responseHandler->errorOutput($input, OpenSubsonic_Api::SSERROR_GENERIC, __FUNCTION__);
                }
            } else {
                $this->responseHandler->errorOutput($input, OpenSubsonic_Api::SSERROR_UNAUTHORIZED, __FUNCTION__);
            }
        } else {
            $this->responseHandler->errorOutput($input, OpenSubsonic_Api::SSERROR_UNAUTHORIZED, __FUNCTION__);
        }
    }

    /**
     * deletePodcastChannel
     *
     * Deletes a Podcast channel.
     * https://opensubsonic.netlify.app/docs/endpoints/deletepodcastchannel/
     * @param array<string, mixed> $input
     */
    public function deletepodcastchannel(array $input, User $user): void
    {
        $sub_id = $this->responseHandler->checkParameter($input, 'id', __FUNCTION__);
        if ($sub_id === false) {
            return;
        }

        if (AmpConfig::get(ConfigurationKeyEnum::PODCAST) && $user->access >= AccessLevelEnum::MANAGER->value) {
            $podcast_id = OpenSubsonic_Api::getAmpacheId($sub_id);
            $podcast    = ($podcast_id)
                ? $this->podcastRepository->findById($podcast_id)
                : null;
            if ($podcast === null) {
                $this->responseHandler->errorOutput($input, OpenSubsonic_Api::SSERROR_DATA_NOTFOUND, __FUNCTION__);
            } else {
                $this->podcastDeleter->delete($podcast);

                $this->responseHandler->responseOutput($input, __FUNCTION__);
            }
        } else {
            $this->responseHandler->errorOutput($input, OpenSubsonic_Api::SSERROR_UNAUTHORIZED, __FUNCTION__);
        }
    }

    /**
     * deletePodcastEpisode
     *
     * Deletes a Podcast episode.
     * https://opensubsonic.netlify.app/docs/endpoints/deletepodcastepisode/
     * @param array<string, mixed> $input
     */
    public function deletepodcastepisode(array $input, User $user): void
    {
        $sub_id = $this->responseHandler->checkParameter($input, 'id', __FUNCTION__);
        if ($sub_id === false) {
            return;
        }

        if (AmpConfig::get('podcast') && $user->access >= 75) {
            $episode = new Podcast_Episode(OpenSubsonic_Api::getAmpacheId($sub_id));
            if ($episode->isNew()) {
                $this->responseHandler->errorOutput($input, OpenSubsonic_Api::SSERROR_DATA_NOTFOUND, __FUNCTION__);
            } elseif ($episode->remove()) {
                Catalog::count_table(CountableTableEnum::PODCAST_EPISODE);

                $this->responseHandler->responseOutput($input, __FUNCTION__);
            } else {
                $this->responseHandler->errorOutput($input, OpenSubsonic_Api::SSERROR_GENERIC, __FUNCTION__);
            }
        } else {
            $this->responseHandler->errorOutput($input, OpenSubsonic_Api::SSERROR_UNAUTHORIZED, __FUNCTION__);
        }
    }

    /**
     * downloadPodcastEpisode
     *
     * Request the server to start downloading a given Podcast episode.
     * https://opensubsonic.netlify.app/docs/endpoints/downloadpodcastepisode/
     * @param array<string, mixed> $input
     */
    public function downloadpodcastepisode(array $input, User $user): void
    {
        $sub_id = $this->responseHandler->checkParameter($input, 'id', __FUNCTION__);
        if ($sub_id === false) {
            return;
        }

        if (AmpConfig::get('podcast') && $user->access >= 75) {
            $episode = new Podcast_Episode(OpenSubsonic_Api::getAmpacheId($sub_id));
            if ($episode->isNew()) {
                $this->responseHandler->errorOutput($input, OpenSubsonic_Api::SSERROR_DATA_NOTFOUND, __FUNCTION__);
            } else {
                $this->podcastSyncer->syncEpisode($episode);

                $this->responseHandler->responseOutput($input, __FUNCTION__);
            }
        } else {
            $this->responseHandler->errorOutput($input, OpenSubsonic_Api::SSERROR_UNAUTHORIZED, __FUNCTION__);
        }
    }

    /**
     * getNewestPodcasts
     *
     * Returns the most recently published Podcast episodes.
     * https://opensubsonic.netlify.app/docs/endpoints/getnewestpodcasts/
     * @param array<string, mixed> $input
     */
    public function getnewestpodcasts(array $input, User $user): void
    {
        unset($user);
        $count = (int) ($input['count'] ?? AmpConfig::get('podcast_new_download'));
        if (!AmpConfig::get('podcast')) {
            $this->responseHandler->errorOutput($input, OpenSubsonic_Api::SSERROR_DATA_NOTFOUND, __FUNCTION__);

            return;
        }

        $episodes = Catalog::get_newest_podcasts($count);
        $format   = (string) ($input['f'] ?? 'xml');
        if ($format === 'xml') {
            $response = $this->responseHandler->addXmlResponse(__FUNCTION__);
            $response = $this->openSubsonicXmlData->addNewestPodcasts($response, $episodes);
        } else {
            $response = $this->responseHandler->addJsonResponse(__FUNCTION__);
            $response = $this->openSubsonicJsonData->addNewestPodcasts($response, $episodes);
        }
        $this->responseHandler->responseOutput($input, __FUNCTION__, $response);
    }

    /**
     * getPodcastEpisode [OS]
     *
     * Returns details for a podcast episode.
     * https://opensubsonic.netlify.app/docs/endpoints/getpodcastepisode/
     * @param array<string, mixed> $input
     */
    public function getpodcastepisode(array $input, User $user): void
    {
        unset($user);
        $sub_id = $this->responseHandler->checkParameter($input, 'id', __FUNCTION__);
        if ($sub_id === false) {
            return;
        }

        $episode_id = OpenSubsonic_Api::getAmpacheId($sub_id);
        if (!$episode_id) {
            $this->responseHandler->errorOutput($input, OpenSubsonic_Api::SSERROR_DATA_NOTFOUND, __FUNCTION__);

            return;
        }
        $episode = new Podcast_Episode($episode_id);
        if ($episode->isNew()) {
            $this->responseHandler->errorOutput($input, OpenSubsonic_Api::SSERROR_DATA_NOTFOUND, __FUNCTION__);

            return;
        }

        $format = (string) ($input['f'] ?? 'xml');
        if ($format === 'xml') {
            $response = $this->responseHandler->addXmlResponse(__FUNCTION__);
            $response = $this->openSubsonicXmlData->addPodcastEpisode($response, $episode);
        } else {
            $response = $this->responseHandler->addJsonResponse(__FUNCTION__);
            $response = $this->openSubsonicJsonData->addPodcastEpisode($response, $episode);
        }
        $this->responseHandler->responseOutput($input, __FUNCTION__, $response);
    }

    /**
     * getPodcasts
     *
     * Returns all Podcast channels the server subscribes to, and (optionally) their episodes.
     * https://opensubsonic.netlify.app/docs/endpoints/getpodcasts/
     * @param array<string, mixed> $input
     */
    public function getpodcasts(array $input, User $user): void
    {
        $sub_id          = $input['id'] ?? null;
        $includeEpisodes = make_bool($input['includeEpisodes'] ?? true);

        if (!AmpConfig::get(ConfigurationKeyEnum::PODCAST)) {
            $this->responseHandler->errorOutput($input, OpenSubsonic_Api::SSERROR_DATA_NOTFOUND, __FUNCTION__);
        }

        $podcast_id = ($sub_id)
            ? OpenSubsonic_Api::getAmpacheId($sub_id)
            : null;
        if ($podcast_id) {
            $podcast = $this->podcastRepository->findById($podcast_id);
            if ($podcast === null) {
                $this->responseHandler->errorOutput($input, OpenSubsonic_Api::SSERROR_DATA_NOTFOUND, __FUNCTION__);

                return;
            }

            $podcasts = [$podcast];
        } else {
            $podcasts = Catalog::get_podcasts(User::get_user_catalogs($user->id));
        }

        $format = (string) ($input['f'] ?? 'xml');
        if ($format === 'xml') {
            $response = $this->responseHandler->addXmlResponse(__FUNCTION__);
            $response = $this->openSubsonicXmlData->addPodcasts($response, $podcasts, $includeEpisodes, $sub_id);
        } else {
            $response = $this->responseHandler->addJsonResponse(__FUNCTION__);
            $response = $this->openSubsonicJsonData->addPodcasts($response, $podcasts, $includeEpisodes, $sub_id);
        }
        $this->responseHandler->responseOutput($input, __FUNCTION__, $response);
    }

    /**
     * refreshPodcasts
     *
     * Requests the server to check for new Podcast episodes.
     * https://opensubsonic.netlify.app/docs/endpoints/refreshpodcasts/
     * @param array<string, mixed> $input
     */
    public function refreshpodcasts(array $input, User $user): void
    {
        if (AmpConfig::get('podcast') && $user->access >= 75) {
            $podcasts = Catalog::get_podcasts(User::get_user_catalogs($user->id));

            $podcastSyncer = $this->podcastSyncer;

            foreach ($podcasts as $podcast) {
                $podcastSyncer->sync($podcast, true);
            }
            $this->responseHandler->responseOutput($input, __FUNCTION__);
        } else {
            $this->responseHandler->errorOutput($input, OpenSubsonic_Api::SSERROR_UNAUTHORIZED, __FUNCTION__);
        }
    }
}
