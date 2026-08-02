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
use Ampache\Module\Api\Exception\ErrorCodeEnum;
use Ampache\Module\Api\Method\Exception\AccessFailedException;
use Ampache\Module\Api\Method\Exception\RequestParamMissingException;
use Ampache\Module\Api\Method\Exception\ResultEmptyException;
use Ampache\Module\Api\Method\MethodInterface;
use Ampache\Module\Api\Output\ApiOutputInterface;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Module\Authorization\Check\PrivilegeCheckerInterface;
use Ampache\Module\System\Preference;
use Ampache\Repository\Model\User;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;

/**
 * Edits a preference value, optionally applying it to all users.
 *
 * Version 5 knows nothing about the `default` parameter the later versions accept and wraps the
 * json payload in a `preference` key, so it keeps a method of its own.
 */
final class PreferenceEdit5Method implements MethodInterface
{
    public const string ACTION = 'preference_edit';

    public function __construct(
        private PrivilegeCheckerInterface $privilegeChecker,
        private StreamFactoryInterface $streamFactory,
    ) {}

    /**
     * preference_edit
     * MINIMUM_API_VERSION=5.0.0
     *
     * Edit a preference value and apply to all users if allowed
     *
     * filter = (string) Preference name e.g ('notify_email', 'ajax_load')
     * value = (string|integer) Preference value
     * all = (boolean) apply to all users //optional
     *
     * @param array{
     *     filter: string,
     *     value: string|int,
     *     all?: int,
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
        foreach (['filter', 'value'] as $parameter) {
            if (!array_key_exists($parameter, $input)) {
                throw new RequestParamMissingException(
                    sprintf('Bad Request: %s', $parameter)
                );
            }
        }

        $all = array_key_exists('all', $input) && (int) $input['all'] == 1;

        // don't apply to all when you aren't an admin
        if (
            $all
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

        // fix preferences that are missing for user
        Preference::fix_user_preferences($user->id);

        // allow getting system prefs is you have access
        $user_id = ($all)
            ? User::INTERNAL_SYSTEM_USER_ID
            : $user->id;

        $pref_name  = (string) $input['filter'];
        $preference = Preference::get($pref_name, $user_id);
        if ($preference === []) {
            throw new ResultEmptyException(
                $pref_name
            );
        }

        $value = $input['value'];
        if (!Preference::update($pref_name, $user->id, $value, $all)) {
            return $response->withBody(
                $this->streamFactory->createStream(
                    $output->error(
                        $apiVersion,
                        ErrorCodeEnum::BAD_REQUEST,
                        'Bad Request',
                        self::ACTION,
                        'system'
                    )
                )
            );
        }

        $preference = Preference::get($pref_name, $user_id);

        return $response->withBody(
            $this->streamFactory->createStream(
                $output->objectArray($apiVersion, ['preference' => $preference], $preference, 'preference')
            )
        );
    }
}
