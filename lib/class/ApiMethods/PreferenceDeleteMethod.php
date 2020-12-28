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
use Preference;
use Session;
use User;

/**
 * Class PreferenceDeleteMethod
 * @package Lib\ApiMethods
 */
final class PreferenceDeleteMethod
{
    private const ACTION = 'preference_delete';

    /**
     * preference_delete
     * MINIMUM_API_VERSION=5.0.0
     *
     * Delete a non-system preference by name
     *
     * @param array $input
     * filter = (string) Preference name e.g ('notify_email', 'ajax_load')
     * @return boolean
     */
    public static function preference_delete($input)
    {
        if (!Api::check_parameter($input, array('filter'), self::ACTION)) {
            return false;
        }
        $user = User::get_from_username(Session::username($input['auth']));
        if (!Api::check_access('interface', 100, $user->id, self::ACTION, $input['api_format'])) {
            return false;
        }
        $pref_name  = (string) $input['filter'];
        $preference = Preference::get($pref_name,-1);
        if (empty($preference)) {
            Api::error(sprintf(T_('Not Found: %s'), $pref_name), '4704', self::ACTION, 'filter', $input['api_format']);

            return false;
        }
        Preference::delete($pref_name);
        Api::message("Deleted: $pref_name", $input['api_format']);
        Session::extend($input['auth']);

        return true;
    }
}
