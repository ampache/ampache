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

namespace Ampache\Module\Api\Method\Api4;

use Ampache\Module\Api\Api4;
use Ampache\Module\Api\Authentication\GatekeeperInterface;
use Ampache\Module\Api\Method\MethodInterface;
use Ampache\Module\Api\Output\ApiOutputInterface;
use Ampache\Module\Util\Recommendation;
use Ampache\Repository\Model\User;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;

final class GetSimilar4Method implements MethodInterface
{
    public const string ACTION = 'get_similar';

    public function __construct(
        private StreamFactoryInterface $streamFactory,
    ) {}

    /**
     * get_similar
     * MINIMUM_API_VERSION=420000
     *
     * Return similar artist id's or similar song ids compared to the input filter
     *
     * type = (string) 'song'|'artist'
     * filter = (integer) artist id or song id
     * offset = (integer) //optional
     * limit = (integer) //optional
     *
     * @param array{
     *     filter: string,
     *     type: string,
     *     offset?: int,
     *     limit?: int,
     *     api_format: string,
     *     auth: string,
     * } $input
     * @param 4 $apiVersion
     */
    public function handle(
        GatekeeperInterface $gatekeeper,
        ResponseInterface $response,
        ApiOutputInterface $output,
        array $input,
        User $user,
        int $apiVersion,
    ): ResponseInterface {
        if (!Api4::check_parameter($input, ['type', 'filter'], self::ACTION)) {
            return $response;
        }
        $type   = (string) $input['type'];
        $filter = (int) $input['filter'];
        // confirm the correct data
        if (!in_array(strtolower($type), ['song', 'artist'])) {
            Api4::message('error', 'Incorrect object type' . ' ' . $type, '401', $input['api_format']);

            return $response;
        }

        $results = [];
        $similar = [];
        switch ($type) {
            case 'artist':
                $similar = Recommendation::get_artists_like($filter);
                break;
            case 'song':
                $similar = Recommendation::get_songs_like($filter);
        }
        foreach ($similar as $child) {
            $results[] = (int) $child['id'];
        }

        ob_end_clean();
        $output->setOffset($apiVersion, $input['offset'] ?? 0);
        $output->setLimit($apiVersion, $input['limit'] ?? 0);

        return $response->withBody(
            $this->streamFactory->createStream(
                $output->indexes($apiVersion, $results, $type, $user, $input['auth'])
            )
        );
    }
}
