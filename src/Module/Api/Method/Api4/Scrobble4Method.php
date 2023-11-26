<?php

/**
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

namespace Ampache\Module\Api\Method\Api4;

use Ampache\Config\AmpConfig;
use Ampache\Repository\Model\Song;
use Ampache\Repository\Model\User;
use Ampache\Module\Api\Api4;
use Ampache\Repository\UserRepositoryInterface;

/**
 * Class Scrobble4Method
 */
final class Scrobble4Method
{
    public const ACTION = 'scrobble';

    /**
     * scrobble
     * MINIMUM_API_VERSION=400001
     *
     * Search for a song using text info and then record a play if found.
     * This allows other sources to record play history to Ampache
     *
     * song       = (string)  $song_name
     * artist     = (string)  $artist_name
     * album      = (string)  $album_name
     * songmbid   = (string)  $song_mbid //optional
     * artistmbid = (string)  $artist_mbid //optional
     * albummbid  = (string)  $album_mbid //optional
     * date       = (integer) UNIXTIME() //optional
     * client     = (string)  $agent //optional
     */
    public static function scrobble(array $input, User $user): bool
    {
        if (!Api4::check_parameter($input, array('song', 'artist', 'album'), self::ACTION)) {
            return false;
        }
        ob_end_clean();
        $charset     = AmpConfig::get('site_charset');
        $song_name   = html_entity_decode(scrub_out($input['song']), ENT_QUOTES, $charset);
        $artist_name = html_entity_decode(scrub_out($input['artist']), ENT_QUOTES, $charset);
        $album_name  = html_entity_decode(scrub_out($input['album']), ENT_QUOTES, $charset);
        $song_mbid   = html_entity_decode(scrub_out($input['song_mbid'] ?? $input['songmbid'] ?? ''), ENT_QUOTES, $charset); //optional
        $artist_mbid = html_entity_decode(scrub_out($input['artist_mbid'] ?? $input['artistmbid'] ?? ''), ENT_QUOTES, $charset); //optional
        $album_mbid  = html_entity_decode(scrub_out($input['album_mbid'] ?? $input['albummbid'] ?? ''), ENT_QUOTES, $charset); //optional
        $date        = (array_key_exists('date', $input) && is_numeric(scrub_in((string) $input['date']))) ? (int) scrub_in((string) $input['date']) : time(); //optional
        $user_id     = $user->id;
        $valid       = in_array($user->id, static::getUserRepository()->getValid());

        // validate supplied user
        if ($valid === false) {
            Api4::message('error', T_('User_id not found'), '404', $input['api_format']);

            return false;
        }

        // validate minimum required options
        debug_event(self::class, 'scrobble searching for:' . $song_name . ' - ' . $artist_name . ' - ' . $album_name, 4);
        if (!$song_name || !$album_name || !$artist_name) {
            Api4::message('error', T_('Invalid input options'), '401', $input['api_format']);

            return false;
        }

        // validate client string or fall back to 'api'
        if ($input['client']) {
            $agent = $input['client'];
        } else {
            $agent = 'api';
        }
        $scrobble_id = Song::can_scrobble($song_name, $artist_name, $album_name, (string) $song_mbid, (string) $artist_mbid, (string) $album_mbid);

        if ($scrobble_id === '') {
            Api4::message('error', T_('Failed to scrobble: No item found!'), '401', $input['api_format']);
        } else {
            $item = new Song((int) $scrobble_id);
            if (!$item->id) {
                Api4::message('error', T_('Library item not found'), '404', $input['api_format']);

                return false;
            }
            debug_event(self::class, 'scrobble: ' . $item->id . ' for ' . $user->username . ' using ' . $agent . ' ' . (string) time(), 5);

            // internal scrobbling (user_activity and object_count tables)
            $item->set_played($user_id, $agent, array(), $date);

            // scrobble plugins
            User::save_mediaplay($user, $item);

            Api4::message('success', 'successfully scrobbled: ' . $scrobble_id, null, $input['api_format']);
        }

        return true;
    } // scrobble

    /**
     * @deprecated inject dependency
     */
    private static function getUserRepository(): UserRepositoryInterface
    {
        global $dic;

        return $dic->get(UserRepositoryInterface::class);
    }
}
