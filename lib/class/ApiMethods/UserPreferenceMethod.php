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
use XML_Data;

/**
 * Class UserPreferenceMethod
 * @package Lib\ApiMethods
 */
final class UserPreferenceMethod
{
    private const ACTION = 'user_preference';

    /**
     * user_preference
     * MINIMUM_API_VERSION=5.0.0
     *
     * Get your user preference by name
     *
     * @param array $input
     * filter = (string) Preference name e.g ('notify_email', 'ajax_load')
     * @return boolean
     */
    public static function user_preference(array $input)
    {
        $user = User::get_from_username(Session::username($input['auth']));
        // fix preferences that are missing for user
        User::fix_preferences($user->id);

        $pref_name  = (string) $input['filter'];
        $preference = Preference::get($pref_name, $user->id);
        if (empty($preference)) {
            /* HINT: Requested object string/id/type ("album", "myusername", "some song title", 1298376) */
            Api::error(sprintf(T_('Not Found: %s'), $pref_name), '4704', self::ACTION, 'filter', $input['api_format']);

            return false;
        }
        $output_array =  array('preference' => $preference);
        switch ($input['api_format']) {
            case 'json':
                echo json_encode($output_array, JSON_PRETTY_PRINT);
                break;
            default:
                echo XML_Data::object_array($output_array['preference'], 'preference');
        }
        Session::extend($input['auth']);

        return true;
    }
}
