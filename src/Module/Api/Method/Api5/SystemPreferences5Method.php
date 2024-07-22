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

use Ampache\Repository\Model\User;
use Ampache\Module\Api\Api5;
use Ampache\Module\Api\Xml5_Data;
use Ampache\Repository\PreferenceRepositoryInterface;

/**
 * Class SystemPreferences5Method
 */
final class SystemPreferences5Method
{
    public const ACTION = 'system_preferences';

    /**
     * system_preferences
     * MINIMUM_API_VERSION=5.0.0
     *
     * Get your system preferences
     */
    public static function system_preferences(array $input, User $user): bool
    {
        if (!Api5::check_access('interface', 100, $user->id, self::ACTION, $input['api_format'])) {
            return false;
        }

        $results = ['preference' => self::getPreferenceRepository()->getAll()];

        switch ($input['api_format']) {
            case 'json':
                echo json_encode($results, JSON_PRETTY_PRINT);
                break;
            default:
                echo Xml5_Data::object_array($results['preference'], 'preference');
        }

        return true;
    }

    /**
     * @todo Replace by constructor injection
     */
    private static function getPreferenceRepository(): PreferenceRepositoryInterface
    {
        global $dic;

        return $dic->get(PreferenceRepositoryInterface::class);
    }
}
