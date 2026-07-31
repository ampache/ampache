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
use Ampache\Module\Api\Authentication\GatekeeperInterface;
use Ampache\Module\Api\Exception\ErrorCodeEnum;
use Ampache\Module\Api\Method\Exception\AccessFailedException;
use Ampache\Module\Api\Output\ApiOutputInterface;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Module\Authorization\Check\PrivilegeCheckerInterface;
use Ampache\Module\System\AutoUpdate;
use Ampache\Module\System\Session;
use Ampache\Module\System\Update;
use Ampache\Repository\Model\Preference;
use Ampache\Repository\Model\User;
use Psr\Http\Message\ResponseInterface;

/**
 * Checks Ampache for updates and runs the update if there is one
 */
final class SystemUpdateMethod implements MethodInterface
{
    public const string ACTION = 'system_update';

    public const string REST_ACTION = 'update';

    private ConfigContainerInterface $configContainer;
    private PrivilegeCheckerInterface $privilegeChecker;
    private Update\UpdaterInterface $updater;

    public function __construct(
        ConfigContainerInterface $configContainer,
        PrivilegeCheckerInterface $privilegeChecker,
        Update\UpdaterInterface $updater,
    ) {
        $this->configContainer  = $configContainer;
        $this->privilegeChecker = $privilegeChecker;
        $this->updater          = $updater;
    }

    /**
     * MINIMUM_API_VERSION=400001
     *
     * Check Ampache for updates and run the update if there is one.
     *
     * @param array{
     *     api_format: string,
     *     auth: string,
     * } $input
     * @throws AccessFailedException
     */
    public function handle(
        GatekeeperInterface $gatekeeper,
        ResponseInterface $response,
        ApiOutputInterface $output,
        array $input,
        User $user,
        int $apiVersion,
    ): ResponseInterface {
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

        $updated   = false;
        $hasUpdate = AutoUpdate::is_update_available(true);

        if ($hasUpdate) {
            // run the update; dependencies are pointless when the sources they belong to never arrived
            if (AutoUpdate::update_files(true)) {
                AutoUpdate::update_dependencies($this->configContainer, true);
            }

            Preference::translate_db();

            // check that the update completed or failed.
            $hasUpdate = AutoUpdate::is_update_available(true);
            if ($hasUpdate) {
                Session::extend($input['auth'], AccessTypeEnum::API->value);

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

            $updated = true;
        }

        // update the database
        if ($this->updater->hasPendingUpdates()) {
            try {
                $this->updater->update();

                $updated = true;
            } catch (Update\Exception\UpdateException) {
                // need to return data to the api
            }
        }

        if ($updated) {
            // there was an update and it was successful
            Session::extend($input['auth'], AccessTypeEnum::API->value);

            $response->getBody()->write(
                $output->success($apiVersion, 'update successful')
            );

            return $response;
        }

        // no update available but you are an admin so tell them
        $response->getBody()->write(
            $output->success($apiVersion, 'No update available')
        );

        return $response;
    }
}
