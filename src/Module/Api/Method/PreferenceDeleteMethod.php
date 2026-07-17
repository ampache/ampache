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
use Ampache\Repository\Model\Preference;
use Ampache\Repository\Model\User;
use Psr\Http\Message\ResponseInterface;

/**
 * Deletes a non-system preference by name
 */
final class PreferenceDeleteMethod implements MethodInterface
{
    public const string ACTION = 'preference_delete';

    public const string REST_ACTION = 'preferences_delete';

    private PrivilegeCheckerInterface $privilegeChecker;

    public function __construct(
        PrivilegeCheckerInterface $privilegeChecker,
    ) {
        $this->privilegeChecker = $privilegeChecker;
    }

    /**
     * MINIMUM_API_VERSION=5.0.0
     *
     * Delete a non-system preference by name
     *
     * filter = (string) Preference name e.g ('notify_email', 'ajax_load')
     *
     * @param array{
     *     filter?: string,
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
        if (!array_key_exists('filter', $input)) {
            throw new RequestParamMissingException(
                sprintf('Bad Request: %s', 'filter')
            );
        }

        if (
            !$this->privilegeChecker->check(
                AccessTypeEnum::INTERFACE,
                AccessLevelEnum::ADMIN,
                $user->getId()
            )
        ) {
            throw new AccessFailedException(
                sprintf('Require: %s', AccessLevelEnum::ADMIN->value)
            );
        }

        $prefName   = (string) $input['filter'];
        $preference = Preference::get($prefName, -1);

        if (empty($preference)) {
            throw new ResultEmptyException(
                $prefName
            );
        }

        // Might be a good idea to not delete system preferences
        if (in_array($prefName, array_merge(Preference::SYSTEM_LIST, Preference::PLUGIN_LIST))) {
            $response->getBody()->write(
                $output->error(
                    $apiVersion,
                    ErrorCodeEnum::BAD_REQUEST,
                    sprintf('Bad Request: %s', $prefName),
                    self::ACTION,
                    'filter'
                )
            );

            return $response;
        }

        Preference::delete($prefName);

        $response->getBody()->write(
            $output->success($apiVersion, sprintf('Deleted: %s', $prefName))
        );

        return $response;
    }
}
