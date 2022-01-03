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
use Ampache\Module\Util\InterfaceImplementationChecker;
use Ampache\Module\Util\ObjectTypeToClassNameMapper;
use Ampache\Repository\Model\Rating;
use Ampache\Repository\Model\User;
use Ampache\Module\Api\Api4;
use Ampache\Module\System\Session;

/**
 * Class Rate4Method
 */
final class Rate4Method
{
    public const ACTION = 'rate';

    /**
     * rate
     * MINIMUM_API_VERSION=380001
     *
     * This rates a library item
     *
     * @param array $input
     * type   = (string) 'song', 'album', 'artist', 'playlist', 'podcast', 'podcast_episode', 'video', 'tvshow', 'tvshow_season' $type
     * id     = (integer) $object_id
     * rating = (integer) 0,1|2|3|4|5 $rating
     * @return boolean
     */
    public static function rate(array $input): bool
    {
        if (!AmpConfig::get('ratings')) {
            Api4::message('error', T_('Access Denied: Rating features are not enabled.'), '400', $input['api_format']);

            return false;
        }
        if (!Api4::check_parameter($input, array('type', 'id', 'rating'), 'rate')) {
            return false;
        }
        ob_end_clean();
        $type      = ObjectTypeToClassNameMapper::map((string)$input['type']);
        $object_id = (int) $input['id'];
        $rating    = (string) $input['rating'];
        $user      = User::get_from_username(Session::username($input['auth']));

        // confirm the correct data
        if (!in_array($type, array('song', 'album', 'artist', 'playlist', 'podcast', 'podcast_episode', 'video', 'tvshow', 'tvshow_season'))) {
            Api4::message('error', T_('Incorrect object type') . ' ' . $type, '401', $input['api_format']);

            return false;
        }
        if (!in_array($rating, array('0', '1', '2', '3', '4', '5'))) {
            Api4::message('error', T_('Ratings must be between [0-5]. ' . $rating . ' is invalid'), '401', $input['api_format']);

            return false;
        }

        if (!InterfaceImplementationChecker::is_library_item($type) || !$object_id) {
            Api4::message('error', T_('Wrong library item type'), '401', $input['api_format']);
        } else {
            $item = new $type($object_id);
            if (!$item->id) {
                Api4::message('error', T_('Library item not found'), '404', $input['api_format']);

                return false;
            }
            $rate = new Rating($object_id, $type);
            $rate->set_rating($rating, $user->id);
            Api4::message('success', 'rating set to ' . $rating . ' for ' . $object_id, null, $input['api_format']);
        }

        return true;
    } // rate
}
