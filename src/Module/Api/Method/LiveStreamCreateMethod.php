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

namespace Ampache\Module\Api\Method;

use Ampache\Module\Api\Exception\ErrorCodeEnum;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Repository\Model\Catalog;
use Ampache\Repository\Model\Live_Stream;
use Ampache\Repository\Model\User;
use Ampache\Module\Api\Api;
use Ampache\Module\Api\Json_Data;
use Ampache\Module\Api\Xml_Data;

/**
 * Class LiveStreamCreateMethod
 * @package Lib\ApiMethods
 */
final class LiveStreamCreateMethod
{
    public const ACTION = 'live_stream_create';

    /**
     * live_stream_create
     * MINIMUM_API_VERSION=6.0.0
     *
     * Create a live_stream (radio station) object.
     *
     * name     = (string) Stream title
     * url      = (string) URL of the http/s stream
     * codec    = (string) stream codec ('mp3', 'flac', 'ogg', 'vorbis', 'opus', 'aac', 'alac')
     * catalog  = (int) Catalog ID to associate with this stream
     * site_url = (string) Homepage URL of the stream //optional
     */
    public static function live_stream_create(array $input, User $user): bool
    {
        if (!Api::check_access(AccessTypeEnum::INTERFACE, AccessLevelEnum::CONTENT_MANAGER, $user->id, self::ACTION, $input['api_format'])) {
            return false;
        }
        if (!Api::check_parameter($input, ['name', 'codec', 'url', 'catalog'], self::ACTION)) {
            return false;
        }
        $name       = $input['name'];
        $url        = filter_var(urldecode($input['url']), FILTER_VALIDATE_URL) ?: null;
        $codec      = (string)preg_replace("/[^a-z]/", "", strtolower($input['codec']));
        $site_url   = (isset($input['site_url'])) ? filter_var(urldecode($input['site_url']), FILTER_VALIDATE_URL) : null;
        $catalog_id = (int)filter_var($input['catalog'], FILTER_SANITIZE_NUMBER_INT);

        // Make sure it's a real catalog
        $catalog = Catalog::create_from_id($catalog_id);
        if ($catalog === null) {
            /* HINT: Requested object string/id/type ("album", "myusername", "some song title", 1298376) */
            Api::error(sprintf('Not Found: %s', $catalog_id), ErrorCodeEnum::NOT_FOUND, self::ACTION, 'catalog', $input['api_format']);
        }
        if (!$url) {
            /* HINT: Requested object string/id/type ("album", "myusername", "some song title", 1298376) */
            Api::error(sprintf('Bad Request: %s', $url), ErrorCodeEnum::BAD_REQUEST, self::ACTION, 'url', $input['api_format']);

            return false;
        }

        $data = [
            "name" => $name,
            "url" => $url,
            "codec" => $codec,
            "catalog" => $catalog_id,
            "site_url" => $site_url
        ];

        $results = Live_Stream::create($data);
        if (empty($results)) {
            Api::empty(null, $input['api_format']);

            return false;
        }

        ob_end_clean();
        switch ($input['api_format']) {
            case 'json':
                echo Json_Data::live_streams([(int)$results], false);
                break;
            default:
                echo Xml_Data::live_streams([(int)$results], $user);
        }

        return true;
    }
}
