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
     * add     = Api::set_filter(date) //optional
     * update  = Api::set_filter(date) //optional
     * offset  = (integer) //optional
     * limit   = (integer) //optional
     */
    public static function browse(array $input, User $user): bool
    {
        $catalog_id  = $input['catalog'] ?? null;
        $object_id   = $input['filter'] ?? null;
        $object_type = $input['type'] ?? 'root';
        if (!AmpConfig::get('podcast') && $object_type == 'podcast') {
            Api::error(T_('Enable: podcast'), ErrorCodeEnum::ACCESS_DENIED, self::ACTION, 'system', $input['api_format']);

            return false;
        }
        // confirm the correct data
        if (!in_array(strtolower($object_type), array('root', 'catalog', 'artist', 'album', 'podcast'))) {
            Api::error(sprintf(T_('Bad Request: %s'), $object_type), ErrorCodeEnum::BAD_REQUEST, self::ACTION, 'type', $input['api_format']);

            return false;
        }

        if ($object_type === 'root') {
            // catalog root
            $objects = User::get_user_catalogs($user->id, 'music');
            if (AmpConfig::get('podcast')) {
                $objects = array_merge($objects, User::get_user_catalogs($user->id, 'podcast'));
            }
            if (AmpConfig::get('video')) {
                // 'clip', 'tvshow', 'movie', 'personal_video'
                $objects = array_merge($objects, User::get_user_catalogs($user->id, 'clip'));
                $objects = array_merge($objects, User::get_user_catalogs($user->id, 'tvshow'));
                $objects = array_merge($objects, User::get_user_catalogs($user->id, 'movie'));
                $objects = array_merge($objects, User::get_user_catalogs($user->id, 'personal_video'));
            }
            $child_type = 'catalog';
            $results    = Catalog::get_name_array($objects, 'catalog');
        } elseif ($object_type === 'catalog') {
            // artist/podcasts/videos
            if (!Api::check_parameter($input, array('filter'), self::ACTION)) {
                return false;
            }
            $catalog = Catalog::create_from_id($object_id);
            if ($catalog === null) {
                /* HINT: Requested object string/id/type ("album", "myusername", "some song title", 1298376) */
                Api::error(sprintf(T_('Not Found: %s'), $object_id), ErrorCodeEnum::NOT_FOUND, self::ACTION, 'filter', $input['api_format']);

                return false;
            }
            $browse = Api::getBrowse();
            $browse->reset_filters();

            Api::set_filter('add', $input['add'] ?? '', $browse);
            Api::set_filter('update', $input['update'] ?? '', $browse);
            switch ((string)$catalog->gather_types) {
                case 'clip':
                case 'tvshow':
                case 'movie':
                case 'personal_video':
                    $output_type = 'video';
                    $browse->set_type('video');
                    $browse->set_filter('gather_types', 'video');
                    break;
                case 'music':
                    $output_type = 'artist';
                    $browse->set_type('album_artist');
                    $browse->set_filter('gather_types', 'music');
                    break;
                case 'podcast':
                    $output_type = 'podcast';
                    $browse->set_type('podcast');
                    $browse->set_filter('gather_types', 'podcast');
                    break;
                default:
                    /* HINT: Requested object string/id/type ("album", "myusername", "some song title", 1298376) */
                    Api::error(sprintf(T_('Bad Request: %s'), $catalog_id), ErrorCodeEnum::BAD_REQUEST, self::ACTION, 'catalog', $input['api_format']);

                    return false;
            }
            $child_type = $output_type;
            $browse->set_sort('name', 'ASC');
            $browse->set_filter('catalog', $catalog->id);
            $objects = $browse->get_objects();
            if (empty($objects)) {
                Api::empty('browse', $input['api_format']);

                return false;
            }
            $results = Catalog::get_name_array($objects, $output_type);
        } else {
            if (!Api::check_parameter($input, array('filter', 'catalog'), self::ACTION)) {
                return false;
            }
            $catalog = Catalog::create_from_id($catalog_id);
            if ($catalog === null) {
                /* HINT: Requested object string/id/type ("album", "myusername", "some song title", 1298376) */
                Api::error(sprintf(T_('Not Found: %s'), $catalog_id), ErrorCodeEnum::NOT_FOUND, self::ACTION, 'catalog', $input['api_format']);

                return false;
            }
            $className = ObjectTypeToClassNameMapper::map($object_type);
            if ($className === $object_type || !$object_id) {
                /* HINT: Requested object string/id/type ("album", "myusername", "some song title", 1298376) */
                Api::error(sprintf(T_('Bad Request: %s'), $object_type), ErrorCodeEnum::BAD_REQUEST, self::ACTION, 'type', $input['api_format']);

                return false;
            }

            /** @var Artist|Album|Podcast $item */
            $item = new $className($object_id);
            if ($item->isNew()) {
                /* HINT: Requested object string/id/type ("album", "myusername", "some song title", 1298376) */
                Api::error(sprintf(T_('Not Found: %s'), $object_id), ErrorCodeEnum::NOT_FOUND, self::ACTION, 'filter', $input['api_format']);

                return false;
            }
            $browse = Api::getBrowse();
            $browse->reset_filters();

            // for sub objects you want to browse their children
            switch ($object_type) {
                case 'artist':
                    /** @var Artist $item */
                    $output_type = 'album';
                    $browse->set_type('album');
                    $browse->set_filter('artist', $item->getId());
                    break;
                case 'album':
                    /** @var Album $item */
                    $output_type = 'song';
                    $browse->set_type('song');
                    $browse->set_filter('album', $item->getId());
                    break;
                case 'podcast':
                    /** @var Podcast $item */
                    $output_type = 'podcast_episode';
                    $browse->set_type('podcast_episode');
                    $browse->set_filter('podcast', $item->getId());
                    break;
                default:
                    $output_type = $object_type;
            }
            $child_type = $output_type;
            $browse->set_sort('name', 'ASC');
            $browse->set_filter('catalog', $catalog->id);

            Api::set_filter('add', $input['add'] ?? '', $browse);
            Api::set_filter('update', $input['update'] ?? '', $browse);

            $objects = $browse->get_objects();
            if (empty($objects)) {
                Api::empty('browse', $input['api_format']);

                return false;
            }
            $results = Catalog::get_name_array($objects, $output_type);
        }

        ob_end_clean();
        switch ($input['api_format']) {
            case 'json':
                Json_Data::set_offset($input['offset'] ?? 0);
                Json_Data::set_limit($input['limit'] ?? 0);
                echo Json_Data::browses($results, $object_id, $object_type, $child_type, $catalog_id);
                break;
            default:
                Xml_Data::set_offset($input['offset'] ?? 0);
                Xml_Data::set_limit($input['limit'] ?? 0);
                echo Xml_Data::browses($results, $object_id, $object_type, $child_type, $catalog_id);
        }

        return true;
    }
}
