<?php

declare(strict_types=0);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2026
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

namespace Ampache\Module\Api\Method\Api6;

use Ampache\Config\AmpConfig;
use Ampache\Module\Api\Api6;
use Ampache\Module\Api\Exception\ErrorCodeEnum;
use Ampache\Module\Api\Json6_Data;
use Ampache\Module\Api\Xml6_Data;
use Ampache\Module\Playback\Stream_Url;
use Ampache\Repository\Model\User;

/**
 * Class UrlToSong6Method
 * @package Lib\Api6Methods
 */
final class UrlToSong6Method
{
    public const string ACTION = 'url_to_song';

    /**
     * url_to_song
     * MINIMUM_API_VERSION=380001
     *
     * This takes a url and returns the song object in question
     *
     * url = (string) $url
     *
     * @param array{
     *     filter?: string,
     *     url?: string,
     *     api_format: string,
     *     auth: string,
     * } $input
     */
    public static function url_to_song(array $input, User $user): bool
    {
        $input['url'] = $input['filter'] ?? $input['url'] ?? null;
        if (!Api6::check_parameter($input, ['url'], self::ACTION)) {
            return false;
        }
        $charset  = AmpConfig::get('site_charset', 'UTF-8');
        $song_url = html_entity_decode((string)$input['url'], ENT_QUOTES, $charset);
        $url_data = Stream_Url::parse($song_url);
        if (!array_key_exists('id', $url_data)) {
            Api6::error('Bad Request', ErrorCodeEnum::BAD_REQUEST, self::ACTION, 'url', $input['api_format']);

            return false;
        }
        ob_end_clean();
        switch ($input['api_format']) {
            case 'json':
                echo Json6_Data::songs([(int)$url_data['id']], $user, $input['auth'], true, false);
                break;
            default:
                echo Xml6_Data::songs([(int)$url_data['id']], $user, $input['auth']);
        }

        return true;
    }
}
