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

use Ampache\Module\Api\Authentication\GatekeeperInterface;
use Ampache\Module\Api\Method\MethodInterface;
use Ampache\Module\Api\Output\ApiOutputInterface;
use Ampache\Module\Database\Query\Search;
use Ampache\Repository\Model\User;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;

final class AdvancedSearch4Method implements MethodInterface
{
    public const string ACTION = 'advanced_search';

    public function __construct(
        private StreamFactoryInterface $streamFactory,
    ) {}

    /**
     * advanced_search
     * MINIMUM_API_VERSION=380001
     *
     * Perform an advanced search given passed rules. This works in a similar way to the web/UI search pages.
     * You can pass multiple rules as well as joins to create in depth search results
     *
     * Rules must be sent in groups of 3 using an int (starting from 1) to designate which rules are combined.
     * Use operator ('and'|'or') to choose whether to join or separate each rule when searching.
     *
     * Rule arrays must contain the following:
     *   * rule name (e.g. rule_1, rule_2)
     *   * rule operator (e.g. rule_1_operator, rule_2_operator)
     *   * rule input (e.g. rule_1_input, rule_2_input)
     *
     * Refer to the wiki for further information on rule_* types and data
     * https://ampache.org/api/api-xml-methods
     *
     * operator = (string) 'and'|'or' (whether to match one rule or all)
     * rule_1 = (string)
     * rule_1_operator = (integer) 0|1|2|3|4|5|6
     * rule_1_input = (mixed) The string, date, integer you are searching for
     * type = (string) 'song', 'album', 'song_artist', 'album_artist', 'artist', 'playlist', 'label', 'user', 'video' (song by default)
     * offset = (integer)
     * limit = (integer)
     *
     * @param array<string, mixed> $input
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
        $type = $input['type'] ?? 'song';

        $data           = $input;
        $data['offset'] = 0;
        $data['limit']  = 0;
        $data['type']   = $type;

        $query   = Search::query(Search::prepare($data, $user));
        $results = $query['results'];

        $output->setOffset($apiVersion, $input['offset'] ?? 0);
        $output->setLimit($apiVersion, $input['limit'] ?? 0);

        // json has always treated the two artist roles as artists here and xml has not; keep both as they were
        $asArtist = ($input['api_format'] === 'json')
            ? in_array($type, ['song_artist', 'album_artist', 'artist'], true)
            : $type === 'artist';

        $body = match (true) {
            $asArtist => $output->artists($apiVersion, $results, [], $user, $input['auth']),
            $type === 'album' => $output->albums($apiVersion, $results, [], $user, $input['auth']),
            $type === 'playlist' => $output->playlists($apiVersion, $results, $user, $input['auth']),
            $type === 'user' => $output->users($apiVersion, $results),
            $type === 'video' => $output->videos($apiVersion, $results, $user, $input['auth']),
            default => $output->songs($apiVersion, $results, $user, $input['auth']),
        };

        return $response->withBody(
            $this->streamFactory->createStream($body)
        );
    }
}
