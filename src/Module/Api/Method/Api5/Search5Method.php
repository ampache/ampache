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

namespace Ampache\Module\Api\Method\Api5;

use Ampache\Module\Api\Authentication\GatekeeperInterface;
use Ampache\Module\Api\Exception\ErrorCodeEnum;
use Ampache\Module\Api\Method\Exception\AccessDeniedException;
use Ampache\Module\Api\Method\Exception\RequestParamMissingException;
use Ampache\Module\Api\Method\MethodInterface;
use Ampache\Module\Api\Output\ApiOutputInterface;
use Ampache\Module\Database\Query\Search;
use Ampache\Repository\Model\User;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;

/**
 * Alias of advanced_search; returns the objects matching the passed rules.
 *
 * Version 5 checks the rules and the type itself (so the failure reports this action) before it
 * hands over to the version 5 advanced_search, so it keeps a method of its own.
 */
final class Search5Method implements MethodInterface
{
    public const string ACTION = 'search';

    public function __construct(
        private AdvancedSearch5Method $advancedSearchMethod,
        private StreamFactoryInterface $streamFactory,
    ) {}

    /**
     * search
     * MINIMUM_API_VERSION=380001
     *
     * Perform an advanced search given passed rules. This works in a similar way to the web/UI search pages.
     * You can pass multiple rules as well as joins to create in depth search results
     *
     * Rules must be sent in groups of 3 using an int (starting from 1) to designate which rules are combined.
     * Use operator ('and', 'or') to choose whether to join or separate each rule when searching.
     *
     * Rule arrays must contain the following:
     *   * rule name (e.g. rule_1, rule_2)
     *   * rule operator (e.g. rule_1_operator, rule_2_operator)
     *   * rule input (e.g. rule_1_input, rule_2_input)
     *
     * Refer to the wiki for further information on rule_* types and data
     * https://ampache.org/api/api-xml-methods
     * https://ampache.org/api/api-json-methods
     *
     * operator = (string) 'and', 'or' (whether to match one rule or all)
     * rule_1 = (string)
     * rule_1_operator = (integer) 0|1|2|3|4|5|6
     * rule_1_input = (mixed) The string, date, integer you are searching for
     * type = (string) 'song', 'album', 'song_artist', 'album_artist', 'artist', 'label', 'playlist', 'podcast', 'podcast_episode', 'genre', 'user', 'video' (song by default) //optional
     * random = (boolean)  0, 1 (random order of results; default to 0) //optional
     * offset = (integer) //optional
     * limit = (integer) //optional
     *
     * @param array{
     *     operator?: string,
     *     rule_1?: string,
     *     rule_1_operator?: int,
     *     rule_1_input?: mixed,
     *     type?: string,
     *     random?: int,
     *     offset?: int,
     *     limit?: int,
     *     api_format: string,
     *     auth: string,
     * } $input
     * @param 5 $apiVersion
     *
     * @throws AccessDeniedException
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
        $this->advancedSearchMethod->checkRules($input);

        $type = (isset($input['type'])) ? (string) $input['type'] : 'song';

        $this->advancedSearchMethod->checkType($type);

        // confirm the correct data
        if (!in_array(strtolower($type), Search::VALID_TYPES)) {
            return $response->withBody(
                $this->streamFactory->createStream(
                    $output->error(
                        $apiVersion,
                        ErrorCodeEnum::BAD_REQUEST,
                        sprintf('Bad Request: %s', $type),
                        self::ACTION,
                        'type'
                    )
                )
            );
        }

        return $this->advancedSearchMethod->handle(
            $gatekeeper,
            $response,
            $output,
            $input,
            $user,
            $apiVersion
        );
    }
}
