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
use Ampache\Module\Api\Method\Exception\AccessFailedException;
use Ampache\Module\Api\Method\Exception\RequestParamMissingException;
use Ampache\Module\Api\Method\Exception\ResultEmptyException;
use Ampache\Module\Api\Method\MethodInterface;
use Ampache\Module\Api\Output\ApiOutputInterface;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Module\Authorization\Check\PrivilegeCheckerInterface;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\Model\User;
use Ampache\Repository\UserRepositoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;

/**
 * Records a play against a song for a user.
 *
 * Version 5 reads the object id from `id` only, so it keeps a method of its own.
 */
final class RecordPlay5Method implements MethodInterface
{
    public const string ACTION = 'record_play';

    public function __construct(
        private ModelFactoryInterface $modelFactory,
        private PrivilegeCheckerInterface $privilegeChecker,
        private StreamFactoryInterface $streamFactory,
        private UserRepositoryInterface $userRepository,
    ) {}

    /**
     * record_play
     * MINIMUM_API_VERSION=400001
     *
     * Take a song_id and update the object_count and user_activity table with a play
     * This allows other sources to record play history to Ampache.
     * Require 100 (Admin) permission to change other user's play history
     *
     * id = (integer) $object_id
     * user = (integer|string) $user_id OR $username //optional
     * client = (string) $agent Default: 'api' //optional
     * date = (integer) UNIXTIME() //optional
     *
     * @param array{
     *     id?: string,
     *     user?: int|string,
     *     client?: string,
     *     date?: int,
     *     api_format: string,
     *     auth: string,
     * } $input
     * @param 5 $apiVersion
     * @throws AccessFailedException|RequestParamMissingException|ResultEmptyException
     */
    public function handle(
        GatekeeperInterface $gatekeeper,
        ResponseInterface $response,
        ApiOutputInterface $output,
        array $input,
        User $user,
        int $apiVersion,
    ): ResponseInterface {
        if (!array_key_exists('id', $input)) {
            throw new RequestParamMissingException(
                sprintf('Bad Request: %s', 'id')
            );
        }

        $play_user = $user;
        if (isset($input['user'])) {
            $play_user = ((int) $input['user'] > 0)
                ? $this->modelFactory->createUser((int) $input['user'])
                : User::get_from_username((string) $input['user']);
        }

        // validate supplied user
        if (
            !$play_user instanceof User
            || !in_array($play_user->id, $this->userRepository->getValid())
        ) {
            throw new ResultEmptyException(
                (string) ($input['user'] ?? $user->id),
                'user'
            );
        }

        // If you are setting plays for other users make sure we have an admin
        if (
            $play_user->id !== $user->id
            && !$this->privilegeChecker->check(
                AccessTypeEnum::INTERFACE,
                AccessLevelEnum::ADMIN,
                $user->id
            )
        ) {
            throw new AccessFailedException(
                sprintf('Require: %s', AccessLevelEnum::ADMIN->value)
            );
        }

        $object_id = (int) $input['id'];
        $date      = (array_key_exists('date', $input)) ? (int) scrub_in((string) $input['date']) : time(); //optional

        // validate client string or fall back to 'api'
        $agent = scrub_in((string) ($input['client'] ?? 'api'));

        $media = $this->modelFactory->createSong($object_id);
        if ($media->isNew()) {
            throw new ResultEmptyException(
                (string) $object_id,
                'id'
            );
        }

        debug_event(self::class, 'record_play: ' . $media->id . ' for ' . $play_user->username . ' using ' . $agent . ' ' . time(), 5);

        // internal scrobbling (user_activity and object_count tables)
        if ($media->set_played($play_user->id, $agent, [], $date)) {
            // scrobble plugins
            User::save_mediaplay($play_user, $media);
        }

        return $response->withBody(
            $this->streamFactory->createStream(
                $output->success(
                    $apiVersion,
                    'successfully recorded play: ' . $media->id . ' for: ' . $play_user->username
                )
            )
        );
    }
}
