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

use Ampache\Module\Api\Api;
use Ampache\Module\Api\Exception\ErrorCodeEnum;
use Ampache\Module\Api\Xml_Data;
use Ampache\Module\System\Plugin\PluginTypeEnum;
use Ampache\Repository\Model\Album;
use Ampache\Repository\Model\Artist;
use Ampache\Repository\Model\Plugin;
use Ampache\Repository\Model\Song;
use Ampache\Repository\Model\User;

/**
 * Class GetExternalMetadataMethod
 * @package Lib\ApiMethods
 */
final class GetExternalMetadataMethod
{
    public const ACTION = 'get_external_metadata';

    /**
     * get_external_metadata
     * MINIMUM_API_VERSION=6.3.0
     *
     * Return External plugin metadata searching by object id and type
     *
     * type   = (string) 'song', 'artist', 'album'
     * filter = (integer) album id, artist id or song id
     */
    public static function get_external_metadata(array $input, User $user): bool
    {
        if (!Api::check_parameter($input, ['type', 'filter'], self::ACTION)) {
            return false;
        }
        $type      = (string) $input['type'];
        $object_id = (int) $input['filter'];
        // confirm the correct data
        if (!in_array(strtolower($type), ['song', 'album', 'artist'])) {
            Api::error(sprintf('Bad Request: %s', $type), ErrorCodeEnum::BAD_REQUEST, self::ACTION, 'type', $input['api_format']);

            return false;
        }
        switch ($type) {
            case 'song':
                $libitem = new Song($object_id);
                $data = [
                    'artist'     => $libitem->get_artist_fullname(),
                    'song'       => $libitem->get_fullname(),
                    'mb_trackid' => $libitem->mbid,
                ];
                break;
            case 'album':
                $libitem = new Album($object_id);
                $data = [
                    'albumartist'     => $libitem->get_artist_fullname(),
                    'album'            => $libitem->get_fullname(true),
                    'mb_albumid_group' => $libitem->mbid_group,
                ];
                break;
            case 'artist':
                $libitem = new Artist($object_id);
                $data = [
                    'artist'      => $libitem->get_fullname(),
                    'mb_artistid' => $libitem->mbid,
                ];
        }
        if (!isset($data) || !isset($libitem) || $libitem->isNew()) {
            Api::error(sprintf('Bad Request: %s', $type), ErrorCodeEnum::BAD_REQUEST, self::ACTION, 'filter', $input['api_format']);

            return false;
        }

        $results = [
            'object_id' => $object_id,
            'object_type' => $type,
            'plugin' => [],
        ];
        $plugin_names = Plugin::get_plugins(PluginTypeEnum::METADATA_RETRIEVER);
        foreach ($plugin_names as $tag_source) {
            if ($tag_source == 'musicbrainz') {
                continue;
            }
            $plugin            = new Plugin($tag_source);
            $installed_version = Plugin::get_plugin_version($plugin->_plugin->name);
            if ($installed_version > 0) {
                if ($plugin->_plugin !== null && $plugin->load($user)) {
                    $results['plugin'][$tag_source] = $plugin->_plugin->get_metadata(
                        ['music', $type],
                        $data,
                    );
                    if ($results['plugin'][$tag_source] === []) {
                        unset($results['plugin'][$tag_source]);
                    }
                }
            }
        }
        if ($results['plugin'] === []) {
            Api::empty($type, $input['api_format']);

            return false;
        }

        ob_end_clean();
        switch ($input['api_format']) {
            case 'json':
                echo json_encode($results, JSON_PRETTY_PRINT);
                break;
            default:
                echo Xml_Data::keyed_array($results);
        }

        return true;
    }
}
