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
use Ampache\Repository\Model\Preference;
use Ampache\Repository\Model\User;
use Ampache\Module\Api\Api;
use Ampache\Module\Api\Xml_Data;

/**
 * Class SystemPreferenceMethod
 * @package Lib\ApiMethods
 */
final class SystemPreferenceMethod
{
    public const ACTION = 'system_preference';

    /**
     * system_preference
     * MINIMUM_API_VERSION=5.0.0
     *
     * Get your system preferences by name
     *
     * filter = (string) Preference name e.g ('notify_email', 'ajax_load')
     */
    public static function system_preference(array $input, User $user): bool
    {
        if (!Api::check_parameter($input, ['filter'], self::ACTION)) {
            return false;
        }
        if (!Api::check_access('interface', 100, $user->id, self::ACTION, $input['api_format'])) {
            return false;
        }
        $pref_name = (string)($input['filter'] ?? '');
        $results   = Preference::get($pref_name, -1);
        if (empty($results)) {
            /* HINT: Requested object string/id/type ("album", "myusername", "some song title", 1298376) */
            Api::error(sprintf('Not Found: %s', $pref_name), ErrorCodeEnum::NOT_FOUND, self::ACTION, 'filter', $input['api_format']);

            return false;
        }
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
