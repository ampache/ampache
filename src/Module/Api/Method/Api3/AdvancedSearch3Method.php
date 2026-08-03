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

namespace Ampache\Module\Api\Method\Api3;

use Ampache\Module\Api\Authentication\GatekeeperInterface;
use Ampache\Module\Api\Method\MethodInterface;
use Ampache\Module\Api\Output\ApiOutputInterface;
use Ampache\Module\Database\Query\Search;
use Ampache\Repository\Model\User;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;

final class AdvancedSearch3Method implements MethodInterface
{
    public const string ACTION = 'advanced_search';

    public function __construct(
        private StreamFactoryInterface $streamFactory,
    ) {}

    /**
     * advanced_search
     * Perform an advanced search given passed rules
     *
     * @param array<string, mixed> $input
     * @param 3 $apiVersion
     */
    public function handle(
        GatekeeperInterface $gatekeeper,
        ResponseInterface $response,
        ApiOutputInterface $output,
        array $input,
        User $user,
        int $apiVersion,
    ): ResponseInterface {
        ob_end_clean();

        $output->setOffset($apiVersion, $input['offset'] ?? 0);
        $output->setLimit($apiVersion, $input['limit'] ?? 0);

        $data           = $input;
        $data['offset'] = 0;
        $data['limit']  = 0;
        $data['type']   = (isset($data['type'])) ? (string) $data['type'] : 'song';
        $search_sql     = Search::prepare($data, $user);
        $query          = Search::query($search_sql);
        $results        = $query['results'];

        $type = 'song';
        if (isset($input['type'])) {
            $type = $input['type'];
        }

        switch ($type) {
            case 'artist':
                return $response->withBody(
                    $this->streamFactory->createStream(
                        $output->artists($apiVersion, $results, [], $user, $input['auth'])
                    )
                );
            case 'album':
                return $response->withBody(
                    $this->streamFactory->createStream(
                        $output->albums($apiVersion, $results, [], $user, $input['auth'])
                    )
                );
            default:
                return $response->withBody(
                    $this->streamFactory->createStream(
                        $output->songs($apiVersion, $results, $user, $input['auth'])
                    )
                );
        }
    }
}
