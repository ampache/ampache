<?php

declare(strict_types=0);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2024
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

namespace Ampache\Module\LastFm;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Module\System\Core;
use WpOrg\Requests\Requests;
use SimpleXMLElement;

final class LastFmQuery implements LastFmQueryInterface
{
    private const API_URL = 'http://ws.audioscrobbler.com/2.0/?method=';

    private ConfigContainerInterface $configContainer;

    public function __construct(
        ConfigContainerInterface $configContainer
    ) {
        $this->configContainer = $configContainer;
    }

    /**
     * @throws Exception\LastFmQueryFailedException
     */
    public function getLastFmResults(string $method, string $query): SimpleXMLElement
    {
        $lang    = (string) $this->configContainer->get('lang');
        $resp    = explode('_', $lang);
        $api_key = $this->configContainer->get('lastfm_api_key');
        $url     = static::API_URL . $method . '&api_key=' . $api_key . '&' . $query . '&lang=' . $resp[0];

        return $this->queryLastFm($url);
    }

    /**
     * @throws Exception\LastFmQueryFailedException
     */
    public function queryLastFm(string $url): SimpleXMLElement
    {
        debug_event(self::class, 'search url : ' . $url, 5);

        $request = Requests::get($url, [], Core::requests_options());

        $result = simplexml_load_string((string)$request->body);

        if ($result === false) {
            throw new Exception\LastFmQueryFailedException();
        }

        return $result;
    }
}
