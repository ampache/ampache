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
use Ampache\Repository\Model\Album;
use Ampache\Repository\Model\Catalog;
use Ampache\Repository\Model\User;
use Ampache\Module\Api\Api;

/**
 * Class CatalogActionMethod
 * @package Lib\ApiMethods
 */
final class CatalogActionMethod
{
    public const ACTION = 'catalog_action';

    /**
     * catalog_action
     * MINIMUM_API_VERSION=400001
     * CHANGED_IN_API_VERSION=420000
     *
     * Kick off a catalog update or clean for the selected catalog
     * Added 'verify_catalog', 'gather_art'
     *
     * task    = (string) 'add_to_catalog', 'clean_catalog', 'verify_catalog', 'gather_art', 'garbage_collect'
     * catalog = (integer) $catalog_id
     *
     * @param array{
     *     task: string,
     *     catalog: int,
     *     api_format: string,
     *     auth: string,
     * } $input
     * @param User $user
     * @return bool
     */
    public static function catalog_action(array $input, User $user): bool
    {
        if (!Api::check_parameter($input, ['catalog', 'task'], self::ACTION)) {
            return false;
        }
        if (!Api::check_access(AccessTypeEnum::INTERFACE, AccessLevelEnum::MANAGER, $user->id, self::ACTION, $input['api_format'])) {
            return false;
        }
        $task = (string) $input['task'];
        // confirm the correct data
        if (!in_array($task, ['add_to_catalog', 'clean_catalog', 'verify_catalog', 'gather_art', 'garbage_collect'])) {
            /* HINT: Requested object string/id/type ("album", "myusername", "some song title", 1298376) */
            Api::error(sprintf('Bad Request: %s', $task), ErrorCodeEnum::BAD_REQUEST, self::ACTION, 'task', $input['api_format']);

            return false;
        }

        $catalog = Catalog::create_from_id((int) $input['catalog']);
        if ($catalog !== null) {
            if (defined('SSE_OUTPUT')) {
                unset($SSE_OUTPUT);
            }
            switch ($task) {
                case 'clean_catalog':
                    $catalog->clean_catalog_proc();
                    break;
                case 'verify_catalog':
                    $catalog->verify_catalog_proc();
                    break;
                case 'gather_art':
                    $catalog->gather_art();
                    break;
                case 'add_to_catalog':
                    $options = [
                        'gather_art' => true,
                        'parse_playlist' => false
                    ];
                    $catalog->add_to_catalog($options);
                    break;
                case 'garbage_collect':
                    $catalog_media_type = $catalog->gather_types;
                    if ($catalog_media_type == 'music') {
                        Catalog::clean_empty_albums();
                        Album::update_album_artist();
                    }
                    Catalog::update_catalog_map($catalog_media_type);
                    Catalog::update_counts();
                    break;
            }

            Api::message('successfully started: ' . $task, $input['api_format']);
        } else {
            Api::error('Not Found', ErrorCodeEnum::NOT_FOUND, self::ACTION, 'catalog', $input['api_format']);

            return false;
        }

        return true;
    }
}
