<?php

declare(strict_types=0);

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

namespace Ampache\Module\Api\Method\Api6;

use Ampache\Module\Api\Api6;
use Ampache\Module\Api\Exception\ErrorCodeEnum;
use Ampache\Module\Api\Xml6_Data;
use Ampache\Repository\Model\Preference;
use Ampache\Repository\Model\User;

/**
 * Class UserPreference6Method
 * @package Lib\Api6Methods
 */
final class UserPreference6Method
{
    public const string ACTION = 'user_preference';

    /**
     * user_preference
     * MINIMUM_API_VERSION=5.0.0
     *
     * Get your user preference by name
     *
     * filter = (string) Preference name e.g ('notify_email', 'ajax_load')
     *
     * @param array{
     *     filter?: string,
     *     api_format: string,
     *     auth: string,
     * } $input
     */
    public static function user_preference(array $input, User $user): bool
    {
        // fix preferences that are missing for user
        User::fix_preferences($user->id);

        $pref_name = (string)($input['filter'] ?? '');
        $results   = Preference::get($pref_name, $user->id);
        if (empty($results)) {
            /* HINT: Requested object string/id/type ("album", "myusername", "some song title", 1298376) */
            Api6::error(sprintf('Not Found: %s', $pref_name), ErrorCodeEnum::NOT_FOUND, self::ACTION, 'filter', $input['api_format']);

            return false;
        }

        $preference = [
            "id" => (string)$results[0]['id'],
            "name" => $results[0]['name'],
            "level" => $results[0]['level'],
            "description" => $results[0]['description'],
            "value" => $results[0]['value'],
            "type" => $results[0]['type'],
            "category" => $results[0]['category'],
            "subcategory" => $results[0]['subcategory'],
            "has_access" => (((int)$results[0]['level']) <= $user->access),
            "values" => [],
        ];

        if ($preference['type'] == 'special') {
            $values = Preference::get_special_values($preference['name'], $user);
            if ($values) {
                $preference['values'] = $values;
            }
        } else {
            unset($preference['values']);
        }

        switch ($input['api_format']) {
            case 'json':
                echo json_encode($preference, JSON_PRETTY_PRINT);
                break;
            default:
                echo Xml6_Data::object_array([$preference], 'preference');
        }

        return true;
    }
}
