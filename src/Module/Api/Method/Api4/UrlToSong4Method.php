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

namespace Ampache\Module\Api\Method\Api4;

use Ampache\Config\AmpConfig;
use Ampache\Repository\Model\User;
use Ampache\Module\Api\Api4;
use Ampache\Module\Api\Json4_Data;
use Ampache\Module\Api\Xml4_Data;
use Ampache\Module\Playback\Stream_Url;

/**
 * Class UrlToSong4Method
 */
final class UrlToSong4Method
{
    public const ACTION = 'url_to_song';

    /**
     * url_to_song
     * MINIMUM_API_VERSION=380001
     *
     * This takes a url and returns the song object in question
     *
     * url = (string) $url
     */
    public static function url_to_song(array $input, User $user): bool
    {
        if (!Api4::check_parameter($input, ['url'], self::ACTION)) {
            return false;
        }
        $charset  = AmpConfig::get('site_charset');
        $song_url = html_entity_decode($input['url'], ENT_QUOTES, $charset);
        $url_data = Stream_URL::parse($song_url);
        ob_end_clean();
        switch ($input['api_format']) {
            case 'json':
                echo Json4_Data::songs([(int)($url_data['id'] ?? 0)], $user);
                break;
            default:
                echo Xml4_Data::songs([(int)($url_data['id'] ?? 0)], $user);
        }

        return true;
    }
}
