<?php

declare(strict_types=0);

/**
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

namespace Ampache\Module\Api\Method\Api4;

use Ampache\Config\AmpConfig;
use Ampache\Module\Api\Api4;
use Ampache\Module\Api\Json4_Data;
use Ampache\Module\Api\Xml4_Data;
use Ampache\Repository\Model\User;
use Ampache\Repository\ShoutRepositoryInterface;

/**
 * Class LastShouts4Method
 */
final class LastShouts4Method
{
    public const ACTION = 'last_shouts';

    /**
     * last_shouts
     * MINIMUM_API_VERSION=380001
     *
     * This get the latest posted shouts
     *
     * username = (string) $username //optional
     * limit = (integer) $limit //optional
     */
    public static function last_shouts(array $input, User $user): bool
    {
        if (!AmpConfig::get('sociable')) {
            Api4::message('error', T_('Access Denied: social features are not enabled.'), '400', $input['api_format']);

            return false;
        }
        if (!Api4::check_parameter($input, array('username'), self::ACTION)) {
            return false;
        }
        unset($user);
        $limit = (int)($input['limit'] ?? 0);
        if ($limit < 1) {
            $limit = (int) AmpConfig::get('popular_threshold', 10);
        }
        if (!empty($input['username'])) {
            $username = $input['username'];
        } else {
            $username = null;
        }

        $results = static::getShoutRepository()->getTop($limit, $username);

        ob_end_clean();
        switch ($input['api_format']) {
            case 'json':
                echo Json4_Data::shouts($results);
                break;
            default:
                echo Xml4_Data::shouts($results);
        }

        return true;
    }

    /**
     * @todo inject by constructor
     */
    private static function getShoutRepository(): ShoutRepositoryInterface
    {
        global $dic;

        return $dic->get(ShoutRepositoryInterface::class);
    }
}
