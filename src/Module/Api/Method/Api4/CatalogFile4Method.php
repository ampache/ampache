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

namespace Ampache\Module\Api\Method\Api4;

use Ampache\Config\AmpConfig;
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
use Ampache\Module\Api\Api4;
use Ampache\Module\Song\Deletion\SongDeleterInterface;

/**
 * Class CatalogFile4Method
 */
final class CatalogFile4Method
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
     * task    = (string) 'add'|'clean'|'verify'|'remove'
     * catalog = (integer) $catalog_id
     */
    public static function catalog_file(array $input, User $user): bool
    {
        if (!Api4::check_parameter($input, array('catalog', 'file', 'task'), self::ACTION)) {
            return false;
        }
        if (!Api4::check_access(AccessTypeEnum::INTERFACE, AccessLevelEnum::CONTENT_MANAGER, $user->id, 'catalog_file', $input['api_format'])) {
            return false;
        }
        $task = (string) $input['task'];
        if (!AmpConfig::get('delete_from_disk') && $task == 'remove') {
            Api4::message('error', T_('Access Denied: delete from disk is not enabled.'), '400', $input['api_format']);

            return false;
        }
        $file = html_entity_decode($input['file']);
        // confirm the correct data
        if (!in_array($task, array('add', 'clean', 'verify', 'remove'))) {
            Api4::message('error', T_('Incorrect file task') . ' ' . $task, '401', $input['api_format']);

            return false;
        }
        if (!file_exists($file) && $task !== 'clean') {
            Api4::message('error', T_('File not found') . ' ' . $file, '404', $input['api_format']);

            return false;
        }
        $catalog_id = (int) $input['catalog'];
        $catalog    = Catalog::create_from_id($catalog_id);
        if ($catalog === null) {
            Api4::message('error', T_('Catalog not found') . ' ' . $catalog_id, '404', $input['api_format']);

            return false;
        }
        switch ($catalog->gather_types) {
            case 'podcast':
                $type  = 'podcast_episode';
                $media = new Podcast_Episode(Catalog::get_id_from_file($file, $type));
                break;
            case 'clip':
            case 'tvshow':
            case 'movie':
            case 'personal_video':
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
            if (defined('SSE_OUTPUT')) {
                unset($SSE_OUTPUT);
            }
            switch ($task) {
                case 'clean':
                    /** @var Catalog_local $catalog */
                    $catalog->clean_file($file, $type);
                    break;
                case 'verify':
                    Catalog::update_media_from_tags($media, array($type));
                    break;
                case 'add':
                    /** @var Catalog_local $catalog */
                    $catalog->add_file($file, array());
                    break;
                case 'remove':
                    $media->remove();
                    break;
            }
            // update the counts too
            if ($media instanceof Song) {
                Album::update_album_count($media->album);
                Artist::update_table_counts();
            }
            Api4::message('success', 'successfully started: ' . $task . ' for ' . $file, null, $input['api_format']);
        } else {
            Api4::message('error', T_('The requested catalog was not found'), '404', $input['api_format']);
        }

        return true;
    }

    /**
     * @deprecated
     */
    public static function getSongDeleter(): SongDeleterInterface
    {
        global $dic;

        return $dic->get(SongDeleterInterface::class);
    }
}
