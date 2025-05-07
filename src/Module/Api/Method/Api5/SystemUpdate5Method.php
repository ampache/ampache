<?php

declare(strict_types=0);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2024
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

use Ampache\Config\ConfigContainerInterface;
use Ampache\Module\Api\Exception\ErrorCodeEnum;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Module\System\Update;
use Ampache\Repository\Model\Preference;
use Ampache\Repository\Model\User;
use Ampache\Module\Api\Api5;
use Ampache\Module\System\AutoUpdate;
use Ampache\Module\System\Session;

/**
 * Class SystemUpdate5Method
 */
final class SystemUpdate5Method
{
    public const ACTION = 'system_update';

    /**
     * system_update
     * MINIMUM_API_VERSION=400001
     *
     * Check Ampache for updates and run the update if there is one.
     *
     * @param array{
     *     api_format: string,
     *     auth: string,
     * } $input
     * @param User $user
     * @return bool
     */
    public static function system_update(array $input, User $user): bool
    {
        if (!Api5::check_access(AccessTypeEnum::INTERFACE, AccessLevelEnum::ADMIN, $user->id, self::ACTION, $input['api_format'])) {
            return false;
        }
        $updated = false;
        if (AutoUpdate::is_update_available(true)) {
            // run the update
            AutoUpdate::update_files(true);
            AutoUpdate::update_dependencies(self::getConfigContainer(), true);
            Preference::translate_db();
            // check that the update completed or failed.
            if (AutoUpdate::is_update_available(true)) {
                Api5::error(T_('Bad Request'), ErrorCodeEnum::BAD_REQUEST, self::ACTION, 'system', $input['api_format']);
                Session::extend($input['auth'], AccessTypeEnum::API->value);

                return false;
            }
            $updated = true;
        }

        $updater = self::getUpdater();

        // update the database
        if ($updater->hasPendingUpdates()) {
            try {
                $updater->update();

                $updated = true;
            } catch (Update\Exception\UpdateException) {
                // need to return data to the api
            }
        }
        if ($updated) {
            // there was an update and it was successful
            Api5::message('update successful', $input['api_format']);
            Session::extend($input['auth'], AccessTypeEnum::API->value);

            return true;
        }
        //no update available but you are an admin so tell them
        Api5::message('No update available', $input['api_format']);

        return true;
    }

    /**
     * @todo inject dependency
     */
    private static function getConfigContainer(): ConfigContainerInterface
    {
        global $dic;

        return $dic->get(ConfigContainerInterface::class);
    }

    /**
     * @todo inject dependency
     */
    private static function getUpdater(): Update\UpdaterInterface
    {
        global $dic;

        return $dic->get(Update\UpdaterInterface::class);
    }
}
