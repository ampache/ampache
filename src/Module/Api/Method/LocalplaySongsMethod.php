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

namespace Ampache\Module\Api\Method;

use Ampache\Config\AmpConfig;
use Ampache\Module\Api\Api;
use Ampache\Module\Api\Xml_Data;
use Ampache\Module\Playback\Localplay\LocalPlay;
use Ampache\Module\System\Session;
use Ampache\Repository\Model\User;

/**
 * Class LocalplaySongsMethod
 * @package Lib\ApiMethods
 */
final class LocalplaySongsMethod
{
    private const ACTION = 'localplay_songs';

    /**
     * localplay_songs
     * MINIMUM_API_VERSION=5.0.0
     *
     * get the list of songs in your localplay instance
     *
     * @param array $input
     * @return boolean
     */
    public static function localplay_songs(array $input)
    {
        // localplay is actually meant to be behind permissions
        $level = AmpConfig::get('localplay_level', 100);
        if (!Api::check_access('localplay', $level, User::get_from_username(Session::username($input['auth']))->id, self::ACTION, $input['api_format'])) {
            return false;
        }
        // Load their Localplay instance
        $localplay = new Localplay(AmpConfig::get('localplay_controller'));
        if (!$localplay->connect()) {
            Api::error(T_('Unable to connect to localplay controller'), '4710', self::ACTION, 'account', $input['api_format']);

            return false;
        }
        // Pull the current playlist and return the objects
        $songs        = $localplay->get();
        if (empty($songs)) {
            Api::empty('localplay_songs', $input['api_format']);

            return false;
        }
        $output_array = array('localplay_songs' => $songs);

        ob_end_clean();
        switch ($input['api_format']) {
            case 'json':
                echo json_encode($output_array, JSON_PRETTY_PRINT);
                break;
            default:
                echo Xml_Data::object_array($output_array['localplay_songs'], 'localplay_songs');
        }
        Session::extend($input['auth']);

        return true;
    }
}
