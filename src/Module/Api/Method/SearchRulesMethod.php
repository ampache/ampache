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
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\Model\Search;
use Ampache\Repository\Model\User;
use Psr\Http\Message\ResponseInterface;

/**
 * Prints the list of valid search rules for a search type
 */
final class SearchRulesMethod implements MethodInterface
{
    public const string ACTION = 'search_rules';

    public const string REST_ACTION = 'rules';

    private ConfigContainerInterface $configContainer;
    private ModelFactoryInterface $modelFactory;

    public function __construct(
        ConfigContainerInterface $configContainer,
        ModelFactoryInterface $modelFactory,
    ) {
        $this->configContainer = $configContainer;
        $this->modelFactory    = $modelFactory;
    }

    /**
     * MINIMUM_API_VERSION=6.8.0
     *
     * Print a list of valid search rules for your search type
     *
     * filter = (string) the search type
     *
     * @param array{
     *     filter?: string,
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
        if (!array_key_exists('filter', $input)) {
            throw new RequestParamMissingException(
                sprintf('Bad Request: %s', 'filter')
            );
        }

        $type = (string) $input['filter'];

        // confirm the correct data
        if (!in_array(strtolower($type), Search::VALID_TYPES)) {
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

        $search  = $this->modelFactory->createSearch(0, $type, $user);
        $results = $search->get_rule_types();

        $response->getBody()->write(
            $output->objectArray($apiVersion, ['rule' => $results], $results, 'rule')
        );

        return $response;
    }
}
