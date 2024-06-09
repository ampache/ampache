<?php

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

namespace Ampache\Repository;

use Ampache\Repository\Model\PrivateMessageInterface;
use Ampache\Repository\Model\User;

interface PrivateMessageRepositoryInterface
{
    /**
     * Get the user received private messages.
     */
    public function getUnreadCount(
        User $user
    ): int;

    /**
     * Get the subsonic chat messages.
     *
     * @return list<int>
     */
    public function getChatMessages(int $since = 0): array;

    /**
     * Clear old messages from the subsonic chat message list.
     */
    public function cleanChatMessages(int $days = 30): void;

    public function setIsRead(PrivateMessageInterface $message, int $state): void;

    public function delete(PrivateMessageInterface $message): void;

    /**
     * Creates a private message and returns the id of the newly created object
     */
    public function create(
        ?User $recipient,
        User $sender,
        string $subject,
        string $message
    ): int;

    public function findById(
        int $privateMessageId
    ): ?PrivateMessageInterface;
}
