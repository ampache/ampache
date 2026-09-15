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

use Ampache\Module\Api\Subsonic\SubsonicResponseHandlerInterface;
use Ampache\Module\Api\Subsonic_Api;
use Ampache\Module\Api\Subsonic_Json_Data;
use Ampache\Module\Api\Subsonic_Xml_Data;
use Ampache\Repository\Model\User;

final class SystemHandler implements SystemHandlerInterface
{
    public function __construct(
        private readonly SubsonicResponseHandlerInterface $responseHandler,
        private readonly Subsonic_Json_Data $subsonicJsonData,
        private readonly Subsonic_Xml_Data $subsonicXmlData,
    ) {}

    /**
     * getLicense
     *
     * Get details about the software license.
     * https://www.subsonic.org/pages/api.jsp#getlicense
     * @param array<string, mixed> $input
     */
    public function getlicense(array $input, User $user): void
    {
        unset($user);

        $format = (string) ($input['f'] ?? 'xml');
        if ($format === 'xml') {
            $response = $this->responseHandler->addXmlResponse(__FUNCTION__);
            $response = $this->subsonicXmlData->addLicense($response);
        } else {
            $response = $this->responseHandler->addJsonResponse(__FUNCTION__);
            $response = $this->subsonicJsonData->addLicense($response);
        }
        $this->responseHandler->responseOutput($input, __FUNCTION__, $response);
    }

    /**
     * getOpenSubsonicExtensions [OS] REMOVED
     * @param array<string, mixed> $input
     */
    public function getopensubsonicextensions(array $input, User $user): void
    {
        unset($user);

        $this->responseHandler->errorOutput($input, Subsonic_Api::SSERROR_APIVERSION_SERVER, __FUNCTION__);
    }

    /**
     * getScanStatus
     *
     * Returns the current status for media library scanning.
     * https://www.subsonic.org/pages/api.jsp#getscanstatus
     * @param array<string, mixed> $input
     */
    public function getscanstatus(array $input, User $user): void
    {
        $format = (string) ($input['f'] ?? 'xml');
        if ($format === 'xml') {
            $response = $this->responseHandler->addXmlResponse(__FUNCTION__);
            $response = $this->subsonicXmlData->addScanStatus($response, $user);
        } else {
            $response = $this->responseHandler->addJsonResponse(__FUNCTION__);
            $response = $this->subsonicJsonData->addScanStatus($response, $user);
        }
        $this->responseHandler->responseOutput($input, __FUNCTION__, $response);
    }

    /**
     * ping
     *
     * Used to test connectivity with the server.
     * https://www.subsonic.org/pages/api.jsp#ping
     * @param array<string, mixed> $input
     */
    public function ping(array $input, User $user): void
    {
        unset($user);

        $this->responseHandler->responseOutput($input, __FUNCTION__);
    }

    /**
     * startScan
     *
     * Initiates a rescan of the media libraries.
     * https://www.subsonic.org/pages/api.jsp#startscan
     * @param array<string, mixed> $input
     */
    public function startscan(array $input, User $user): void
    {
        $format = (string) ($input['f'] ?? 'xml');
        if ($format === 'xml') {
            $response = $this->responseHandler->addXmlResponse(__FUNCTION__);
            $response = $this->subsonicXmlData->addScanStatus($response, $user);
        } else {
            $response = $this->responseHandler->addJsonResponse(__FUNCTION__);
            $response = $this->subsonicJsonData->addScanStatus($response, $user);
        }
        $this->responseHandler->responseOutput($input, __FUNCTION__, $response);
    }

    /**
     * tokenInfo [OS] REMOVED
     *
     * Returns information about an API key.
     * https://opensubsonic.netlify.app/docs/endpoints/tokeninfo/
     * @param array<string, mixed> $input
     */
    public function tokeninfo(array $input, User $user): void
    {
        unset($user);

        $this->responseHandler->errorOutput($input, Subsonic_Api::SSERROR_APIVERSION_SERVER, __FUNCTION__);
    }
}
