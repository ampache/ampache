<?php

declare(strict_types=0);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
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

namespace Ampache\Module\Api\Method;

use Ampache\Config\AmpConfig;
use Ampache\Module\Api\Exception\ErrorCodeEnum;
use Ampache\Module\Util\ObjectTypeToClassNameMapper;
use Ampache\Repository\Model\Album;
use Ampache\Repository\Model\Artist;
use Ampache\Repository\Model\Catalog;
use Ampache\Repository\Model\Podcast;
use Ampache\Repository\Model\User;
use Ampache\Module\Api\Api;
use Ampache\Module\Api\Json_Data;
use Ampache\Module\Api\Xml_Data;

/**
 * Class ListMethod
 * @package Lib\ApiMethods
 */
final class BrowseMethod
{
    public const ACTION = 'browse';

    /**
     * browse
     * MINIMUM_API_VERSION=6.0.0
     *
     * Return children of a parent object in a folder traversal/browse style
     * If you don't send any parameters you'll get a catalog list (the 'root' path)
     * Catalog ID is required on 'artist', 'album', 'podcast' so you can filter the browse correctly
     *
     * filter  = (string) object_id //optional
     * type    = (string) 'root', 'catalog', 'artist', 'album', 'podcast' // optional
     * catalog = (integer) catalog ID you are browsing // optional
     * add     = $browse->set_api_filter(date) //optional
     * update  = $browse->set_api_filter(date) //optional
     * offset  = (integer) //optional
     * limit   = (integer) //optional
     * cond    = (string) Apply additional filters to the browse using ';' separated comma string pairs (e.g. 'filter1,value1;filter2,value2') //optional
     * sort    = (string) sort name or comma separated key pair. Order default 'ASC' (e.g. 'name,ASC' and 'name' are the same) //optional
     */
    public static function browse(array $input, User $user): bool
    {
        $catalog_id  = $input['catalog'] ?? null;
        $object_id   = $input['filter'] ?? null;
        $object_type = $input['type'] ?? 'root';
        if (!AmpConfig::get('podcast') && $object_type == 'podcast') {
            Api::error('Enable: podcast', ErrorCodeEnum::ACCESS_DENIED, self::ACTION, 'system', $input['api_format']);

            return false;
        }
        // confirm the correct data
        if (!in_array(strtolower($object_type), array('root', 'catalog', 'artist', 'album', 'podcast'))) {
            Api::error(sprintf('Bad Request: %s', $object_type), ErrorCodeEnum::BAD_REQUEST, self::ACTION, 'type', $input['api_format']);

            return false;
        }

        $browse = Api::getBrowse();
        if ($object_type === 'root') {
            // catalog root
            $output_type  = 'catalog';
            $child_type   = $output_type;
            $gather_types = array('music');
            if (AmpConfig::get('podcast')) {
                $gather_types[] = 'podcast';
            }
            if (AmpConfig::get('video')) {
                $gather_types = array_merge($gather_types, array('clip', 'tvshow', 'movie', 'personal_video'));
            }

            $browse->set_type($output_type);
            $browse->set_filter('gather_types', $gather_types);
            $browse->set_filter('user', $user->getId());
        } elseif ($object_type === 'catalog') {
            // artist/podcasts/videos
            if (!Api::check_parameter($input, array('filter'), self::ACTION)) {
                return false;
            }
            $catalog = Catalog::create_from_id($object_id);
            if ($catalog === null) {
                /* HINT: Requested object string/id/type ("album", "myusername", "some song title", 1298376) */
                Api::error(sprintf('Not Found: %s', $object_id), ErrorCodeEnum::NOT_FOUND, self::ACTION, 'filter', $input['api_format']);

                return false;
            }

            switch ((string)$catalog->gather_types) {
                case 'clip':
                case 'tvshow':
                case 'movie':
                case 'personal_video':
                    $output_type = 'video';
                    $gather_type = 'video';
                    $browse->set_type('video');
                    break;
                case 'music':
                    $output_type = 'artist';
                    $gather_type = 'music';
                    $browse->set_type('album_artist');
                    break;
                case 'podcast':
                    $output_type = 'podcast';
                    $gather_type = 'podcast';
                    $browse->set_type('podcast');
                    break;
                default:
                    /* HINT: Requested object string/id/type ("album", "myusername", "some song title", 1298376) */
                    Api::error(sprintf('Bad Request: %s', $catalog_id), ErrorCodeEnum::BAD_REQUEST, self::ACTION, 'catalog', $input['api_format']);

                    return false;
            }
            $child_type = $output_type;

            $browse->set_sort_order(html_entity_decode((string)($input['sort'] ?? '')), ['name','ASC']);

            $browse->set_filter('gather_type', $gather_type);
            $browse->set_filter('catalog', $catalog->id);
        } else {
            if (!Api::check_parameter($input, array('filter', 'catalog'), self::ACTION)) {
                return false;
            }
            $catalog = Catalog::create_from_id($catalog_id);
            if ($catalog === null) {
                /* HINT: Requested object string/id/type ("album", "myusername", "some song title", 1298376) */
                Api::error(sprintf('Not Found: %s', $catalog_id), ErrorCodeEnum::NOT_FOUND, self::ACTION, 'catalog', $input['api_format']);

                return false;
            }
            $className = ObjectTypeToClassNameMapper::map($object_type);
            if ($className === $object_type || !$object_id) {
                /* HINT: Requested object string/id/type ("album", "myusername", "some song title", 1298376) */
                Api::error(sprintf('Bad Request: %s', $object_type), ErrorCodeEnum::BAD_REQUEST, self::ACTION, 'type', $input['api_format']);

                return false;
            }

            /** @var Artist|Album|Podcast $item */
            $item = new $className($object_id);
            if ($item->isNew()) {
                /* HINT: Requested object string/id/type ("album", "myusername", "some song title", 1298376) */
                Api::error(sprintf('Not Found: %s', $object_id), ErrorCodeEnum::NOT_FOUND, self::ACTION, 'filter', $input['api_format']);

                return false;
            }

            // for sub objects you want to browse their children
            switch ($object_type) {
                case 'artist':
                    /** @var Artist $item */
                    $output_type = 'album';
                    $filter_type = 'album_artist';
                    $browse->set_type('album');
                    $original_year = AmpConfig::get('use_original_year') ? "original_year" : "year";
                    $sort_type     = AmpConfig::get('album_sort');
                    switch ($sort_type) {
                        case 'name_asc':
                            $sort  = 'name';
                            $order = 'ASC';
                            break;
                        case 'name_desc':
                            $sort  = 'name';
                            $order = 'DESC';
                            break;
                        case 'year_asc':
                            $sort  = $original_year;
                            $order = 'ASC';
                            break;
                        case 'year_desc':
                            $sort  = $original_year;
                            $order = 'DESC';
                            break;
                        default:
                            $sort  = 'name_' . $original_year;
                            $order = 'ASC';
                    }
                    break;
                case 'album':
                    /** @var Album $item */
                    $output_type = 'song';
                    $filter_type = 'album';
                    $browse->set_type('song');
                    $sort  = 'album';
                    $order = 'ASC';
                    break;
                case 'podcast':
                    /** @var Podcast $item */
                    $output_type = 'podcast_episode';
                    $filter_type = 'podcast';
                    $browse->set_type('podcast_episode');
                    $sort  = 'podcast';
                    $order = 'ASC';
                    break;
                default:
                    $output_type = $object_type;
                    $filter_type = '';
                    $sort        = 'name';
                    $order       = 'ASC';
            }
            $child_type = $output_type;

            $browse->set_sort_order(html_entity_decode((string)($input['sort'] ?? '')), [$sort,$order]);

            if (!empty($filter_type)) {
                $browse->set_filter($filter_type, $item->getId());
            }
            $browse->set_filter('catalog', $catalog->id);
        }

        $browse->set_api_filter('add', $input['add'] ?? '');
        $browse->set_api_filter('update', $input['update'] ?? '');

        $browse->set_conditions(html_entity_decode((string)($input['cond'] ?? '')));

        $objects = $browse->get_objects();
        if (empty($objects)) {
            Api::empty('browse', $input['api_format']);

            return false;
        }

        $sort    = $browse->get_sort();
        $results = Catalog::get_name_array($objects, $output_type, $sort['name'] ?? 'name', $sort['order'] ?? 'ASC');
        ob_end_clean();
        switch ($input['api_format']) {
            case 'json':
                Json_Data::set_offset((int)($input['offset'] ?? 0));
                Json_Data::set_limit($input['limit'] ?? 0);
                echo Json_Data::browses($results, $object_id, $object_type, $child_type, $catalog_id);
                break;
            default:
                Xml_Data::set_offset((int)($input['offset'] ?? 0));
                Xml_Data::set_limit($input['limit'] ?? 0);
                echo Xml_Data::browses($results, $object_id, $object_type, $child_type, $catalog_id);
        }

        return true;
    }
}
