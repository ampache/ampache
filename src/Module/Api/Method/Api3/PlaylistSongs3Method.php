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

namespace Ampache\Module\Api\Method\Api3;

use Ampache\Module\Api\Authentication\GatekeeperInterface;
use Ampache\Module\Api\Method\MethodInterface;
use Ampache\Module\Api\Output\ApiOutputInterface;
use Ampache\Module\Api\Xml3_Data;
use Ampache\Repository\Model\LibraryItemEnum;
use Ampache\Repository\Model\Playlist;
use Ampache\Repository\Model\User;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;

final class PlaylistSongs3Method implements MethodInterface
{
    public const string ACTION = 'playlist_songs';

    public function __construct(
        private Xml3_Data $xml3Data,
        private StreamFactoryInterface $streamFactory,
    ) {}

    /**
     * playlist_songs
     * This returns the songs for a playlist
     *
     * @param array{
     *     filter: string,
     *     random?: int,
     *     offset?: int,
     *     limit?: int,
     *     api_format: string,
     *     auth: string,
     * } $input
     * @param 3 $apiVersion
     */
    public function handle(
        GatekeeperInterface $gatekeeper,
        ResponseInterface $response,
        ApiOutputInterface $output,
        array $input,
        User $user,
        int $apiVersion,
    ): ResponseInterface {
        $playlist = new Playlist((int) $input['filter']);
        $items    = $playlist->get_items();

        $results = [];
        foreach ($items as $object) {
            if ($object['object_type'] === LibraryItemEnum::SONG) {
                $results[] = $object['object_id'];
            }
        }

        $output->setOffset($apiVersion, $input['offset'] ?? 0);
        $output->setLimit($apiVersion, $input['limit'] ?? 0);
        ob_end_clean();

        // version 3 threads playlist_data through songs(); the shared output contract has no slot for it
        return $response->withBody(
            $this->streamFactory->createStream(
                $this->xml3Data->songs($results, $user, $input['auth'], $items)
            )
        );
    }
}
