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

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Module\Api\Authentication\GatekeeperInterface;
use Ampache\Module\Api\Exception\ErrorCodeEnum;
use Ampache\Module\Api\Method\Exception\AccessDeniedException;
use Ampache\Module\Api\Method\Exception\RequestParamMissingException;
use Ampache\Module\Api\Output\ApiOutputInterface;
use Ampache\Repository\Model\Search;
use Ampache\Repository\Model\User;
use Psr\Http\Message\ResponseInterface;

/**
 * Performs a search given passed rules and returns the matching objects grouped by type
 */
final class SearchGroupMethod implements MethodInterface
{
    public const string ACTION = 'search_group';

    public const string REST_ACTION = 'groups';

    /** @var array<string, string[]> the object types each group searches */
    private const array GROUP_TYPES = [
        'all' => [
            'album_artist',
            'album',
            'artist',
            'genre',
            'label',
            'playlist',
            'podcast_episode',
            'podcast',
            'song_artist',
            'song',
            'user',
        ],
        'music' => ['album', 'artist', 'song'],
        'song_artist' => ['album', 'song_artist', 'song'],
        'album_artist' => ['album_artist', 'album', 'song'],
        'podcast' => ['podcast', 'podcast_episode'],
        'video' => ['video'],
    ];

    /** @var string[] */
    private const array SEARCH_GROUPS = [
        'album_artist',
        'all',
        'music',
        'podcast',
        'song_artist',
        'video',
    ];

    private ConfigContainerInterface $configContainer;

    public function __construct(
        ConfigContainerInterface $configContainer,
    ) {
        $this->configContainer = $configContainer;
    }

    /**
     * MINIMUM_API_VERSION=6.3.0
     *
     * Perform a search given passed rules and return matching objects in a group.
     * If the rules do not exist for the object type, or would return the entire table, they will
     * not return objects.
     *
     * operator        = (string) 'and', 'or' (whether to match one rule or all)
     * rule_1          = (string)
     * rule_1_operator = (integer) 0|1|2|3|4|5|6
     * rule_1_input    = (mixed) The string, date, integer you are searching for
     * type            = (string) 'all', 'music', 'song_artist', 'album_artist', 'podcast', 'video' //optional
     * random          = (boolean) 0, 1 //optional
     * offset          = (integer) //optional
     * limit           = (integer) //optional
     *
     * @param array{
     *     operator?: string,
     *     rule_1?: string,
     *     rule_1_operator?: int,
     *     rule_1_input?: mixed,
     *     type?: string,
     *     offset?: int,
     *     limit?: int,
     *     api_format: string,
     *     auth: string,
     * } $input
     * @throws AccessDeniedException|RequestParamMissingException
     */
    public function handle(
        GatekeeperInterface $gatekeeper,
        ResponseInterface $response,
        ApiOutputInterface $output,
        array $input,
        User $user,
        int $apiVersion,
    ): ResponseInterface {
        foreach (['rule_1', 'rule_1_operator', 'rule_1_input'] as $parameter) {
            if (!array_key_exists($parameter, $input)) {
                throw new RequestParamMissingException(
                    sprintf('Bad Request: %s', $parameter)
                );
            }
        }

        $type = $input['type'] ?? 'all';

        if (
            $type === 'video'
            && !$this->configContainer->get(ConfigurationKeyEnum::ALLOW_VIDEO)
        ) {
            throw new AccessDeniedException(
                'Enable: video'
            );
        }

        // confirm the correct data
        if (!in_array(strtolower((string) $type), self::SEARCH_GROUPS)) {
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

        $offset = (int) ($input['offset'] ?? 0);
        $limit  = (int) ($input['limit'] ?? 0);

        // each type is searched unpaged; the output applies the paging
        $data           = $input;
        $data['offset'] = 0;
        $data['limit']  = 0;

        $results = [];
        $counts  = [];
        foreach (self::GROUP_TYPES[$type] as $searchType) {
            $data['type'] = $searchType;

            $query                = Search::query(Search::prepare($data, $user));
            $results[$searchType] = $query['results'];
            $counts[$searchType]  = $query['count'];
        }

        $response->getBody()->write(
            $output->searchGroup($apiVersion, $results, $counts, $user, $input['auth'], $offset, $limit)
        );

        return $response;
    }
}
