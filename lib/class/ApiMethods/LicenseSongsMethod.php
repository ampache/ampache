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
use License;
use Session;
use User;
use XML_Data;

/**
 * Class LicenseSongsMethod
 * @package Lib\ApiMethods
 */
final class LicenseSongsMethod
{
    private const ACTION = 'license_songs';

    /**
     * license_songs
     * MINIMUM_API_VERSION=420000
     *
     * This returns all songs attached to a license ID
     *
     * @param array $input
     * filter = (string) UID of license
     * @return boolean
     */
    public static function license_songs(array $input)
    {
        if (!AmpConfig::get('licensing')) {
            Api::error(T_('Enable: licensing'), '4703', self::ACTION, 'system', $input['api_format']);

            return false;
        }
        if (!Api::check_parameter($input, array('filter'), self::ACTION)) {
            return false;
        }
        $user     = User::get_from_username(Session::username($input['auth']));
        $song_ids = License::get_license_songs((int) scrub_in($input['filter']));
        if (empty($song_ids)) {
            Api::empty('song', $input['api_format']);

            return false;
        }

        ob_end_clean();
        switch ($input['api_format']) {
            case 'json':
                echo JSON_Data::songs($song_ids, $user->id);
                break;
            default:
                echo XML_Data::songs($song_ids, $user->id);
        }
        Session::extend($input['auth']);

        return true;
    }
}
