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
use Ampache\Module\Api\Method\Exception\AccessDeniedException;
use Ampache\Module\Api\Method\Exception\RequestParamMissingException;
use Ampache\Module\Api\Output\ApiOutputInterface;
use Ampache\Module\System\Preference;
use Ampache\Repository\Model\User;
use Ampache\Repository\UserActivityRepositoryInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Returns a user's timeline
 *
 * The two live api versions only differ in how they name the user: version 6 reports it as
 * `username` and version 8 as `filter`, each accepting the other as an alias. The version classes
 * supply that pair of names; everything else is shared.
 */
abstract class AbstractTimelineMethod implements MethodInterface
{
    public const string ACTION = 'timeline';

    // the alias the version prefers when both names are supplied; overridden per version
    protected const string FILTER_ALIAS = 'username';

    // the name the version reports the user under; overridden per version
    protected const string FILTER_KEY = 'filter';

    private ConfigContainerInterface $configContainer;
    private UserActivityRepositoryInterface $userActivityRepository;

    public function __construct(
        ConfigContainerInterface $configContainer,
        UserActivityRepositoryInterface $userActivityRepository,
    ) {
        $this->configContainer        = $configContainer;
        $this->userActivityRepository = $userActivityRepository;
    }

    /**
     * MINIMUM_API_VERSION=380001
     *
     * This gets a user timeline from their username
     *
     * filter   = (integer|string) filter by user id OR username //optional
     * username = (string)
     * limit    = (integer) //optional
     * since    = (integer) UNIXTIME() //optional
     *
     * @param array{
     *     filter?: int|string,
     *     username?: string,
     *     limit?: int,
     *     since?: int,
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
        if (!$this->configContainer->get(ConfigurationKeyEnum::SOCIABLE)) {
            throw new AccessDeniedException(
                'Enable: sociable'
            );
        }

        $filter = $input[static::FILTER_ALIAS] ?? $input[static::FILTER_KEY] ?? null;
        if ($filter === null) {
            throw new RequestParamMissingException(
                sprintf('Bad Request: %s', static::FILTER_KEY)
            );
        }

        $leadUser = (is_numeric($filter))
            ? User::get_from_id((int) $filter)
            : User::get_from_username((string) $filter);

        // an unknown user, or one who keeps their activity private, simply renders nothing
        if (
            !$leadUser instanceof User
            || (
                $leadUser->getId() !== $user->getId()
                && !Preference::get_by_user($leadUser->getId(), 'allow_personal_info_recent')
            )
        ) {
            return $response;
        }

        $results = $this->userActivityRepository->getActivities(
            $leadUser->getId(),
            (int) ($input['limit'] ?? 0),
            (int) ($input['since'] ?? 0)
        );

        $response->getBody()->write(
            $output->timeline($apiVersion, $results)
        );

        return $response;
    }
}
