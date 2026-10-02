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

use Ampache\Module\Api\OpenSubsonic\OpenSubsonicResponseHandlerInterface;
use Ampache\Module\Api\OpenSubsonic\SonicAnalysisPluginResolverInterface;
use Ampache\Module\Api\OpenSubsonic_Json_Data;
use Ampache\Module\Api\OpenSubsonic_Xml_Data;
use Ampache\Plugin\PluginSonicAnalysisInterface;
use Ampache\Repository\Model\User;

final class SystemHandler implements SystemHandlerInterface
{
    public function __construct(
        private readonly OpenSubsonicResponseHandlerInterface $responseHandler,
        private readonly OpenSubsonic_Json_Data $openSubsonicJsonData,
        private readonly OpenSubsonic_Xml_Data $openSubsonicXmlData,
        private readonly SonicAnalysisPluginResolverInterface $sonicAnalysisPluginResolver,
    ) {}

    /**
     * getLicense
     *
     * Get details about the software license.
     * https://opensubsonic.netlify.app/docs/endpoints/getlicense/
     * @param array<string, mixed> $input
     */
    public function getlicense(array $input, User $user): void
    {
        unset($user);

        $format = (string) ($input['f'] ?? 'xml');
        if ($format === 'xml') {
            $response = $this->responseHandler->addXmlResponse(__FUNCTION__);
            $response = $this->openSubsonicXmlData->addLicense($response);
        } else {
            $response = $this->responseHandler->addJsonResponse(__FUNCTION__);
            $response = $this->openSubsonicJsonData->addLicense($response);
        }
        $this->responseHandler->responseOutput($input, __FUNCTION__, $response);
    }

    /**
     * getOpenSubsonicExtensions
     *
     * List the OpenSubsonic extensions supported by this server.
     * https://opensubsonic.netlify.app/docs/endpoints/getopensubsonicextensions/
     * @param array<string, mixed> $input
     */
    public function getopensubsonicextensions(array $input, User $user): void
    {
        // Clients match these names literally, so they stay exactly as the spec writes them. `template` is a doc
        // placeholder rather than a capability, so it is deliberately absent.
        $extensions = [
            'apiKeyAuthentication' => [1],
            'getPodcastEpisode' => [1],
            'indexBasedQueue' => [1],
            'formPost' => [1],
            'playbackReport' => [1],
            'songLyrics' => [1],
            'topSongsByArtistId' => [1],
            'transcodeOffset' => [1],
            'transcoding' => [1],
        ];

        // sonicSimilarity needs an audio-analysis backend, so it is only claimed while a plugin can actually answer.
        if ($this->sonicAnalysisPluginResolver->resolve($user) instanceof PluginSonicAnalysisInterface) {
            $extensions['sonicSimilarity'] = [1];
        }

        ksort($extensions);

        $format = (string) ($input['f'] ?? 'xml');
        if ($format === 'xml') {
            $response = $this->responseHandler->addXmlResponse(__FUNCTION__);
            $response = $this->openSubsonicXmlData->addOpenSubsonicExtensions($response, $extensions);
        } else {
            $response = $this->responseHandler->addJsonResponse(__FUNCTION__);
            $response = $this->openSubsonicJsonData->addOpenSubsonicExtensions($response, $extensions);
        }
        $this->responseHandler->responseOutput($input, __FUNCTION__, $response);
    }

    /**
     * getScanStatus
     *
     * Returns the current status for media library scanning.
     * https://opensubsonic.netlify.app/docs/endpoints/getscanstatus/
     * @param array<string, mixed> $input
     */
    public function getscanstatus(array $input, User $user): void
    {
        $format = (string) ($input['f'] ?? 'xml');
        if ($format === 'xml') {
            $response = $this->responseHandler->addXmlResponse(__FUNCTION__);
            $response = $this->openSubsonicXmlData->addScanStatus($response, $user);
        } else {
            $response = $this->responseHandler->addJsonResponse(__FUNCTION__);
            $response = $this->openSubsonicJsonData->addScanStatus($response, $user);
        }
        $this->responseHandler->responseOutput($input, __FUNCTION__, $response);
    }

    /**
     * ping
     *
     * Used to test connectivity with the server.
     * https://opensubsonic.netlify.app/docs/endpoints/ping/
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
     * https://opensubsonic.netlify.app/docs/endpoints/startscan/
     * @param array<string, mixed> $input
     */
    public function startscan(array $input, User $user): void
    {
        $format = (string) ($input['f'] ?? 'xml');
        if ($format === 'xml') {
            $response = $this->responseHandler->addXmlResponse(__FUNCTION__);
            $response = $this->openSubsonicXmlData->addScanStatus($response, $user);
        } else {
            $response = $this->responseHandler->addJsonResponse(__FUNCTION__);
            $response = $this->openSubsonicJsonData->addScanStatus($response, $user);
        }
        $this->responseHandler->responseOutput($input, __FUNCTION__, $response);
    }

    /**
     * tokenInfo [OS]
     *
     * Returns information about an API key.
     * https://opensubsonic.netlify.app/docs/endpoints/tokeninfo/
     * @param array<string, mixed> $input
     */
    public function tokeninfo(array $input, User $user): void
    {
        $format = (string) ($input['f'] ?? 'xml');
        if ($format === 'xml') {
            $response = $this->responseHandler->addXmlResponse(__FUNCTION__);
            $response = $this->openSubsonicXmlData->addTokenInfo($response, $user);
        } else {
            $response = $this->responseHandler->addJsonResponse(__FUNCTION__);
            $response = $this->openSubsonicJsonData->addTokenInfo($response, $user);
        }
        $this->responseHandler->responseOutput($input, __FUNCTION__, $response);
    }
}
