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

use Ampache\Config\AmpConfig;
use Ampache\Module\Api\Api;
use Ampache\Module\Api\Exception\ErrorCodeEnum;
use Ampache\Module\Api\Json_Data;
use Ampache\Module\Api\Xml_Data;
use Ampache\Repository\Model\User;

/**
 * Class ShareMethod
 * @package Lib\ApiMethods
 */
final class ShareMethod
{
    public const ACTION = 'share';

    /**
     * share
     * MINIMUM_API_VERSION=420000
     *
     * Get the share from it's id.
     *
     * filter = (integer) Share ID number
     */
    public static function share(array $input, User $user): bool
    {
        if (!AmpConfig::get('share')) {
            Api::error('Enable: share', ErrorCodeEnum::ACCESS_DENIED, self::ACTION, 'system', $input['api_format']);

            return false;
        }
        if (!Api::check_parameter($input, ['filter'], self::ACTION)) {
            return false;
        }
        $results = [(int) $input['filter']];

        ob_end_clean();
        switch ($input['api_format']) {
            case 'json':
                echo Json_Data::shares($results, false);
                break;
            default:
                echo Xml_Data::shares($results, $user);
        }

        return true;
    }
}
