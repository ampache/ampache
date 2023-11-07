<?php
/*
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2023
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

namespace Ampache\Module\Api\Method;

use Ampache\Repository\Model\Preference;
use Ampache\Repository\Model\User;
use Ampache\Module\Api\Api;
use Ampache\Module\Api\Xml_Data;

/**
 * Class PreferenceCreateMethod
 * @package Lib\ApiMethods
 */
final class PreferenceCreateMethod
{
    public const ACTION = 'preference_create';

    /**
     * preference_create
     * MINIMUM_API_VERSION=5.0.0
     *
     * Add a new preference to your server
     *
     * This inserts a new preference into the preference table
     *
     * filter      = (string) preference name
     * type        = (string) 'boolean', 'integer', 'string', 'special'
     * default     = (string|integer) default value
     * category    = (string) 'interface', 'internal', 'options', 'playlist', 'plugins', 'streaming'
     * description = (string) description of preference //optional
     * subcategory = (string) $subcategory //optional
     * level       = (integer) access level required to change the value (default 100) //optional
     */
    public static function preference_create(array $input, User $user): bool
    {
        if (!Api::check_parameter($input, array('filter', 'type', 'default', 'category'), self::ACTION)) {
            return false;
        }
        if (!Api::check_access('interface', 100, $user->id, self::ACTION, $input['api_format'])) {
            return false;
        }
        $pref_name = (string)($input['filter'] ?? '');
        $pref_list = Preference::get($pref_name, -1);
        // if you found the preference or it's a system preference; don't add it.
        if (!empty($pref_list) || in_array($pref_name, Preference::SYSTEM_LIST)) {
            /* HINT: Requested object string/id/type ("album", "myusername", "some song title", 1298376) */
            Api::error(sprintf(T_('Bad Request: %s'), $pref_name), '4710', self::ACTION, 'filter', $input['api_format']);

            return false;
        }
        $type = (string) $input['type'];
        if (!in_array(strtolower($type), array('boolean', 'integer', 'string', 'special'))) {
            Api::error(sprintf(T_('Bad Request: %s'), $type), '4710', self::ACTION, 'type', $input['api_format']);

            return false;
        }
        $category = (string) $input['category'];
        if (!in_array($category, array('interface', 'internal', 'options', 'playlist', 'plugins', 'streaming'))) {
            Api::error(sprintf(T_('Bad Request: %s'), $type), '4710', self::ACTION, 'category', $input['api_format']);

            return false;
        }
        $level       = (isset($input['level'])) ? (int) $input['level'] : 100;
        $default     = ($type == 'boolean' || $type == 'integer') ? (int) $input['default'] : (string) $input['default'];
        $description = (string)($input['description'] ?? '');
        $subcategory = (string)($input['subcategory'] ?? '');

        // insert and return the new preference
        Preference::insert($pref_name, $description, $default, $level, $type, $category, $subcategory);
        $results = Preference::get($pref_name, -1);
        if (empty($results)) {
            /* HINT: Requested object string/id/type ("album", "myusername", "some song title", 1298376) */
            Api::error(sprintf(T_('Not Found: %s'), $pref_name), '4704', self::ACTION, 'system', $input['api_format']);

            return false;
        }
        switch ($input['api_format']) {
            case 'json':
                echo json_encode($results[0], JSON_PRETTY_PRINT);
                break;
            default:
                echo Xml_Data::object_array($results, 'preference');
        }
        // fix preferences that are missing for user
        User::fix_preferences($user->id);

        return true;
    }
}
