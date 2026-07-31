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

use Ampache\Module\Api\Authentication\GatekeeperInterface;
use Ampache\Module\Api\Method\Exception\AccessFailedException;
use Ampache\Module\Api\Method\Exception\RequestParamMissingException;
use Ampache\Module\Api\Method\Exception\ResultEmptyException;
use Ampache\Module\Api\Output\ApiOutputInterface;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Module\Authorization\Check\PrivilegeCheckerInterface;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\Model\User;
use Ampache\Repository\UserRepositoryInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Records a play against a song for a user
 *
 * The two live api versions only differ in how they name the object id: version 6 reports it as
 * `id` and version 8 as `filter`, each accepting the other as an alias. The version classes supply
 * that pair of names; everything else is shared.
 */
abstract class AbstractRecordPlayMethod implements MethodInterface
{
    public const string ACTION = 'record_play';

    // the alias the version prefers when both names are supplied; overridden per version
    protected const string FILTER_ALIAS = 'id';

    // the name the version reports the object id under; overridden per version
    protected const string FILTER_KEY = 'filter';

    private ModelFactoryInterface $modelFactory;
    private PrivilegeCheckerInterface $privilegeChecker;
    private UserRepositoryInterface $userRepository;

    public function __construct(
        ModelFactoryInterface $modelFactory,
        PrivilegeCheckerInterface $privilegeChecker,
        UserRepositoryInterface $userRepository,
    ) {
        $this->modelFactory     = $modelFactory;
        $this->privilegeChecker = $privilegeChecker;
        $this->userRepository   = $userRepository;
    }

    /**
     * MINIMUM_API_VERSION=400001
     *
     * Take a song_id and update the object_count and user_activity table with a play
     * This allows other sources to record play history to Ampache.
     * Require 100 (Admin) permission to change other user's play history
     *
     * id     = (string) $object_id
     * user   = (integer|string) $user_id OR $username //optional
     * client = (string) $agent Default: 'api' //optional
     * date   = (integer) UNIXTIME() //optional
     *
     * @param array{
     *     filter?: string,
     *     id?: string,
     *     user?: int|string,
     *     client?: string,
     *     date?: int,
     *     api_format: string,
     *     auth: string,
     * } $input
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
        $filter = $input[static::FILTER_ALIAS] ?? $input[static::FILTER_KEY] ?? null;
        if ($filter === null) {
            throw new RequestParamMissingException(
                sprintf('Bad Request: %s', static::FILTER_KEY)
            );
        }

        $playUser = $user;
        if (isset($input['user'])) {
            $playUser = ((int) $input['user'] > 0)
                ? $this->modelFactory->createUser((int) $input['user'])
                : User::get_from_username((string) $input['user']);
        }

        // validate supplied user
        if (
            !$playUser instanceof User
            || !in_array($playUser->getId(), $this->userRepository->getValid())
        ) {
            throw new ResultEmptyException(
                (string) ($input['user'] ?? $user->getId()),
                'user'
            );
        }

        // If you are setting plays for other users make sure we have an admin
        if (
            $playUser->getId() !== $user->getId()
            && !$this->privilegeChecker->check(
                AccessTypeEnum::INTERFACE,
                AccessLevelEnum::ADMIN,
                $user->getId()
            )
        ) {
            throw new AccessFailedException(
                sprintf('Require: %s', AccessLevelEnum::ADMIN->value)
            );
        }

        $objectId = (int) $filter;
        $date     = (array_key_exists('date', $input)) ? (int) scrub_in((string) $input['date']) : time();

        // validate client string or fall back to 'api'
        $agent = scrub_in((string) ($input['client'] ?? 'api'));

        $media = $this->modelFactory->createSong($objectId);
        if ($media->isNew()) {
            throw new ResultEmptyException(
                (string) $objectId,
                'id'
            );
        }

        debug_event(static::class, 'record_play: ' . $media->getId() . ' for ' . $playUser->username . ' using ' . $agent . ' ' . time(), 5);

        // internal scrobbling (user_activity and object_count tables)
        if ($media->set_played($playUser->getId(), $agent, [], $date)) {
            // scrobble plugins
            User::save_mediaplay($playUser, $media);
        }

        $response->getBody()->write(
            $output->success(
                $apiVersion,
                'successfully recorded play: ' . $media->getId() . ' for: ' . $playUser->username
            )
        );

        return $response;
    }
}
