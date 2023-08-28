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

use Ampache\Config\AmpConfig;
use Ampache\Repository\Model\Song;
use Ampache\Repository\Model\User;
use Ampache\Module\Api\Api5;
use Ampache\Repository\UserRepositoryInterface;

/**
 * Class Scrobble5Method
 */
final class Scrobble5Method
{
    public const ACTION = 'scrobble';

    /**
     * scrobble
     * MINIMUM_API_VERSION=400001
     *
     * Search for a song using text info and then record a play if found.
     * This allows other sources to record play history to Ampache
     *
     * @param array $input
     * @param User $user
     * song       = (string)  $song_name
     * artist     = (string)  $artist_name
     * album      = (string)  $album_name
     * songmbid   = (string)  $song_mbid //optional
     * artistmbid = (string)  $artist_mbid //optional
     * albummbid  = (string)  $album_mbid //optional
     * date       = (integer) UNIXTIME() //optional
     * client     = (string)  $agent //optional
     * @return boolean
     */
    public static function scrobble(array $input, User $user): bool
    {
        if (!Api5::check_parameter($input, array('song', 'artist', 'album'), self::ACTION)) {
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
        $date        = (array_key_exists('date', $input) && is_numeric(scrub_in($input['date']))) ? (int) scrub_in($input['date']) : time(); //optional
        $user_id     = $user->id;
        $valid       = in_array($user->id, static::getUserRepository()->getValid());

        // validate supplied user
        if ($valid === false) {
            /* HINT: Requested object string/id/type ("album", "myusername", "some song title", 1298376) */
            Api5::error(sprintf(T_('Not Found: %s'), $user_id), '4704', self::ACTION, 'empty', $input['api_format']);

            return false;
        }

        // validate minimum required options
        debug_event(self::class, 'scrobble searching for:' . $song_name . ' - ' . $artist_name . ' - ' . $album_name, 4);
        if (!$song_name || !$album_name || !$artist_name) {
            Api5::error(T_('Bad Request'), '4710', self::ACTION, 'input', $input['api_format']);

            return false;
        }

        // validate client string or fall back to 'api'
        $agent       = filter_var($input['client'], FILTER_SANITIZE_STRING) ?? 'api';
        $scrobble_id = Song::can_scrobble($song_name, $artist_name, $album_name, $song_mbid, $artist_mbid, $album_mbid);

        if ($scrobble_id === '') {
            Api5::error(T_('Not Found'), '4704', self::ACTION, 'song', $input['api_format']);
        } else {
            $media = new Song((int) $scrobble_id);
            if (!$media->id) {
                /* HINT: Requested object string/id/type ("album", "myusername", "some song title", 1298376) */
                Api5::error(sprintf(T_('Not Found: %s'), $scrobble_id), '4704', self::ACTION, 'song', $input['api_format']);

                return false;
            }
            debug_event(self::class, 'scrobble: ' . $media->id . ' for ' . $user->username . ' using ' . $agent . ' ' . $date, 5);

            // internal scrobbling (user_activity and object_count tables)
            if ($media->set_played($user_id, $agent, array(), $date)) {
                // scrobble plugins
                User::save_mediaplay($user, $media);
            }

            Api5::message('successfully scrobbled: ' . $scrobble_id, $input['api_format']);
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
