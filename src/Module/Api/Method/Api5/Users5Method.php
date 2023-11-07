<?php

/*
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 *  LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
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

namespace Ampache\Module\Api\Method\Api5;

use Ampache\Module\Api\Api5;
use Ampache\Module\Api\Json5_Data;
use Ampache\Module\Api\Xml5_Data;
use Ampache\Repository\Model\User;
use Ampache\Repository\UserRepositoryInterface;

/**
 * Class Users5Method
 */
final class Users5Method
{
    const ACTION = 'users';

    /**
     * users
     * MINIMUM_API_VERSION=5.0.0
     *
     * Get ids and usernames for your site
     */
    public static function users(array $input, User $user): bool
    {
        $results = static::getUserRepository()->getValid();
        if (empty($results)) {
            Api5::empty('user', $input['api_format']);

            return false;
        }
        unset($user);

        ob_end_clean();
        switch ($input['api_format']) {
            case 'json':
                echo Json5_Data::users($results);
                break;
            default:
                echo Xml5_Data::users($results);
        }

        return true;
    }

    /**
     * @deprecated inject dependency
     */
    private static function getUserRepository(): UserRepositoryInterface
    {
        global $dic;

        return $dic->get(UserRepositoryInterface::class);
    }
}
