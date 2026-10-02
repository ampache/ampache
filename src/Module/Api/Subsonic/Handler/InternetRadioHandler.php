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

namespace Ampache\Module\Api\Subsonic\Handler;

use Ampache\Config\AmpConfig;
use Ampache\Module\Api\Subsonic\SubsonicResponseHandlerInterface;
use Ampache\Module\Api\Subsonic_Api;
use Ampache\Module\Api\Subsonic_Json_Data;
use Ampache\Module\Api\Subsonic_Xml_Data;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Repository\LiveStreamRepositoryInterface;
use Ampache\Repository\Model\Live_Stream;
use Ampache\Repository\Model\User;

final class InternetRadioHandler implements InternetRadioHandlerInterface
{
    public function __construct(
        private readonly LiveStreamRepositoryInterface $liveStreamRepository,
        private readonly SubsonicResponseHandlerInterface $responseHandler,
        private readonly Subsonic_Json_Data $subsonicJsonData,
        private readonly Subsonic_Xml_Data $subsonicXmlData,
    ) {}

    /**
     * createInternetRadioStation
     *
     * Adds a new internet radio station.
     * https://www.subsonic.org/pages/api.jsp#createinternetradiostation
     * @param array<string, mixed> $input
     */
    public function createinternetradiostation(array $input, User $user): void
    {
        $url = $this->responseHandler->checkParameter($input, 'streamUrl', __FUNCTION__);
        if ($url === false) {
            return;
        }

        $name = $this->responseHandler->checkParameter($input, 'name', __FUNCTION__);
        if ($name === false) {
            return;
        }

        $site_url = filter_var(urldecode($input['homepageUrl']), FILTER_VALIDATE_URL) ?: '';
        $catalogs = User::get_user_catalogs($user->id, 'music');
        if (AmpConfig::get('live_stream') && $user->access >= 75) {
            $data = [
                "name" => $name,
                "url" => $url,
                "codec" => 'mp3',
                "catalog" => $catalogs[0],
                "site_url" => $site_url
            ];
            if (!Live_Stream::create($data)) {
                $this->responseHandler->errorOutput($input, Subsonic_Api::SSERROR_DATA_NOTFOUND, __FUNCTION__);

                return;
            }
            $this->responseHandler->responseOutput($input, __FUNCTION__);
        } else {
            $this->responseHandler->errorOutput($input, Subsonic_Api::SSERROR_UNAUTHORIZED, __FUNCTION__);
        }
    }

    /**
     * deleteInternetRadioStation
     *
     * Deletes an existing internet radio station.
     * https://www.subsonic.org/pages/api.jsp#deleteinternetradiostation
     * @param array<string, mixed> $input
     */
    public function deleteinternetradiostation(array $input, User $user): void
    {
        $sub_id = $this->responseHandler->checkParameter($input, 'id', __FUNCTION__);
        if ($sub_id === false) {
            return;
        }

        $liveStreamRepository = $this->liveStreamRepository;

        if (AmpConfig::get('live_stream') && $user->access >= AccessLevelEnum::MANAGER->value) {
            $radio_id   = Subsonic_Api::getAmpacheId($sub_id);
            $liveStream = ($radio_id)
                ? $liveStreamRepository->findById($radio_id)
                : null;

            if ($liveStream === null) {
                $this->responseHandler->errorOutput($input, Subsonic_Api::SSERROR_DATA_NOTFOUND, __FUNCTION__);
            } else {
                $liveStreamRepository->delete($liveStream);

                $this->responseHandler->responseOutput($input, __FUNCTION__);
            }
        } else {
            $this->responseHandler->errorOutput($input, Subsonic_Api::SSERROR_DATA_NOTFOUND, __FUNCTION__);
        }
    }

    /**
     * getInternetRadioStations
     *
     * Returns all internet radio stations.
     * https://www.subsonic.org/pages/api.jsp#getinternetradiostations
     * @param array<string, mixed> $input
     */
    public function getinternetradiostations(array $input, User $user): void
    {
        $radios = $this->liveStreamRepository->findAll($user);
        $format = (string) ($input['f'] ?? 'xml');
        if ($format === 'xml') {
            $response = $this->responseHandler->addXmlResponse(__FUNCTION__);
            $response = $this->subsonicXmlData->addInternetRadioStations($response, $radios);
        } else {
            $response = $this->responseHandler->addJsonResponse(__FUNCTION__);
            $response = $this->subsonicJsonData->addInternetRadioStations($response, $radios);
        }
        $this->responseHandler->responseOutput($input, __FUNCTION__, $response);
    }

    /**
     * updateInternetRadioStation
     *
     * Updates an existing internet radio station.
     * https://www.subsonic.org/pages/api.jsp#updateinternetradiostation
     * @param array<string, mixed> $input
     */
    public function updateinternetradiostation(array $input, User $user): void
    {
        $sub_id = $this->responseHandler->checkParameter($input, 'id', __FUNCTION__);
        if ($sub_id === false) {
            return;
        }

        $url = $this->responseHandler->checkParameter($input, 'streamUrl', __FUNCTION__);
        if ($url === false) {
            return;
        }

        $name = $this->responseHandler->checkParameter($input, 'name', __FUNCTION__);
        if ($name === false) {
            return;
        }

        $site_url = filter_var(urldecode($input['homepageUrl']), FILTER_VALIDATE_URL) ?: '';

        if (AmpConfig::get('live_stream') && $user->access >= 75) {
            $internetradiostation = new Live_Stream(Subsonic_Api::getAmpacheId($sub_id));
            if ($internetradiostation->id > 0) {
                $data = [
                    "name" => $name,
                    "url" => $url,
                    "codec" => 'mp3',
                    "site_url" => $site_url
                ];
                if ($internetradiostation->update($data)) {
                    $this->responseHandler->responseOutput($input, __FUNCTION__);
                } else {
                    $this->responseHandler->errorOutput($input, Subsonic_Api::SSERROR_UNAUTHORIZED, __FUNCTION__);
                }
            } else {
                $this->responseHandler->errorOutput($input, Subsonic_Api::SSERROR_DATA_NOTFOUND, __FUNCTION__);
            }
        } else {
            $this->responseHandler->errorOutput($input, Subsonic_Api::SSERROR_UNAUTHORIZED, __FUNCTION__);
        }
    }
}
