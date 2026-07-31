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
 * Performs an advanced search given passed rules
 */
final class AdvancedSearchMethod implements MethodInterface
{
    public const string ACTION = 'advanced_search';

    private ConfigContainerInterface $configContainer;

    public function __construct(
        ConfigContainerInterface $configContainer,
    ) {
        $this->configContainer = $configContainer;
    }

    /**
     * The rules and the config gates are shared with the `search` alias, so they live here
     *
     * @param array<string, mixed> $input
     * @throws AccessDeniedException|RequestParamMissingException
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
     * MINIMUM_API_VERSION=380001
     *
     * Perform an advanced search given passed rules. This works in a similar way to the web/UI
     * search pages. You can pass multiple rules as well as joins to create in depth search results
     *
     * Rules must be sent in groups of 3 using an int (starting from 1) to designate which rules are
     * combined. Use operator ('and', 'or') to choose whether to join or separate each rule.
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
     * operator        = (string) 'and', 'or' (whether to match one rule or all)
     * rule_1          = (string)
     * rule_1_operator = (integer) 0|1|2|3|4|5|6
     * rule_1_input    = (mixed) The string, date, integer you are searching for
     * type            = (string) song by default //optional
     * random          = (boolean) 0, 1 (random order of results; default to 0) //optional
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
        $this->checkRules($input);

        $type = (isset($input['type'])) ? (string) $input['type'] : 'song';

        if (!$this->isSearchableType($type)) {
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

        // older versions have no album_disk formatter, so they get an empty result rather than
        // album disks rendered as songs
        if ($apiVersion < 8 && strtolower($type) === 'album_disk') {
            $response->getBody()->write(
                $output->writeEmpty($apiVersion, $type)
            );

            return $response;
        }

        $data           = $input;
        $data['offset'] = 0;
        $data['limit']  = 0;
        $data['type']   = $type;

        $query   = Search::query(Search::prepare($data, $user));
        $results = $query['results'];

        if (empty($results)) {
            $response->getBody()->write(
                $output->writeEmpty($apiVersion, $type)
            );

            return $response;
        }

        $response->getBody()->write(
            $output->searchResult(
                $apiVersion,
                $type,
                $results,
                $user,
                $input['auth'],
                (int) ($input['offset'] ?? 0),
                (int) ($input['limit'] ?? 0),
                $query['count']
            )
        );

        return $response;
    }

    /**
     * Whether the type can be searched at all; throws when the type exists but is switched off
     *
     * Shared with the `search` alias.
     *
     * @throws AccessDeniedException
     */
    public function isSearchableType(string $type): bool
    {
        if (!in_array(strtolower($type), Search::VALID_TYPES)) {
            return false;
        }

        if (
            $type === 'video'
            && !$this->configContainer->get(ConfigurationKeyEnum::ALLOW_VIDEO)
        ) {
            throw new AccessDeniedException(
                'Enable: video'
            );
        }

        if (
            $type === 'label'
            && !$this->configContainer->get(ConfigurationKeyEnum::LABEL)
        ) {
            throw new AccessDeniedException(
                'Enable: label'
            );
        }

        return true;
    }
}
