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

namespace Ampache\Module\Api\Method;

use Ampache\Module\Api\Authentication\GatekeeperInterface;
use Ampache\Module\Api\Exception\ErrorCodeEnum;
use Ampache\Module\Api\Method\Exception\RequestParamMissingException;
use Ampache\Module\Api\Output\ApiOutputInterface;
use Ampache\Module\Util\Recommendation;
use Ampache\Repository\Model\User;
use Psr\Http\Message\ResponseInterface;

/**
 * Returns similar artist or song ids compared to the input filter
 */
final class GetSimilarMethod implements MethodInterface
{
    public const string ACTION = 'get_similar';

    /**
     * MINIMUM_API_VERSION=420000
     *
     * Return similar artist id's or similar song ids compared to the input filter
     *
     * filter = (string) artist id or song id
     * type   = (string) 'song', 'artist'
     * offset = (integer) //optional
     * limit  = (integer) //optional
     *
     * @param array{
     *     filter?: string,
     *     type?: string,
     *     offset?: int,
     *     limit?: int,
     *     api_format: string,
     *     auth: string,
     * } $input
     * @throws RequestParamMissingException
     */
    public function handle(
        GatekeeperInterface $gatekeeper,
        ResponseInterface $response,
        ApiOutputInterface $output,
        array $input,
        User $user,
        int $apiVersion,
    ): ResponseInterface {
        foreach (['type', 'filter'] as $parameter) {
            if (!array_key_exists($parameter, $input)) {
                throw new RequestParamMissingException(
                    sprintf('Bad Request: %s', $parameter)
                );
            }
        }

        $type     = (string) $input['type'];
        $objectId = (int) $input['filter'];

        // confirm the correct data
        if (!in_array(strtolower($type), ['song', 'artist'])) {
            $response->getBody()->write(
                $output->error(
                    $apiVersion,
                    ErrorCodeEnum::BAD_REQUEST,
                    sprintf('Bad Request: %s', $type),
                    self::ACTION,
                    'type'
                )
            );

            return $response;
        }

        // the guard above accepts any casing, but only the canonical names resolve to a lookup
        $similar = match ($type) {
            'artist' => Recommendation::get_artists_like($objectId),
            'song' => Recommendation::get_songs_like($objectId),
            default => [],
        };

        $results = [];
        foreach ($similar as $child) {
            $results[] = (int) $child['id'];
        }

        if ($results === []) {
            $response->getBody()->write(
                $output->writeEmpty($apiVersion, $type)
            );

            return $response;
        }

        $output->setOffset($apiVersion, (int) ($input['offset'] ?? 0));
        $output->setLimit($apiVersion, $input['limit'] ?? 0);

        // a non-empty result set is only reachable for the two canonical names
        $response->getBody()->write(
            ($type === 'artist')
                ? $output->artists($apiVersion, $results, [], $user, $input['auth'])
                : $output->songs($apiVersion, $results, $user, $input['auth'])
        );

        return $response;
    }
}
