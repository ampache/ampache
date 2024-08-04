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

use Ampache\Config\AmpConfig;
use Ampache\Module\Api\Exception\ErrorCodeEnum;
use Ampache\Module\Api\Api5;
use Ampache\Module\Api\Json5_Data;
use Ampache\Module\Api\Xml5_Data;
use Ampache\Repository\Model\User;
use Ampache\Repository\ShoutRepositoryInterface;

/**
 * Class LastShouts5Method
 */
final class LastShouts5Method
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
            Api5::error(T_('Enable: sociable'), ErrorCodeEnum::ACCESS_DENIED, self::ACTION, 'system', $input['api_format']);

            return false;
        }
        if (!Api5::check_parameter($input, ['username'], self::ACTION)) {
            return false;
        }
        unset($user);
        $limit = (int)($input['limit'] ?? 0);
        if ($limit < 1) {
            $limit = (int) AmpConfig::get('popular_threshold', 10);
        }

        $username = (!empty($input['username']))
            ? $input['username']
            : null;

        $results = static::getShoutRepository()->getTop($limit, $username);

        ob_end_clean();
        switch ($input['api_format']) {
            case 'json':
                echo Json5_Data::shouts($results);
                break;
            default:
                echo Xml5_Data::shouts($results);
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
