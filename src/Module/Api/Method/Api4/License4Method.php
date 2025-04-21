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

namespace Ampache\Module\Api\Method\Api4;

use Ampache\Config\AmpConfig;
use Ampache\Module\Api\Api4;
use Ampache\Module\Api\Json4_Data;
use Ampache\Module\Api\Xml4_Data;
use Ampache\Repository\Model\User;

/**
 * Class License4Method
 */
final class License4Method
{
    public const ACTION = 'license';

    /**
     * license
     * MINIMUM_API_VERSION=420000
     *
     * This returns a single license based on UID
     *
     * filter = (string) UID of license
     *
     * @param array{
     *     filter: string,
     *     api_format: string,
     *     auth: string,
     * } $input
     * @param User $user
     * @return bool
     */
    public static function license(array $input, User $user): bool
    {
        if (!AmpConfig::get('licensing')) {
            Api4::message('error', T_('Access Denied: licensing features are not enabled.'), '400', $input['api_format']);

            return false;
        }
        if (!Api4::check_parameter($input, ['filter'], self::ACTION)) {
            return false;
        }
        unset($user);
        $results = [(int) scrub_in((string) $input['filter'])];
        ob_end_clean();
        switch ($input['api_format']) {
            case 'json':
                echo Json4_Data::licenses($results);
                break;
            default:
                echo Xml4_Data::licenses($results);
        }

        return true;
    }
}
