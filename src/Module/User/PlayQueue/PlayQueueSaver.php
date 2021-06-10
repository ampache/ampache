<?php
/*
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright 2001 - 2020 Ampache.org
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
 */

declare(strict_types=1);

namespace Ampache\Module\User\PlayQueue;

use Ampache\Repository\Model\User;
use Ampache\Repository\UserPlaylistRepositoryInterface;

final class PlayQueueSaver implements PlayQueueSaverInterface
{
    private UserPlaylistRepositoryInterface $userPlaylistRepository;

    public function __construct(
        UserPlaylistRepositoryInterface $userPlaylistRepository
    ) {
        $this->userPlaylistRepository = $userPlaylistRepository;
    }

    /**
     * This function resets the User_Playlist while optionally setting the update clietn and time for that user
     *
     * @param array<array{
     *  object_type: string,
     *  object_id: int
     * }> $playlist
     */
    public function save(
        int $userId,
        array $playlist,
        string $current_type,
        int $current_id,
        int $current_time,
        ?int $time = null,
        ?string $client = null
    ): void {
        if ($playlist !== []) {
            // clear the old list
            $this->userPlaylistRepository->clear($userId);

            // set the new items
            $index = 1;
            foreach ($playlist as $row) {
                $this->userPlaylistRepository->addItem(
                    $userId,
                    $row['object_type'],
                    $row['object_id'],
                    $index
                );
                $index++;
            }

            $this->userPlaylistRepository->setCurrentObjectByUser(
                $userId,
                $current_type,
                $current_id,
                $current_time
            );

            // subsonic cares about queue dates so set them (and set them together)
            if ($time && $client) {
                User::set_user_data($userId, 'playqueue_date', $time);
                User::set_user_data($userId, 'playqueue_client', $client);
            }
        }
    }
}
