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
use Ampache\Module\Api\Xml6_Data;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Repository\Model\User;
use Ampache\Repository\PreferenceRepositoryInterface;

/**
 * Class SystemPreferences6Method
 * @package Lib\Api6Methods
 */
final class SystemPreferences6Method
{
    public const string ACTION = 'system_preferences';

    /**
     * system_preferences
     * MINIMUM_API_VERSION=5.0.0
     *
     * Get your system preferences
     *
     * @param array{
     *     api_format: string,
     *     auth: string,
     * } $input
     */
    public static function system_preferences(array $input, User $user): bool
    {
        if (!Api6::check_access(AccessTypeEnum::INTERFACE, AccessLevelEnum::ADMIN, $user->id, self::ACTION, $input['api_format'])) {
            return false;
        }
        $results = ['preference' => self::getPreferenceRepository()->getAll(null, true)];

        switch ($input['api_format']) {
            case 'json':
                echo json_encode($results, JSON_PRETTY_PRINT);
                break;
            default:
                echo Xml6_Data::object_array($results['preference'], 'preference');
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
