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

/**
 * Class CatalogFolderMethod
 * @package Lib\ApiMethods
 */
final class CatalogFolderMethod
{
    public const ACTION = 'catalog_folder';

    /**
     * catalog_folder
     * MINIMUM_API_VERSION=6.0.0
     *
     * Perform actions on local catalog folders.
     * Single folder versions of catalog add, clean and verify.
     * Make sure you remember to urlencode those folder names!
     *
     * folder  = (string) urlencode(FULL path to local folder)
     * task    = (string) 'add', 'clean', 'verify', 'remove' (can be comma separated)
     * catalog = (integer) $catalog_id
     */
    public static function catalog_folder(array $input, User $user): bool
    {
        if (!Api::check_access(AccessTypeEnum::INTERFACE, AccessLevelEnum::CONTENT_MANAGER, $user->id, self::ACTION, $input['api_format'])) {
            return false;
        }
        if (!Api::check_parameter($input, array('catalog', 'folder', 'task'), self::ACTION)) {
            return false;
        }
        $folder = html_entity_decode($input['folder']);
        $task   = explode(',', (string)$input['task']);
        if (!is_array($task)) {
            $task = array();
        }

        // confirm that a valid task is going to happen
        if (!AmpConfig::get('delete_from_disk') && in_array('remove', $task)) {
            Api::error('Enable: delete_from_disk', ErrorCodeEnum::ACCESS_DENIED, self::ACTION, 'system', $input['api_format']);

            return false;
        }
        if (!file_exists($folder) && !in_array('clean', $task)) {
            /* HINT: Requested object string/id/type ("album", "myusername", "some song title", 1298376) */
            Api::error(sprintf('Not Found: %s', $folder), ErrorCodeEnum::NOT_FOUND, self::ACTION, 'folder', $input['api_format']);

            return false;
        }
        $output_task = '';
        foreach ($task as $item) {
            if (!in_array($item, array('add', 'clean', 'verify', 'remove'))) {
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
                $type      = 'podcast_episode';
                $file_ids  = Catalog::get_ids_from_folder($folder, $type);
                $className = Podcast_Episode::class;
                break;
            case 'clip':
            case 'tvshow':
            case 'movie':
            case 'personal_video':
                $type      = 'video';
                $file_ids  = Catalog::get_ids_from_folder($folder, $type);
                $className = Video::class;
                break;
            case 'music':
            default:
                $type      = 'song';
                $file_ids  = Catalog::get_ids_from_folder($folder, $type);
                $className = Song::class;
                break;
        }
        $changed = 0;
        if ($catalog->catalog_type == 'local') {
            if (in_array('add', $task)) {
                /** @var Catalog_local $catalog */
                if ($catalog->add_files($folder, array())) {
                    $changed++;
                }
            }
            foreach ($file_ids as $file_id) {
                /** @var Song|Podcast_Episode|Video $className */
                $media = new $className($file_id);
                foreach ($task as $item) {
                    if (defined('SSE_OUTPUT')) {
                        unset($SSE_OUTPUT);
                    }
                    switch ($item) {
                        case 'clean':
                            if ($media->file) {
                                /** @var Catalog_local $catalog */
                                if ($catalog->clean_file($media->file, $type)) {
                                    $changed++;
                                }
                            }
                            break;
                        case 'verify':
                            if ($media->isNew() === false) {
                                Catalog::update_media_from_tags($media, array($type));
                            }
                            break;
                        case 'remove':
                            if ($media->id && $media->remove()) {
                                $changed++;
                            }
                            break;
                    }
                }
            }
            if ($changed > 0) {
                // update the counts too
                $catalog_media_type = $catalog->gather_types;
                if ($catalog_media_type == 'music') {
                    Album::update_table_counts();
                    Artist::update_table_counts();
                }
                // clean up after the action
                Catalog::update_catalog_map($catalog_media_type);
                Catalog::garbage_collect_mapping();
                Catalog::garbage_collect_filters();
            }
            Api::message('successfully started: ' . $output_task . ' for ' . $folder, $input['api_format']);
        } else {
            Api::error('Not Found', ErrorCodeEnum::NOT_FOUND, self::ACTION, 'catalog', $input['api_format']);
        }

        return true;
    }
}
