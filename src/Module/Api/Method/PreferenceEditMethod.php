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
use Ampache\Module\Api\Exception\ErrorCodeEnum;
use Ampache\Module\Api\Method\Exception\AccessFailedException;
use Ampache\Module\Api\Method\Exception\RequestParamMissingException;
use Ampache\Module\Api\Method\Exception\ResultEmptyException;
use Ampache\Module\Api\Output\ApiOutputInterface;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Module\Authorization\Check\PrivilegeCheckerInterface;
use Ampache\Module\System\Preference;
use Ampache\Repository\Model\User;
use Psr\Http\Message\ResponseInterface;

/**
 * Edits a preference value, optionally applying it to all users
 */
final class PreferenceEditMethod implements MethodInterface
{
    public const string ACTION = 'preference_edit';

    public const string REST_ACTION = 'preferences_edit';

    private PrivilegeCheckerInterface $privilegeChecker;

    public function __construct(
        PrivilegeCheckerInterface $privilegeChecker,
    ) {
        $this->privilegeChecker = $privilegeChecker;
    }

    /**
     * MINIMUM_API_VERSION=5.0.0
     *
     * Edit a preference value and apply to all users if allowed
     *
     * filter  = (string) Preference name e.g ('notify_email', 'popular_threshold')
     * value   = (string|integer) Preference value
     * all     = (integer) 0,1 if true apply to all users //optional
     * default = (integer) 0,1 if true set as system default (New and public users) //optional
     *
     * @param array{
     *     filter?: string,
     *     value?: string|int,
     *     all?: int,
     *     default?: int,
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
        foreach (['filter', 'value'] as $parameter) {
            if (!array_key_exists($parameter, $input)) {
                throw new RequestParamMissingException(
                    sprintf('Bad Request: %s', $parameter)
                );
            }
        }

        $all     = (array_key_exists('all', $input) && (int) $input['all'] === 1);
        $default = (array_key_exists('default', $input) && (int) $input['default'] === 1);

        // don't apply to all or set default when you aren't an admin
        if (
            ($all || $default)
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

        // fix preferences that are missing for user
        Preference::fix_user_preferences($user->getId());

        // allow getting system prefs if you have access
        $userId = ($all || $default)
            ? User::INTERNAL_SYSTEM_USER_ID
            : $user->getId();

        $prefName   = (string) $input['filter'];
        $preference = Preference::get($prefName, $userId);
        if (empty($preference)) {
            throw new ResultEmptyException(
                $prefName
            );
        }

        if (!Preference::update($prefName, $userId, $input['value'], $all, $default)) {
            $response->getBody()->write(
                $output->error(
                    $apiVersion,
                    ErrorCodeEnum::BAD_REQUEST,
                    'Bad Request',
                    self::ACTION,
                    'system'
                )
            );

            return $response;
        }

        $results = Preference::get($prefName, $userId);
        if ($apiVersion >= 8 && $results !== []) {
            $results[0]['id'] = (string) $results[0]['id'];
        }

        $response->getBody()->write(
            $output->objectArray($apiVersion, $results[0], $results, 'preference')
        );

        return $response;
    }
}
