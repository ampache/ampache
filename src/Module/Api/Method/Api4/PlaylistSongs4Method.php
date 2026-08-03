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
use Ampache\Module\Database\Query\Search;
use Ampache\Repository\Model\Playlist;
use Ampache\Repository\Model\User;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;

/**
 * Returns the songs of a playlist or a smartlist.
 */
final class PlaylistSongs4Method implements MethodInterface
{
    public const string ACTION = 'playlist_songs';

    public function __construct(
        private StreamFactoryInterface $streamFactory,
    ) {}

    /**
     * @param array<string, mixed> $input
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
        if (!Api4::check_parameter($input, ['filter'], self::ACTION)) {
            return $response;
        }

        $uid = scrub_in((string) ($input['filter'] ?? ''));
        debug_event(self::class, 'User ' . $user->id . ' loading playlist: ' . $uid, 5);

        $playlist = (str_replace('smart_', '', $uid) === $uid)
            ? new Playlist((int) $uid)
            : new Search((int) str_replace('smart_', '', $uid), 'song', $user);

        if ($playlist->isNew()) {
            Api4::message('error', 'Library item not found', '404', $input['api_format']);

            return $response;
        }

        if (
            $playlist->type !== 'public'
            && !$playlist->has_collaborate($user)
        ) {
            Api4::message('error', 'Access denied to this playlist', '401', $input['api_format']);

            return $response;
        }

        $results = [];
        foreach ($playlist->get_items() as $object) {
            if ($object['object_type']->value == 'song') {
                $results[] = $object['object_id'];
            }
        }

        $output->setOffset($apiVersion, $input['offset'] ?? 0);
        $output->setLimit($apiVersion, $input['limit'] ?? 0);

        return $response->withBody(
            $this->streamFactory->createStream(
                $output->songs($apiVersion, $results, $user, $input['auth'])
            )
        );
    }
}
