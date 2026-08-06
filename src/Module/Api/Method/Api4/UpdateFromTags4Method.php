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

use Ampache\Module\Api\Api4;
use Ampache\Module\Api\Authentication\GatekeeperInterface;
use Ampache\Module\Api\Method\MethodInterface;
use Ampache\Module\Api\Output\ApiOutputInterface;
use Ampache\Module\Catalog\Catalog;
use Ampache\Module\Util\ObjectTypeToClassNameMapper;
use Ampache\Repository\Model\Album;
use Ampache\Repository\Model\Artist;
use Ampache\Repository\Model\Song;
use Ampache\Repository\Model\User;
use Psr\Http\Message\ResponseInterface;

final class UpdateFromTags4Method implements MethodInterface
{
    public const string ACTION = 'update_from_tags';

    /**
     * update_from_tags
     * MINIMUM_API_VERSION=400001
     *
     * updates a single album, artist, song from the tag data
     *
     * type = (string) 'artist'|'album'|'song'
     * id = (integer) $artist_id, $album_id, $song_id
     *
     * @param array{
     *     id: string,
     *     type: string,
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
        if (!Api4::check_parameter($input, ['type', 'id'], self::ACTION)) {
            return $response;
        }
        unset($user);
        $type      = (string) $input['type'];
        $object_id = (int) $input['id'];

        // confirm the correct data
        if (!in_array(strtolower($type), ['artist', 'album', 'song'])) {
            Api4::message('error', 'Incorrect object type' . ' ' . $type, '401', $input['api_format']);

            return $response;
        }
        $className = ObjectTypeToClassNameMapper::map($type);
        /** @var Artist|Album|Song $item */
        $item = new $className($object_id);
        if ($item->isNew()) {
            Api4::message('error', 'The requested item was not found', '404', $input['api_format']);

            return $response;
        }
        // update your object
        Catalog::update_single_item($type, $object_id, true);

        Api4::message('success', 'Updated tags for: ' . $object_id . ' (' . $type . ')', null, $input['api_format']);

        return $response;
    }
}
