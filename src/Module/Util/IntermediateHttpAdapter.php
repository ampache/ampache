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

namespace Ampache\Module\Util;

use Exception;
use MusicBrainz\HttpAdapters\AbstractHttpAdapter;
use Psr\Http\Message\RequestFactoryInterface;

/**
 * Intermedia http adapter for MusicBrainz
 */
final class IntermediateHttpAdapter extends AbstractHttpAdapter
{
    private UtilityFactoryInterface $utilityFactory;

    private RequestFactoryInterface $requestFactory;

    public function __construct(
        UtilityFactoryInterface $utilityFactory,
        RequestFactoryInterface $requestFactory
    ) {
        $this->utilityFactory = $utilityFactory;
        $this->requestFactory = $requestFactory;
    }

    /**
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws Exception
     */
    public function call($path, array $params = [], array $options = [], $isAuthRequired = false, $returnArray = false)
    {
        if ($options['user-agent'] == '') {
            throw new Exception('You must set a valid User Agent before accessing the MusicBrainz API');
        }

        $client         = $this->utilityFactory->createHttpClient();
        $requestOptions = [];

        $url = sprintf(
            '%s/%s',
            $this->endpoint,
            $path
        );
        foreach ($params as $name => $value) {
            $url .= ($i++ == 0) ? '?' : '&';
            $url .= urlencode($name) . '=' . urlencode($value);
        }

        $request = $this->requestFactory
            ->createRequest(
                'GET',
                $url
            )
            ->withHeader('Accept', 'application/json')
            ->withHeader('User-Agent', $options['user-agent']);

        if ($isAuthRequired) {
            if ($options['user'] != null && $options['password'] != null) {
                $requestOptions['auth'] = [$options['user'], $options['password'], CURLAUTH_DIGEST];
            } else {
                throw new Exception('Authentication is required');
            }
        }

        // musicbrainz throttle
        sleep(1);

        try {
            $result = $client->send(
                $request,
                $requestOptions
            );
        } catch (\Exception $e) {
            throw new Exception('Musicbrainz query failed');
        }

        return json_decode((string) $result->getBody());
    }
}
