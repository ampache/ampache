<?php

declare(strict_types=1);

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

namespace Ampache\Module\Util\Rss\Type;

use Ampache\Module\Statistics\Stats;
use Ampache\Repository\Model\Song;
use Ampache\Repository\Model\User;
use Generator;

final readonly class RecentlyPlayedFeed extends AbstractGenericRssFeed
{
    public function __construct(
        private ?User $user
    ) {
    }

    protected function getTitle(): string
    {
        return T_('Recently Played');
    }

    /**
     * @return Generator<array{
     *  title: string,
     *  link: string,
     *  description: string,
     *  comments: string,
     *  pubDate: string,
     *  image?: string
     * }>
     */
    protected function getItems(): Generator
    {
        $userId = $this->user?->getId();

        $data = Stats::get_recently_played($userId, 'stream', 'song');

        foreach ($data as $item) {
            $client = new User($item['user']);
            $song   = new Song($item['object_id']);
            $row_id = ($item['user'] > 0) ? (int) $item['user'] : -1;

            $has_allowed_recent = (bool) $item['user_recent'];
            $is_allowed_recent  = ($userId > 0 && $userId == $row_id) || $has_allowed_recent;
            if ($song->enabled && $is_allowed_recent) {

                yield [
                    'title' => sprintf(
                        '%s - %s - %s',
                        $song->get_fullname(),
                        $song->get_artist_fullname(),
                        $song->get_album_fullname()
                    ),
                    'link' => str_replace('&amp;', '&', $song->get_link()),
                    'description' => sprintf(
                        '<p>%s: %s</p><p>%s: %s</p><p>%s: %s</p><p>%s: %s</p><p>%s: %s</p>',
                        T_('User'),
                        $client->username,
                        T_('Title'),
                        $song->get_fullname(),
                        T_('Artist'),
                        $song->get_artist_fullname(),
                        T_('Album'),
                        $song->get_album_fullname(),
                        T_('Play date'),
                        get_datetime($item['date'])
                    ),
                    'comments' => (string)$client->username,
                    'pubDate' => date("r", (int)$item['date'])
                ];
            }
        }
    }
}
