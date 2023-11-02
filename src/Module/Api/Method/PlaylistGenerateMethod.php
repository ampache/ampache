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

namespace Ampache\Module\Api\Method;

use Ampache\Config\AmpConfig;
use Ampache\Repository\Model\Album;
use Ampache\Repository\Model\Artist;
use Ampache\Repository\Model\Search;
use Ampache\Repository\Model\User;
use Ampache\Module\Api\Api;
use Ampache\Module\Api\Json_Data;
use Ampache\Module\Api\Xml_Data;

/**
 * Class PlaylistGenerateMethod
 * @package Lib\ApiMethods
 */
final class PlaylistGenerateMethod
{
    const ACTION = 'playlist_generate';

    /**
     * playlist_generate
     * MINIMUM_API_VERSION=400001
     * CHANGED_IN_API_VERSION=400002
     *
     * Get a list of song xml, indexes or id's based on some simple search criteria
     * 'recent' will search for tracks played after 'Statistics Day Threshold' days
     * 'forgotten' will search for tracks played before 'Statistics Day Threshold' days
     * 'unplayed' added in 400002 for searching unplayed tracks.
     *
     * @param array $input
     * @param User $user
     * mode   = (string)  'recent', 'forgotten', 'unplayed', 'random' //optional, default = 'random'
     * filter = (string)  $filter                       //optional, LIKE matched to song title
     * album  = (integer) $album_id                     //optional
     * artist = (integer) $artist_id                    //optional
     * flag   = (integer) 0,1                           //optional, default = 0
     * format = (string)  'song', 'index', 'id'         //optional, default = 'song'
     * offset = (integer)                               //optional
     * limit  = (integer)                               //optional
     * @return boolean
     */
    public static function playlist_generate(array $input, User $user): bool
    {
        // parameter defaults
        $mode   = (array_key_exists('mode', $input) && in_array($input['mode'], array('forgotten', 'recent', 'unplayed', 'random'), true))
            ? $input['mode']
            : 'random';
        $format = (array_key_exists('format', $input) && in_array($input['format'], array('song', 'index', 'id'), true))
            ? $input['format']
            : 'song';
        // confirm the correct data
        if (!in_array($format, array('song', 'index', 'id'))) {
            Api::error(sprintf(T_('Bad Request: %s'), $format), '4710', self::ACTION, 'type', $input['api_format']);

            return false;
        }
        $offset     = (int)($input['offset'] ?? 0);
        $limit      = (int)($input['limit'] ?? 0);
        $rule_count = 1;
        $data       = array(
            'type' => 'song'
        );
        debug_event(self::class, 'playlist_generate ' . $mode, 5);
        if (in_array($mode, array('forgotten', 'recent'), true)) {
            // played songs
            $data['rule_' . $rule_count]               = 'myplayed';
            $data['rule_' . $rule_count . '_operator'] = 0;
            $rule_count++;

            // not played for a while or played recently
            $data['rule_' . $rule_count]               = 'last_play';
            $data['rule_' . $rule_count . '_input']    = AmpConfig::get('stats_threshold', 7);
            $data['rule_' . $rule_count . '_operator'] = ($mode == 'recent') ? 0 : 1;
            $rule_count++;
        } elseif ($mode == 'unplayed') {
            // unplayed songs
            $data['rule_' . $rule_count]               = 'myplayed';
            $data['rule_' . $rule_count . '_operator'] = 1;
            $rule_count++;
        } else {
            // random / anywhere
            $data['rule_' . $rule_count]               = 'anywhere';
            $data['rule_' . $rule_count . '_input']    = '%';
            $data['rule_' . $rule_count . '_operator'] = 0;
            $rule_count++;
        }
        // additional rules
        if (array_key_exists('', $input) && (int)$input['flag'] == 1) {
            $data['rule_' . $rule_count]               = 'favorite';
            $data['rule_' . $rule_count . '_input']    = '%';
            $data['rule_' . $rule_count . '_operator'] = 0;
            $rule_count++;
        }
        if (array_key_exists('filter', $input)) {
            $data['rule_' . $rule_count]               = 'title';
            $data['rule_' . $rule_count . '_input']    = (string)($input['filter'] ?? '');
            $data['rule_' . $rule_count . '_operator'] = 0;
            $rule_count++;
        }
        $album = new Album((int)($input['album'] ?? 0));
        if ((array_key_exists('album', $input)) && ($album->id == $input['album'])) {
            // set rule
            $data['rule_' . $rule_count]               = 'album';
            $data['rule_' . $rule_count . '_input']    = $album->get_fullname(true);
            $data['rule_' . $rule_count . '_operator'] = 4;
            $rule_count++;
        }
        $artist = new Artist((int)($input['artist'] ?? 0));
        if ((array_key_exists('artist', $input)) && ($artist->id == $input['artist'])) {
            // set rule
            $data['rule_' . $rule_count]               = 'artist';
            $data['rule_' . $rule_count . '_input']    = $artist->get_fullname();
            $data['rule_' . $rule_count . '_operator'] = 4;
        }

        ob_end_clean();
        switch ($input['api_format']) {
            case 'json':
                Json_Data::set_offset($offset);
                Json_Data::set_limit($limit);
                break;
            default:
                Xml_Data::set_offset($offset);
                Xml_Data::set_limit($limit);
        }

        // get db data
        $results = Search::run($data, $user);
        shuffle($results);

        //slice the array if there is a limit
        if ($limit > 0) {
            $results = array_slice($results, 0, $limit);
        }
        if (empty($results)) {
            Api::empty($format, $input['api_format']);

            return false;
        }

        // output formatted XML
        ob_end_clean();
        switch ($format) {
            case 'id':
                switch ($input['api_format']) {
                    case 'json':
                        echo json_encode($results, JSON_PRETTY_PRINT);
                        break;
                    default:
                        echo Xml_Data::keyed_array($results, false, 'id');
                }
                break;
            case 'index':
                switch ($input['api_format']) {
                    case 'json':
                        echo Json_Data::indexes($results, 'song', $user);
                        break;
                    default:
                        echo Xml_Data::indexes($results, 'song', $user);
                }
                break;
            case 'song':
            default:
                switch ($input['api_format']) {
                    case 'json':
                        echo Json_Data::songs($results, $user);
                        break;
                    default:
                        echo Xml_Data::songs($results, $user);
                }
        }

        return true;
    }
}
