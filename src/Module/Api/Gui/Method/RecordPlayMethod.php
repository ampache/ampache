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

use Ampache\Module\Api\Gui\Authentication\GatekeeperInterface;
use Ampache\Module\Api\Gui\Method\Exception\AccessDeniedException;
use Ampache\Module\Api\Gui\Method\Exception\RequestParamMissingException;
use Ampache\Module\Api\Gui\Method\Exception\ResultEmptyException;
use Ampache\Module\Api\Gui\Output\ApiOutputInterface;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Plugin\Adapter\UserMediaPlaySaverAdapterInterface;
use Ampache\Module\System\LegacyLogger;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\UserRepositoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Log\LoggerInterface;

final class RecordPlayMethod implements MethodInterface
{
    public const ACTION = 'record_play';

    private UserRepositoryInterface $userRepository;

    private UserMediaPlaySaverAdapterInterface $userMediaPlaySaverAdapter;

    private StreamFactoryInterface $streamFactory;

    private LoggerInterface $logger;

    private ModelFactoryInterface $modelFactory;

    public function __construct(
        UserRepositoryInterface $userRepository,
        UserMediaPlaySaverAdapterInterface $userMediaPlaySaverAdapter,
        StreamFactoryInterface $streamFactory,
        LoggerInterface $logger,
        ModelFactoryInterface $modelFactory
    ) {
        $this->userRepository            = $userRepository;
        $this->userMediaPlaySaverAdapter = $userMediaPlaySaverAdapter;
        $this->streamFactory             = $streamFactory;
        $this->logger                    = $logger;
        $this->modelFactory              = $modelFactory;
    }

    /**
     * MINIMUM_API_VERSION=400001
     *
     * Take a song_id and update the object_count and user_activity table with a play
     * This allows other sources to record play history to Ampache.
     * Require 100 (Admin) permission to change other user's play history
     *
     * @param GatekeeperInterface $gatekeeper
     * @param ResponseInterface $response
     * @param ApiOutputInterface $output
     * @param array $input
     * id     = (integer) $object_id
     * user   = (integer|string) $user_id OR $username //optional
     * client = (string) $agent //optional
     * date   = (integer) UNIXTIME() //optional
     *
     * @return ResponseInterface
     * @throws ResultEmptyException
     * @throws AccessDeniedException
     * @throws RequestParamMissingException
     */
    public function handle(
        GatekeeperInterface $gatekeeper,
        ResponseInterface $response,
        ApiOutputInterface $output,
        array $input
    ): ResponseInterface {
        foreach (['id', 'user'] as $key) {
            if (!array_key_exists($key, $input)) {
                throw new RequestParamMissingException(
                    sprintf(T_('Bad Request: %s'), $key)
                );
            }
        }

        $userLookupValue = $input['user'];
        $userLookupId    = (int) $userLookupValue;

        if ($userLookupId === 0) {
            $userLookupId = $this->userRepository->findByUsername((string) $userLookupValue);
        }

        // If you are setting plays for other users make sure we have an admin
        if (
            $userLookupId !== $gatekeeper->getUser()->getId() &&
            !$gatekeeper->mayAccess(AccessLevelEnum::TYPE_INTERFACE, AccessLevelEnum::LEVEL_ADMIN)
        ) {
            throw new AccessDeniedException(
                T_('Require: 100')
            );
        }

        // validate supplied user
        if (in_array($userLookupId, $this->userRepository->getValid()) === false) {
            throw new ResultEmptyException(
                sprintf(T_('Not Found: %d'), $userLookupId)
            );
        }

        // validate client string or fall back to 'api'
        $agent    = $input['client'] ?? 'api';
        $objectId = (int) $input['id'];
        $date     = (int) ($input['date'] ?? time());

        $media = $this->modelFactory->createSong($objectId);
        if ($media->isNew()) {
            throw new ResultEmptyException(
                sprintf(T_('Not Found: %s'), $objectId)
            );
        }

        $playUser     = $this->modelFactory->createUser($userLookupId);
        $playUserName = $playUser->username;

        $this->logger->debug(
            sprintf('record_play: %s for %s using %s %d', $objectId, $playUserName, $agent, $date),
            [LegacyLogger::CONTEXT_TYPE => __CLASS__]
        );

        // internal scrobbling (user_activity and object_count tables)
        if ($media->set_played($userLookupId, $agent, [], $date)) {
            // scrobble plugins
            $this->userMediaPlaySaverAdapter->save($playUser, $media);
        }

        return $response->withBody(
            $this->streamFactory->createStream(
                $output->success(
                    sprintf('successfully recorded play: %s for: %s', $objectId, $playUserName)
                )
            )
        );
    }
}
