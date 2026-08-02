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
use Ampache\Module\Api\Method\Exception\RequestParamMissingException;
use Ampache\Module\Api\Output\ApiOutputInterface;
use Ampache\Module\Database\Query\Search;
use Ampache\Repository\Model\User;
use Psr\Http\Message\ResponseInterface;

/**
 * Searches the songs and returns songs
 *
 * The two live api versions differ in what satisfies the search term: version 8 accepts
 * `rule_1_input` on its own, version 6 insists on `filter` being present even when `rule_1_input`
 * supplies the value. The version classes supply that flag; everything else is shared.
 */
abstract class AbstractSearchSongsMethod implements MethodInterface
{
    public const string ACTION = 'search_songs';

    // whether `rule_1_input` alone satisfies the search term; overridden per version
    protected const bool ALIAS_SATISFIES_FILTER = true;

    /**
     * MINIMUM_API_VERSION=380001
     *
     * This searches the songs and returns... songs
     *
     * filter = (string) The string, date, integer you are searching for
     * offset = (integer) //optional
     * limit  = (integer) //optional
     *
     * @param array{
     *     rule_1_input?: string,
     *     filter?: string,
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
        if (!static::ALIAS_SATISFIES_FILTER && !array_key_exists('filter', $input)) {
            throw new RequestParamMissingException(
                sprintf('Bad Request: %s', 'filter')
            );
        }

        $filter = $input['rule_1_input'] ?? $input['filter'] ?? null;
        if ($filter === null) {
            throw new RequestParamMissingException(
                sprintf('Bad Request: %s', 'filter')
            );
        }

        $query = Search::query(
            Search::prepare(
                [
                    'type' => 'song',
                    'rule_1' => 'anywhere',
                    'rule_1_input' => $filter,
                    'rule_1_operator' => 0,
                ],
                $user
            )
        );

        $results = $query['results'];
        if (empty($results)) {
            $response->getBody()->write(
                $output->writeEmpty($apiVersion, 'song')
            );

            return $response;
        }

        $output->setOffset($apiVersion, (int) ($input['offset'] ?? 0));
        $output->setLimit($apiVersion, (int) ($input['limit'] ?? 0));
        $output->setCount($apiVersion, $query['count']);

        $response->getBody()->write(
            $output->songs($apiVersion, $results, $user, $input['auth'])
        );

        return $response;
    }
}
