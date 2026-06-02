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

namespace Ampache\Module\Api\Method\Api8;

use Ampache\Module\Api\Api;
use Ampache\Module\Api\Exception\ErrorCodeEnum;
use Ampache\Module\Api\Xml8_Data;
use Ampache\Repository\Model\Preference;
use Ampache\Repository\Model\User;

/**
 * Class UserPreference8Method
 * @package Lib\Api8Methods
 */
final class UserPreference8Method
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

        $pref_name  = $input['filter'];
        $preference = Preference::get($pref_name, -1);
        if (empty($preference)) {
            /* HINT: Requested object string/id/type ("album", "myusername", "some song title", 1298376) */
            Api::error(sprintf('Not Found: %s', $pref_name), ErrorCodeEnum::NOT_FOUND, self::ACTION, 'filter', $input['api_format']);

            return false;
        }

        $results   = [];
        $results[] = [
            "id" => (string)$preference[0]['id'],
            "name" => $preference[0]['name'],
            "level" => $preference[0]['level'],
            "description" => $preference[0]['description'],
            "value" => $preference[0]['value'],
            "type" => $preference[0]['type'],
            "category" => $preference[0]['category'],
            "subcategory" => $preference[0]['subcategory'],
            "has_access" => (((int)$preference[0]['level']) <= $user->access),
            "values" => [],
        ];

        if ($preference[0]['type'] == 'special') {
            $values = Preference::get_special_values($preference[0]['name'], $user);
            if ($values) {
                $results[0]['values'] = $values;
            }
        } else {
            unset($results[0]['values']);
        }

        switch ($input['api_format']) {
            case 'json':
                echo json_encode($results[0], JSON_PRETTY_PRINT);
                break;
            default:
                echo Xml8_Data::object_array($results, 'preference');
        }

        return true;
    }
}
