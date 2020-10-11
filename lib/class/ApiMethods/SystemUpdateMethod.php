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

declare(strict_types=0);

namespace Lib\ApiMethods;

use Api;
use AutoUpdate;
use User;

final class SystemUpdateMethod
{
    /**
     * system_update
     * MINIMUM_API_VERSION=400001
     *
     * Check Ampache for updates and run the update if there is one.
     *
     * @param array $input
     * @return boolean
     */
    public static function system_update($input)
    {
        $user = User::get_from_username(\Session::username($input['auth']));
        if (!Api::check_access('interface', 100, $user->id)) {
            return false;
        }
        if (AutoUpdate::is_update_available(true)) {
            // run the update
            AutoUpdate::update_files(true);
            AutoUpdate::update_dependencies(true);
            // check that the update completed or failed failed.
            if (AutoUpdate::is_update_available(true)) {
                Api::message('error', 'update failed', '400', $input['api_format']);
                \Session::extend($input['auth']);

                return false;
            }
            // there was an update and it was successful
            Api::message('success', 'update successful', null, $input['api_format']);
            \Session::extend($input['auth']);

            return true;
        }
        //no update available but you are an admin so tell them
        Api::message('success', 'No update available', null, $input['api_format']);
        \Session::extend($input['auth']);

        return true;
    }
}
