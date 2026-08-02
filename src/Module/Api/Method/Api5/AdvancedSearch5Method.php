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

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
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
 * Performs an advanced search given passed rules and returns the matching objects.
 *
 * Version 5 does not send a result count and renders the `label`/`genre` results without the
 * later per-user data, so it keeps a method of its own.
 */
final class AdvancedSearch5Method implements MethodInterface
{
    public const string ACTION = 'advanced_search';

    public function __construct(
        private ConfigContainerInterface $configContainer,
        private StreamFactoryInterface $streamFactory,
    ) {}

    /**
     * The rule check is shared with the `search` alias, so it lives here
     *
     * @param array<string, mixed> $input
     *
     * @throws RequestParamMissingException
     */
    public function checkRules(array $input): void
    {
        foreach (['rule_1', 'rule_1_operator', 'rule_1_input'] as $parameter) {
            if (!array_key_exists($parameter, $input)) {
                throw new RequestParamMissingException(
                    sprintf('Bad Request: %s', $parameter)
                );
            }
        }
    }

    /**
     * The video gate is shared with the `search` alias, so it lives here
     *
     * @throws AccessDeniedException
     */
    public function checkType(string $type): void
    {
        if (
            $type == 'video'
            && !$this->configContainer->get(ConfigurationKeyEnum::ALLOW_VIDEO)
        ) {
            throw new AccessDeniedException(
                'Enable: video'
            );
        }
    }

    /**
     * advanced_search
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
        $this->checkRules($input);

        $type = (isset($input['type'])) ? (string) $input['type'] : 'song';

        $this->checkType($type);

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

        if (strtolower($type) === 'album_disk') {
            return $response->withBody(
                $this->streamFactory->createStream(
                    $output->writeEmpty($apiVersion, $type)
                )
            );
        }

        $data           = $input;
        $data['offset'] = 0;
        $data['limit']  = 0;
        $data['type']   = $type;

        $query   = Search::query(Search::prepare($data, $user));
        $results = $query['results'];

        if ($results === []) {
            return $response->withBody(
                $this->streamFactory->createStream(
                    $output->writeEmpty($apiVersion, $type)
                )
            );
        }

        $output->setOffset($apiVersion, $input['offset'] ?? 0);
        $output->setLimit($apiVersion, $input['limit'] ?? 0);

        return $response->withBody(
            $this->streamFactory->createStream(
                $this->renderResult($output, $apiVersion, $type, $results, $user, $input)
            )
        );
    }

    /**
     * Render the result for the searched type
     *
     * `song_artist` and `album_artist` are artist searches, so both formats render them as artists.
     *
     * @param 5 $apiVersion
     * @param array<int|string> $results
     * @param array{auth: string, ...} $input
     */
    private function renderResult(
        ApiOutputInterface $output,
        int $apiVersion,
        string $type,
        array $results,
        User $user,
        array $input,
    ): string {
        return match ($type) {
            'album' => $output->albums($apiVersion, $results, [], $user, $input['auth']),
            'artist', 'song_artist', 'album_artist' => $output->artists($apiVersion, $results, [], $user, $input['auth']),
            'label' => $output->labels($apiVersion, $results, $user),
            'playlist' => $output->playlists($apiVersion, $results, $user, $input['auth']),
            'podcast' => $output->podcasts($apiVersion, $results, $user, $input['auth']),
            'podcast_episode' => $output->podcastEpisodes($apiVersion, $results, $user, $input['auth']),
            'genre', 'tag' => $output->genres($apiVersion, $results, $user),
            'user' => $output->users($apiVersion, $results),
            'video' => $output->videos($apiVersion, $results, $user, $input['auth']),
            default => $output->songs($apiVersion, $results, $user, $input['auth']),
        };
    }
}
