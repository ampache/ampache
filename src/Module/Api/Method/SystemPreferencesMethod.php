<?php
/*
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 *  LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright 2001 - 2022 Ampache.org
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
 * Class SystemPreferencesMethod
 * @package Lib\ApiMethods
 */
final class SystemPreferencesMethod
{
    public const ACTION = 'system_preferences';

    /**
     * system_preferences
     * MINIMUM_API_VERSION=5.0.0
     *
     * Get your system preferences
     *
     * @param array $input
     * @param User $user
     * @return boolean
     */
    public static function system_preferences(array $input, User $user): bool
    {
        if (!Api::check_access('interface', 100, $user->id, self::ACTION, $input['api_format'])) {
            return false;
        }
        $preferences  = Preference::get_all(-1);
        $output_array = array('preference' => $preferences);
        switch ($input['api_format']) {
            case 'json':
                echo json_encode($output_array, JSON_PRETTY_PRINT);
                break;
            default:
                echo Xml_Data::object_array($output_array['preference'], 'preference');
        }

        return true;
    }
}
