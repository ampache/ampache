<?php

/*
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 *  LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright 2001 - 2020 Ampache.org
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

namespace Ampache\Module\Api\Method\Api4;

use Ampache\Config\AmpConfig;
use Ampache\Module\Api\Api4;
use Ampache\Module\Api\Json4_Data;
use Ampache\Module\Api\Xml4_Data;

/**
 * Class Share4Method
 */
final class Share4Method
{
    public const ACTION = 'share';

    /**
     * share
     * MINIMUM_API_VERSION=420000
     *
     * Get the share from it's id.
     *
     * @param array $input
     * filter = (integer) Share ID number
     * @return boolean
     */
    public static function share(array $input): bool
    {
        if (!AmpConfig::get('share')) {
            Api4::message('error', T_('Access Denied: sharing features are not enabled.'), '400', $input['api_format']);

            return false;
        }
        if (!Api4::check_parameter($input, array('filter'), 'share')) {
            return false;
        }
        $share = array((int) $input['filter']);

        ob_end_clean();
        switch ($input['api_format']) {
            case 'json':
                echo Json4_Data::shares($share);
            break;
            default:
                echo Xml4_Data::shares($share);
        }

        return true;
    } // share
}
