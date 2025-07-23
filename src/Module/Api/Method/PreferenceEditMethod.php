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

namespace Ampache\Module\Api\Method;

use Ampache\Module\Api\Exception\ErrorCodeEnum;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Repository\Model\Preference;
use Ampache\Repository\Model\User;
use Ampache\Module\Api\Api;
use Ampache\Module\Api\Xml_Data;

/**
 * Class PreferenceEditMethod
 * @package Lib\ApiMethods
 */
final class PreferenceEditMethod
{
    public const ACTION = 'preference_edit';

    /**
     * preference_edit
     * MINIMUM_API_VERSION=5.0.0
     *
     * Edit a preference value and apply to all users if allowed
     *
     * filter = (string) Preference name e.g ('notify_email', 'ajax_load')
     * value  = (string|integer) Preference value
     * all    = (integer) 0,1 if true apply to all users //optional
     *
     * @param array{
     *     filter: string,
     *     value: string|int,
     *     all?: int,
     *     api_format: string,
     *     auth: string,
     * } $input
     * @param User $user
     * @return bool
     */
    public static function preference_edit(array $input, User $user): bool
    {
        if (!Api::check_parameter($input, ['filter', 'value'], self::ACTION)) {
            return false;
        }
        $all = (array_key_exists('all', $input) && (int)$input['all'] == 1);
        // don't apply to all when you aren't an admin
        if ($all && !Api::check_access(AccessTypeEnum::INTERFACE, AccessLevelEnum::ADMIN, $user->id, self::ACTION, $input['api_format'])) {
            return false;
        }

        // fix preferences that are missing for user
        User::fix_preferences($user->id);

        // allow getting system prefs is you have access
        $user_id = ($all)
            ? User::INTERNAL_SYSTEM_USER_ID
            : $user->id;

        $pref_name  = $input['filter'];
        $preference = Preference::get($pref_name, $user_id);

        if (empty($preference)) {
            /* HINT: Requested object string/id/type ("album", "myusername", "some song title", 1298376) */
            Api::error(sprintf('Not Found: %s', $pref_name), ErrorCodeEnum::NOT_FOUND, self::ACTION, 'filter', $input['api_format']);

            return false;
        }
        $value = $input['value'];
        if (!Preference::update($pref_name, $user->id, $value, $all)) {
            Api::error('Bad Request', ErrorCodeEnum::BAD_REQUEST, self::ACTION, 'system', $input['api_format']);

            return false;
        }
        $results = Preference::get($pref_name, $user_id);
        switch ($input['api_format']) {
            case 'json':
                echo json_encode($results[0], JSON_PRETTY_PRINT);
                break;
            default:
                echo Xml_Data::object_array($results, 'preference');
        }

        return true;
    }
}
