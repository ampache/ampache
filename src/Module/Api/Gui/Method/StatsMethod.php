<?php

/*
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
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

namespace Ampache\Module\Api\Gui\Method;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Module\Api\Gui\Authentication\GatekeeperInterface;
use Ampache\Module\Api\Gui\Method\Exception\RequestParamMissingException;
use Ampache\Module\Api\Gui\Method\Lib\ItemToplistMapperInterface;
use Ampache\Module\Api\Gui\Output\ApiOutputInterface;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\UserRepositoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;

final class StatsMethod implements MethodInterface
{
    public const ACTION = 'stats';

    private StreamFactoryInterface $streamFactory;

    private ItemToplistMapperInterface $itemToplistMapper;

    private ModelFactoryInterface $modelFactory;

    private UserRepositoryInterface $userRepository;

    private ConfigContainerInterface $configContainer;

    public function __construct(
        StreamFactoryInterface $streamFactory,
        ItemToplistMapperInterface $itemToplistMapper,
        ModelFactoryInterface $modelFactory,
        UserRepositoryInterface $userRepository,
        ConfigContainerInterface $configContainer
    ) {
        $this->streamFactory     = $streamFactory;
        $this->itemToplistMapper = $itemToplistMapper;
        $this->modelFactory      = $modelFactory;
        $this->userRepository    = $userRepository;
        $this->configContainer   = $configContainer;
    }

    /**
     * MINIMUM_API_VERSION=380001
     * CHANGED_IN_API_VERSION=5.0.0
     *
     * Get some items based on some simple search types and filters. (Random by default)
     * This method HAD partial backwards compatibility with older api versions but it has now been removed
     *
     * @param GatekeeperInterface $gatekeeper
     * @param ResponseInterface $response
     * @param ApiOutputInterface $output
     * @param array $input
     * type     = (string)  'song', 'album', 'artist'
     * filter   = (string)  'newest', 'highest', 'frequent', 'recent', 'forgotten', 'flagged', 'random' (Default: random) //optional
     * user_id  = (integer) //optional
     * username = (string)  //optional
     * offset   = (integer) //optional
     * limit    = (integer) //optional
     *
     * @return ResponseInterface
     * @throws RequestParamMissingException
     */
    public function handle(
        GatekeeperInterface $gatekeeper,
        ResponseInterface $response,
        ApiOutputInterface $output,
        array $input
    ): ResponseInterface {
        $type = $input['type'] ?? null;

        if ($type === null) {
            throw new RequestParamMissingException(
                sprintf(T_('Bad Request: %s'), 'type')
            );
        }

        // confirm the correct data
        if (!in_array($type, ['song', 'album', 'artist'])) {
            throw new RequestParamMissingException(
                T_('Bad Request')
            );
        }

        $offset = (int) ($input['offset'] ?? 0);
        $limit  = (int) ($input['limit'] ?? 0);
        if ($limit < 1) {
            $limit = $this->configContainer->getPopularThreshold(10);
        }

        $username = $input['username'] ?? null;
        $userId   = $input['user_id'] ?? null;

        // override your user if you're looking at others
        if ($username !== null) {
            $user = $this->modelFactory->createUser(
                $this->userRepository->findByUsername($username)
            );
        } elseif ($userId !== null) {
            $user = $this->modelFactory->createUser((int) $userId);
        } else {
            $user = $gatekeeper->getUser();
        }

        $action = $this->itemToplistMapper->map($input['filter']);

        $objectIds = $action(
            $user,
            $type,
            $limit,
            $offset
        );

        $result = '';
        if ($objectIds === []) {
            $result = $output->emptyResult($type);
        } else {
            if ($type === 'song') {
                $result = $output->songs($objectIds, $user->getId());
            }
            if ($type === 'artist') {
                $result = $output->artists($objectIds, [], $user->getId());
            }
            if ($type === 'album') {
                $result = $output->albums($objectIds, [], $user->getId());
            }
        }

        return $response->withBody(
            $this->streamFactory->createStream($result)
        );
    }
}
