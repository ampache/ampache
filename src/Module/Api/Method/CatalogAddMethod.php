<?php

/*
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

declare(strict_types=0);

namespace Ampache\Module\Api\Method;

use Ampache\Module\Catalog\Catalog_local;
use Ampache\Repository\Model\Catalog;
use Ampache\Repository\Model\User;
use Ampache\Module\Api\Api;
use Ampache\Module\Api\Json_Data;
use Ampache\Module\Api\Xml_Data;

/**
 * Class CatalogAddMethod
 * @package Lib\ApiMethods
 */
final class CatalogAddMethod
{
    public const ACTION = 'catalog_add';

    /**
     * catalog_add
     * MINIMUM_API_VERSION=6.0.0
     *
     * Create a new catalog
     *
     * name           = (string) catalog_name
     * path           = (string) URL or folder path for your catalog
     * type           = (string) catalog_type default: local ('local', 'beets', 'remote', 'subsonic', 'seafile', 'beetsremote') //optional
     * media_type     = (string) Default: 'music' ('music', 'podcast', 'clip', 'tvshow', 'movie', 'personal_video') //optional
     * file_pattern   = (string) Pattern used identify tags from the file name. Default '%T - %t' //optional
     * folder_pattern = (string) Pattern used identify tags from the folder name. Default '%a/%A' //optional
     * username       = (string) login to remote catalog ('remote', 'subsonic', 'seafile') //optional
     * password       = (string) password to remote catalog ('remote', 'subsonic', 'seafile', 'beetsremote') //optional
     */
    public static function catalog_add(array $input, User $user): bool
    {
        if (!Api::check_parameter($input, array('name', 'path'), self::ACTION)) {
            return false;
        }
        if (!Api::check_access('interface', 75, $user->id, self::ACTION, $input['api_format'])) {
            return false;
        }
        $path           = $input['path'];
        $name           = $input['name'];
        $type           = $input['type'] ?? 'local';
        $rename_pattern = $input['file_pattern'] ?? '%T - %t';
        $sort_pattern   = $input['folder_pattern'] ?? '%a/%A';
        $gather_types   = $input['media_type'] ?? 'music';
        $username       = $input['username'] ?? null;
        $password       = $input['password'] ?? null;

        // confirm the correct data
        if (!in_array(strtolower($type), array('local', 'beets', 'remote', 'subsonic', 'seafile'))) {
            /* HINT: Requested object string/id/type ("album", "myusername", "some song title", 1298376) */
            Api::error(sprintf(T_('Bad Request: %s'), $type), '4710', self::ACTION, 'type', $input['api_format']);

            return false;
        }
        $is_remote = in_array($type, array('remote', 'subsonic', 'beetsremote', 'seafile'));
        if ($is_remote) {
            if (!$username) {
                Api::error(T_('Bad Request'), '4710', self::ACTION, 'username', $input['api_format']);

                return false;
            }
            if (!$password) {
                Api::error(T_('Bad Request'), '4710', self::ACTION, 'password', $input['api_format']);

                return false;
            }
        }
        $path_ok = ($is_remote)
            ? filter_var(urldecode($path), FILTER_VALIDATE_URL)
            : Catalog_local::check_path($path);
        if (!$path_ok) {
            /* HINT: Requested object string/id/type ("album", "myusername", "some song title", 1298376) */
            Api::error(sprintf(T_('Bad Request: %s'), $path), '4710', self::ACTION, 'path', $input['api_format']);

            return false;
        }

        $object = array(
            'name' => $name,
            'path' => $path, // local, beets
            'uri' => $path, // remote, subsonic, beetsremote
            'type' => $type,
            'rename_pattern' => $rename_pattern,
            'sort_pattern' => $sort_pattern,
            'gather_media' => $gather_types,
            'username' => $username,
            'password' => $password
        );
        if ($type == 'seafile') {
            $object['library_name'  ] = $object['name'];
            $object['server_uri']     = $object['path'];
            $object['api_call_delay'] = 250;
        }

        // create it then retrieve it
        $catalog_id = Catalog::create($object);
        if ($catalog_id == 0) {
            Api::error(T_('Bad Request'), '4710', self::ACTION, 'system', $input['api_format']);

            return false;
        }
        $catalog = Catalog::create_from_id($catalog_id);
        if (!$catalog instanceof Catalog) {
            Api::empty('catalog', $input['api_format']);

            return false;
        }
        $results = array($catalog->id);

        ob_end_clean();
        switch ($input['api_format']) {
            case 'json':
                echo Json_Data::catalogs($results);
                break;
            default:
                echo Xml_Data::catalogs($results, $user);
        }

        return true;
    }
}
