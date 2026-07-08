<?php

declare(strict_types=1);

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

namespace Ampache\Module\Api\Method\Api8;

use Ampache\Module\Api\Api;
use Ampache\Module\Api\Exception\ErrorCodeEnum;
use Ampache\Module\Api\Json8_Data;
use Ampache\Module\Api\Xml8_Data;
use Ampache\Repository\Model\Album;
use Ampache\Repository\Model\Artist;
use Ampache\Repository\Model\Catalog;
use Ampache\Repository\Model\Podcast;
use Ampache\Repository\Model\User;

/**
 * Class List8Method
 * @package Lib\Api8Methods
 */
final class Folders8Method
{
    public const ACTION = 'folders';

    /**
     * folders
     * MINIMUM_API_VERSION=8.0.0
     *
     * Return children of a parent object in a folder traversal style
     *
     * filter = (string) path name filter (Default: '/') //optional
     * exact = (integer) 0,1, if true filter is exact rather then fuzzy (Default: 1) //optional
     * add = $browse->set_api_filter(date) //optional
     * update = $browse->set_api_filter(date) //optional
     * offset = (integer) //optional
     * limit = (integer) //optional
     * cond = (string) Apply additional filters to the browse using ';' separated comma string pairs (e.g. 'filter1,value1;filter2,value2') //optional
     * sort = (string) sort name or comma separated key pair. Order default 'ASC' (e.g. 'name,ASC' and 'name' are the same) //optional
     *
     * @param array{
     *     filter?: string,
     *     exact?: int,
     *     add?: string,
     *     update?: string,
     *     offset?: int,
     *     limit?: int,
     *     cond?: string,
     *     sort?: string,
     *     api_format: string,
     *     auth: string,
     * } $input
     */
    public static function folders(array $input, User $user): bool
    {
        $browse    = Api::getBrowse($user);
        $object    = (isset($input['filter'])) ? $input['filter'] : '/';
        $parentId  = null;
        $path      = '/';
        $item_type = 'catalog';
        if ($object === '/' || (int) $object === -1) {
            $parent = [
                'id' => '-1',
                'title' => T_('Home'),
                'parent' => $parentId,
                'path' => $path,
                'catalog' => null,
                'item_type' => $item_type,
            ];
        } else {
            preg_match('~(?:^|/)([a-z_]+)-([0-9]+)/?$~', html_entity_decode((string) $object), $matches);
            $object_type = $matches[1] ?? null;
            if (!in_array($object_type, ['catalog', 'artist', 'album', 'podcast', 'podcast_episode', 'song', 'video'], true)) {
                /* HINT: Requested object string/id/type ("album", "myusername", "some song title", 1298376) */
                Api::error(ErrorCodeEnum::BAD_REQUEST, sprintf('Bad Request: %s', $object), self::ACTION, 'type', $input['api_format']);

                return false;
            }

            $path_catalog_id = null;
            if (preg_match('/(?:^|\/)catalog-([0-9]+)(?:\/|$)/', html_entity_decode((string) $object), $catalogMatches)) {
                $path_catalog_id = (int)$catalogMatches[1];
            }


            $object_id = (int) ($matches[2] ?? 0);
            $catalogId = null;
            switch ($object_type) {
                case 'catalog':
                    $libitem   = Catalog::create_from_id($object_id);
                    $parentId  = -1;
                    $path      = '/catalog-' . $object_id;
                    switch ($libitem?->gather_types) {
                        case 'music':
                            $item_type = 'artist';
                            break;
                        case 'podcast':
                            $item_type = 'podcast';
                            break;
                        case 'video':
                            $item_type = 'video';
                            break;
                        default:
                            Api::empty('folder', $input['api_format']);

                            return false;
                    }
                    $browse->set_type($item_type);
                    break;
                case 'artist':
                    $libitem   = new Artist($object_id);
                    $parentId  = $path_catalog_id ?? $libitem->getCatalogId() ?: null;
                    $catalogId = $path_catalog_id ?? $libitem->getCatalogId();
                    $path      = '/catalog-' . $catalogId . '/artist-' . $object_id;
                    $item_type = 'album';
                    $browse->set_type($item_type);
                    $browse->set_filter('album_artist', $object_id);
                    break;
                case 'album':
                    $libitem   = new Album($object_id);
                    $parentId  = $libitem->getAlbumArtist();
                    $catalogId = $libitem->getCatalogId();
                    $path      = '/catalog-' . $catalogId . '/artist-' . $parentId . '/album-' . $object_id;
                    $item_type = 'song';
                    $browse->set_type($item_type);
                    $browse->set_filter('album', $object_id);
                    break;
                case 'podcast':
                    $libitem   = new Podcast($object_id);
                    $parentId  = $libitem->getCatalogId();
                    $catalogId = $libitem->getCatalogId();
                    $path      = '/catalog-' . $catalogId . '/podcast-' . $object_id;
                    $item_type = 'podcast_episode';
                    $browse->set_filter('podcast', $object_id);
                    break;
                case 'podcast_episode':
                case 'song':
                case 'video':
                default:
                    Api::empty('folder', $input['api_format']);

                    return false;
            }

            if ($libitem === null || $object_id === 0 || $parentId === 0) {
                /* HINT: Requested object string/id/type ("album", "myusername", "some song title", 1298376) */
                Api::error(ErrorCodeEnum::NOT_FOUND, sprintf('Not Found: %s', $object), self::ACTION, 'filter', $input['api_format']);

                return false;
            }

            $parent = [
                'id' => (string)$object_id,
                'title' => $libitem->get_fullname(),
                'parent' => $parentId,
                'path' => $path,
                'catalog' => $catalogId,
                'item_type' => $item_type,
            ];
        }

        $browse->set_type($item_type);
        if (isset($catalogId) && $catalogId > 0) {
            $browse->set_filter('catalog', $catalogId);
        }
        $browse->set_api_filter('add', $input['add'] ?? '');
        $browse->set_api_filter('update', $input['update'] ?? '');

        $browse->set_conditions(html_entity_decode((string) ($input['cond'] ?? '')));

        $results = $browse->get_objects();
        if (empty($results)) {
            Api::empty('folder', $input['api_format']);

            return false;
        }

        ob_end_clean();
        switch ($input['api_format']) {
            case 'json':
                Json8_Data::set_offset((int) ($input['offset'] ?? 0));
                Json8_Data::set_limit($input['limit'] ?? 0);
                Json8_Data::set_count(count($results));
                echo Json8_Data::folders($results, $item_type, $parent, $user, $input['auth']);
                break;
            default:
                Xml8_Data::set_offset((int) ($input['offset'] ?? 0));
                Xml8_Data::set_limit($input['limit'] ?? 0);
                Xml8_Data::set_count(count($results));
                echo Xml8_Data::folders($results, $item_type, $parent, $user, $input['auth']);
        }

        return true;
    }
}
