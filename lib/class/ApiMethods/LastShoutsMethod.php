<?php
/*
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
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

namespace Lib\ApiMethods;

use AmpConfig;
use Api;
use JSON_Data;
use Session;
use Shoutbox;
use XML_Data;

/**
 * Class LastShoutsMethod
 * @package Lib\ApiMethods
 */
final class LastShoutsMethod
{
    private const ACTION = 'last_shouts';

    /**
     * last_shouts
     * MINIMUM_API_VERSION=380001
     *
     * This get the latest posted shouts
     *
     * @param array $input
     * username = (string) $username //optional
     * limit = (integer) $limit //optional
     * @return boolean
     */
    public static function last_shouts(array $input)
    {
        if (!AmpConfig::get('sociable')) {
            Api::error(T_('Enable: sociable'), '4703', self::ACTION, 'system', $input['api_format']);

            return false;
        }
        if (!Api::check_parameter($input, array('username'), self::ACTION)) {
            return false;
        }
        $limit = (int) ($input['limit']);
        if ($limit < 1) {
            $limit = AmpConfig::get('popular_threshold', 10);
        }
        $username = $input['username'];
        $shouts   = (!empty($username))
            ? Shoutbox::get_top($limit, $username)
            : Shoutbox::get_top($limit);

        ob_end_clean();
        switch ($input['api_format']) {
            case 'json':
                echo JSON_Data::shouts($shouts);
                break;
            default:
                echo XML_Data::shouts($shouts);
        }
        Session::extend($input['auth']);

        return true;
    }
}
