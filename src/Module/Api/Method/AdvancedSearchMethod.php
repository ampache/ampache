<?php

/*
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 *  LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright 2001 - 2020 Ampache.org
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

declare(strict_types=1);

namespace Ampache\Module\Api\Method;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Module\Api\Authentication\GatekeeperInterface;
use Ampache\Module\Api\Method\Exception\FunctionDisabledException;
use Ampache\Module\Api\Method\Exception\RequestParamMissingException;
use Ampache\Module\Api\Output\ApiOutputInterface;
use Ampache\Repository\Model\ModelFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;

final class AdvancedSearchMethod implements MethodInterface
{
    public const ACTION = 'advanced_search';

    private ModelFactoryInterface $modelFactory;

    private StreamFactoryInterface $streamFactory;

    private ConfigContainerInterface $configContainer;

    public function __construct(
        ModelFactoryInterface $modelFactory,
        StreamFactoryInterface $streamFactory,
        ConfigContainerInterface $configContainer
    ) {
        $this->modelFactory    = $modelFactory;
        $this->streamFactory   = $streamFactory;
        $this->configContainer = $configContainer;
    }

    /**
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
     * http://ampache.org/api/api-xml-methods
     * http://ampache.org/api/api-json-methods
     *
     * @param GatekeeperInterface $gatekeeper
     * @param ResponseInterface $response
     * @param ApiOutputInterface $output
     * @param array $input
     * operator        = (string) 'and', 'or' (whether to match one rule or all)
     * rule_1          = (string)
     * rule_1_operator = (integer) 0,1|2|3|4|5|6
     * rule_1_input    = (mixed) The string, date, integer you are searching for
     * type            = (string) 'song', 'album', 'artist', 'playlist', 'label', 'user', 'video' (song by default) //optional
     * random          = (boolean)  0, 1 (random order of results; default to 0) //optional
     * offset          = (integer) //optional
     * limit           = (integer) //optional
     *
     * @return ResponseInterface
     * @throws RequestParamMissingException
     * @throws FunctionDisabledException
     */
    public function handle(
        GatekeeperInterface $gatekeeper,
        ResponseInterface $response,
        ApiOutputInterface $output,
        array $input
    ): ResponseInterface {
        foreach (['rule_1', 'rule_1_operator', 'rule_1_input'] as $key) {
            if (!array_key_exists($key, $input)) {
                throw new RequestParamMissingException(
                    sprintf(T_('Bad Request: %s'), $key)
                );
            }
        }

        $type = $input['type'] ?? 'song';

        if ($type === 'video' && $this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::ALLOW_VIDEO) === false) {
            throw new FunctionDisabledException(
                T_('Enable: video')
            );
        }
        // confirm the correct data
        if (!in_array($type, ['song', 'album', 'artist', 'playlist', 'label', 'user', 'video'])) {
            throw new RequestParamMissingException(
                sprintf(T_('Bad Request: %s'), $type)
            );
        }

        $user = $gatekeeper->getUser();

        $results = $this->modelFactory->createSearch()->runSearch($input, $user);
        if ($results === []) {
            return $response->withBody(
                $this->streamFactory->createStream(
                    $output->emptyResult($type)
                )
            );
        }

        $userId = $user->getId();
        $limit  = (int) ($input['limit'] ?? 0);
        $offset = (int) ($input['offset'] ?? 0);

        switch ($type) {
            case 'artist':
                $result = $output->artists(
                    $results,
                    [],
                    $userId,
                    true,
                    true,
                    $limit,
                    $offset
                );
                break;
            case 'album':
                $result = $output->albums(
                    $results,
                    [],
                    $userId,
                    true,
                    $limit,
                    $offset
                );
                break;
            case 'playlist':
                $result = $output->playlists(
                    $results,
                    $userId,
                    false,
                    true,
                    $limit,
                    $offset
                );
                break;
            case 'label':
                $result = $output->labels(
                    $results,
                    true,
                    $limit,
                    $offset
                );
                break;
            case 'user':
                $result = $output->users(
                    $results,
                );
                break;
            case 'video':
                $result = $output->videos(
                    $results,
                    $userId,
                    true,
                    $limit,
                    $offset
                );
                break;
            default:
                $result = $output->songs(
                    $results,
                    $userId,
                    true,
                    true,
                    true,
                    $limit,
                    $offset
                );
                break;
        }

        return $response->withBody(
            $this->streamFactory->createStream($result)
        );
    }
}
