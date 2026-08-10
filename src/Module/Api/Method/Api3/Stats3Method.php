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

use Ampache\Config\AmpConfig;
use Ampache\Module\Api\Authentication\GatekeeperInterface;
use Ampache\Module\Api\Method\MethodInterface;
use Ampache\Module\Api\Output\ApiOutputInterface;
use Ampache\Module\Statistics\Rating;
use Ampache\Module\Statistics\Stats;
use Ampache\Module\Statistics\Userflag;
use Ampache\Repository\AlbumRepositoryInterface;
use Ampache\Repository\Model\User;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;

final class Stats3Method implements MethodInterface
{
    public const string ACTION = 'stats';

    public function __construct(
        private AlbumRepositoryInterface $albumRepository,
        private StreamFactoryInterface $streamFactory,
    ) {}

    /**
     * This get library stats.
     *
     * @param array{
     *     type: string,
     *     filter?: string,
     *     user_id?: int,
     *     username?: string,
     *     offset?: int,
     *     limit?: int,
     *     cond?: string,
     *     sort?: string,
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
        $type     = $input['type'];
        $offset   = $input['offset'] ?? 0;
        $limit    = $input['limit'] ?? 0;
        $username = $input['username'] ?? '';
        // override your user if you're looking at others
        if (array_key_exists('username', $input) && User::get_from_username($input['username'])) {
            $user = User::get_from_username($input['username']);
        }
        $results = [];
        if ($type == "newest") {
            $results = Stats::get_newest("album", $limit, $offset);
        } elseif ($type == "highest") {
            $results = Rating::get_highest("album", $limit, $offset);
        } elseif ($type == "frequent") {
            $results = Stats::get_top("album", $limit, 0, $offset);
        } elseif ($type == "recent") {
            if (!empty($username)) {
                if ($user->isNew()) {
                    debug_event(self::class, 'User `' . $username . '` cannot be found.', 1);
                } else {
                    $results = $user->get_recently_played('album', $limit);
                }
            } else {
                $results = Stats::get_recent('album', $limit, $offset);
            }
        } elseif ($type == "flagged") {
            $results = Userflag::get_latest('album');
        } else {
            if (!$limit) {
                $limit = (int) AmpConfig::get('popular_threshold', 10);
            }
            $results = $this->albumRepository->getRandom($user->id, $limit);
        }

        if (!empty($results)) {
            ob_end_clean();

            return $response->withBody(
                $this->streamFactory->createStream(
                    $output->albums($apiVersion, $results, [], $user, $input['auth'])
                )
            );
        }

        return $response;
    }
}
