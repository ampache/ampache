<?php

/*
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright 2001 - 2022 Ampache.org
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

use Ampache\Repository\Model\Catalog;
use Ampache\Repository\Model\Live_Stream;
use Ampache\Repository\Model\User;
use Ampache\Module\Api\Api;
use Ampache\Module\Api\Json_Data;
use Ampache\Module\Api\Xml_Data;

/**
 * Class LiveStreamEditMethod
 * @package Lib\ApiMethods
 */
final class LiveStreamEditMethod
{
    public const ACTION = 'live_stream_edit';

    /**
     * live_stream_edit
     * MINIMUM_API_VERSION=6.0.0
     *
     * Edit a live_stream (radio station) object.
     *
     * @param array $input
     * @param User $user
     * filter   = (string) object_id
     * name     = (string) Stream title //optional
     * url      = (string) URL of the http/s stream //optional
     * codec    = (string) stream codec ('mp3', 'flac', 'ogg', 'vorbis', 'opus', 'aac', 'alac') //optional
     * catalog  = (int) Catalog ID to associate with this stream //optional
     * site_url = (string) Homepage URL of the stream //optional
     * @return boolean
     */
    public static function live_stream_edit(array $input, User $user): bool
    {
        if (!Api::check_access('interface', 50, $user->id, self::ACTION, $input['api_format'])) {
            return false;
        }
        if (!Api::check_parameter($input, array('filter'), self::ACTION)) {
            return false;
        }
        $object_id = filter_var($input['filter'], FILTER_SANITIZE_NUMBER_INT);
        $item      = new Live_Stream($object_id);
        if (!$item->id) {
            /* HINT: Requested object string/id/type ("album", "myusername", "some song title", 1298376) */
            Api::error(sprintf(T_('Not Found: %s'), $object_id), '4704', self::ACTION, 'filter', $input['api_format']);

            return false;
        }
        $name       = (isset($input['name'])) ? filter_var(urldecode($input['name']), FILTER_SANITIZE_SPECIAL_CHARS) : $item->name;
        $url        = (isset($input['url'])) ? filter_var(urldecode($input['url']), FILTER_VALIDATE_URL) ?? '' : $item->url;
        $codec      = (isset($input['codec'])) ? preg_replace("/[^a-z]/", "", strtolower($input['codec'])) ?? '' : $item->codec;
        $site_url   = (isset($input['site_url'])) ? filter_var(urldecode($input['site_url']), FILTER_VALIDATE_URL) ?? '' : $item->site_url;
        $catalog_id = (isset($input['catalog'])) ? filter_var($input['catalog'], FILTER_SANITIZE_NUMBER_INT) : $item->catalog;

        // Make sure it's a real catalog
        $catalog = Catalog::create_from_id($catalog_id);
        if (!$catalog->name) {
            /* HINT: Requested object string/id/type ("album", "myusername", "some song title", 1298376) */
            Api::error(sprintf(T_('Not Found: %s'), $catalog_id), '4704', self::ACTION, 'catalog', $input['api_format']);
        }

        $data = array(
            "object_id" => $object_id,
            "name" => $name,
            "url" => $url,
            "codec" => $codec,
            "catalog" => $catalog_id,
            "site_url" => $site_url
        );

        // check for the live_stream first
        $results = $item->update($data);
        if (empty($results)) {
            Api::empty('live_stream', $input['api_format']);

            return false;
        }

        ob_end_clean();
        switch ($input['api_format']) {
            case 'json':
                echo Json_Data::live_streams(array((int)$results));
                break;
            default:
                echo Xml_Data::live_streams(array((int)$results), $user);
        }

        return true;
    }
}
