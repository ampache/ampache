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

use Ampache\Config\AmpConfig;
use Ampache\Module\Api\Exception\ErrorCodeEnum;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Module\Catalog\Catalog_local;
use Ampache\Repository\Model\Album;
use Ampache\Repository\Model\Artist;
use Ampache\Repository\Model\Catalog;
use Ampache\Repository\Model\Podcast_Episode;
use Ampache\Repository\Model\Song;
use Ampache\Repository\Model\User;
use Ampache\Repository\Model\Video;
use Ampache\Module\Api\Api;
use Exception;

/**
 * Class CatalogFileMethod
 * @package Lib\ApiMethods
 */
final class CatalogFileMethod
{
    public const ACTION = 'catalog_file';

    /**
     * catalog_file
     * MINIMUM_API_VERSION=420000
     *
     * Perform actions on local catalog files.
     * Single file versions of catalog add, clean and verify.
     * Make sure you remember to urlencode those file names!
     *
     * file    = (string) urlencode(FULL path to local file)
     * task    = (string) 'add', 'clean', 'verify', 'remove' (can be comma separated)
     * catalog = (integer) $catalog_id
     *
     * @param array{
     *     file: string,
     *     task: string,
     *     catalog: int,
     *     api_format: string,
     *     auth: string,
     * } $input
     * @param User $user
     * @return bool
     */
    public static function catalog_file(array $input, User $user): bool
    {
        if (!Api::check_access(AccessTypeEnum::INTERFACE, AccessLevelEnum::CONTENT_MANAGER, $user->id, self::ACTION, $input['api_format'])) {
            return false;
        }
        if (!Api::check_parameter($input, ['catalog', 'file', 'task'], self::ACTION)) {
            return false;
        }
        $file = html_entity_decode($input['file']);
        $task = explode(',', html_entity_decode((string)($input['task'])));
        if (!is_array($task)) {
            $task = [];
        }

        // confirm that a valid task is going to happen
        if (!AmpConfig::get('delete_from_disk') && in_array('remove', $task)) {
            Api::error('Enable: delete_from_disk', ErrorCodeEnum::ACCESS_DENIED, self::ACTION, 'system', $input['api_format']);

            return false;
        }
        if (!file_exists($file) && !in_array('clean', $task)) {
            /* HINT: Requested object string/id/type ("album", "myusername", "some song title", 1298376) */
            Api::error(sprintf('Not Found: %s', $file), ErrorCodeEnum::NOT_FOUND, self::ACTION, 'file', $input['api_format']);

            return false;
        }
        $output_task = '';
        foreach ($task as $item) {
            if (!in_array($item, ['add', 'clean', 'verify', 'remove'])) {
                /* HINT: Requested object string/id/type ("album", "myusername", "some song title", 1298376) */
                Api::error(sprintf('Bad Request: %s', $item), ErrorCodeEnum::BAD_REQUEST, self::ACTION, 'task', $input['api_format']);

                return false;
            }
            $output_task .= $item . ', ';
        }
        $output_task = rtrim($output_task, ', ');
        $catalog_id  = (int) $input['catalog'];
        $catalog     = Catalog::create_from_id($catalog_id);
        if ($catalog === null) {
            /* HINT: Requested object string/id/type ("album", "myusername", "some song title", 1298376) */
            Api::error(sprintf('Not Found: %s', $catalog_id), ErrorCodeEnum::NOT_FOUND, self::ACTION, 'catalog', $input['api_format']);

            return false;
        }
        switch ($catalog->gather_types) {
            case 'podcast':
                $type  = 'podcast_episode';
                $media = new Podcast_Episode(Catalog::get_id_from_file($file, $type));
                break;
            case 'video':
                $type  = 'video';
                $media = new Video(Catalog::get_id_from_file($file, $type));
                break;
            case 'music':
            default:
                $type  = 'song';
                $media = new Song(Catalog::get_id_from_file($file, $type));
                break;
        }

        if ($catalog->catalog_type == 'local') {
            foreach ($task as $item) {
                if (defined('SSE_OUTPUT')) {
                    unset($SSE_OUTPUT);
                }
                switch ($item) {
                    case 'clean':
                        if ($media->isNew() === false) {
                            /** @var Catalog_local $catalog */
                            $catalog->clean_file($file, $type);
                        }
                        break;
                    case 'verify':
                        if ($media->isNew() === false) {
                            Catalog::update_media_from_tags($media, [$type]);
                        }
                        break;
                    case 'add':
                        if ($media->isNew()) {
                            /** @var Catalog_local $catalog */
                            try {
                                $catalog->add_file($file, []);
                            } catch (Exception) {
                                /* HINT: Requested object string/id/type ("album", "myusername", "some song title", 1298376) */
                                Api::error(sprintf('Bad Request: %s', $file), ErrorCodeEnum::GENERIC_ERROR, self::ACTION, 'file', $input['api_format']);

                                return false;
                            }
                        }
                        break;
                    case 'remove':
                        if ($media->isNew() === false) {
                            $media->remove();
                        }
                        break;
                }
            }
            // update the counts too
            if ($media instanceof Song) {
                Album::update_album_count($media->album);
                Artist::update_table_counts();
            }
            Api::message('successfully started: ' . $output_task . ' for ' . $file, $input['api_format']);
        } else {
            Api::error('Not Found', ErrorCodeEnum::NOT_FOUND, self::ACTION, 'catalog', $input['api_format']);

            return false;
        }

        return true;
    }
}
