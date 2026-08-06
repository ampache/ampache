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

namespace Ampache\Module\Api\Method\Api4;

use Ampache\Config\AmpConfig;
use Ampache\Module\Api\Api4;
use Ampache\Module\Api\Authentication\GatekeeperInterface;
use Ampache\Module\Api\Method\MethodInterface;
use Ampache\Module\Api\Output\ApiOutputInterface;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Module\Catalog\Catalog;
use Ampache\Module\Catalog\Catalog_local;
use Ampache\Repository\Model\Album;
use Ampache\Repository\Model\Artist;
use Ampache\Repository\Model\Podcast_Episode;
use Ampache\Repository\Model\Song;
use Ampache\Repository\Model\User;
use Ampache\Repository\Model\Video;
use Psr\Http\Message\ResponseInterface;

final class CatalogFile4Method implements MethodInterface
{
    public const string ACTION = 'catalog_file';

    /**
     * catalog_file
     * MINIMUM_API_VERSION=420000
     *
     * Perform actions on local catalog files.
     * Single file versions of catalog add, clean and verify.
     * Make sure you remember to urlencode those file names!
     *
     * file = (string) urlencode(FULL path to local file)
     * task = (string) 'add'|'clean'|'verify'|'remove'
     * catalog = (integer) $catalog_id
     *
     * @param array{
     *     file: string,
     *     task: string,
     *     catalog: int,
     *     api_format: string,
     *     auth: string,
     * } $input
     * @param 4 $apiVersion
     */
    public function handle(
        GatekeeperInterface $gatekeeper,
        ResponseInterface $response,
        ApiOutputInterface $output,
        array $input,
        User $user,
        int $apiVersion,
    ): ResponseInterface {
        if (!Api4::check_parameter($input, ['catalog', 'file', 'task'], self::ACTION)) {
            return $response;
        }
        if (!Api4::check_access(AccessTypeEnum::INTERFACE, AccessLevelEnum::CONTENT_MANAGER, $user->id, 'catalog_file', $input['api_format'])) {
            return $response;
        }
        $task = (string) $input['task'];
        if (!AmpConfig::get('delete_from_disk') && $task == 'remove') {
            Api4::message('error', 'Access Denied: delete from disk is not enabled.', '400', $input['api_format']);

            return $response;
        }
        $file = html_entity_decode($input['file']);
        // confirm the correct data
        if (!in_array($task, ['add', 'clean', 'verify', 'remove'])) {
            Api4::message('error', 'Incorrect file task' . ' ' . $task, '401', $input['api_format']);

            return $response;
        }
        if (!file_exists($file) && $task !== 'clean') {
            Api4::message('error', 'File not found' . ' ' . $file, '404', $input['api_format']);

            return $response;
        }
        $catalog_id = (int) $input['catalog'];
        $catalog    = Catalog::create_from_id($catalog_id);
        if ($catalog === null) {
            Api4::message('error', 'Catalog not found' . ' ' . $catalog_id, '404', $input['api_format']);

            return $response;
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
            switch ($task) {
                case 'clean':
                    /** @var Catalog_local $catalog */
                    $catalog->clean_file($file, $type);
                    break;
                case 'verify':
                    Catalog::update_media_from_tags($media, [$type]);
                    break;
                case 'add':
                    /** @var Catalog_local $catalog */
                    $catalog->add_file($file, []);
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
            Api4::message('error', 'The requested catalog was not found', '404', $input['api_format']);
        }

        return $response;
    }
}
