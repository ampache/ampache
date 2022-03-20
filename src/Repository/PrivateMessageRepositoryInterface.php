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

namespace Ampache\Repository;

use Ampache\Repository\Model\PrivateMessageInterface;
use Ampache\Repository\Exception\ItemNotFoundException;

interface PrivateMessageRepositoryInterface
{
    /**
     * Get the user received private messages.
     *
     * @param int $userId
     * @return int
     */
    public function getUnreadCount(
        int $userId
    ): int;

    /**
     * Get the subsonic chat messages.
     *
     * @return int[]
     */
    public function getChatMessages(int $since = 0): array;

    /**
     * Clear old messages from the subsonic chat message list.
     */
    public function cleanChatMessages(int $days = 30): void;

    /**
     * Sends a subsonic chat message
     */
    public function sendChatMessage(string $message, int $userId): ?int;

    public function setIsRead(int $privateMessageId, int $state): void;

    public function delete(int $privateMessageId): void;

    /**
     * Creates a private message and returns the id of the newly created object
     */
    public function create(
        int $senderUserId,
        int $recipientUserId,
        string $subject,
        string $message
    ): ?int;

    /**
     * @throws ItemNotFoundException
     */
    public function getById(
        int $privateMessageId
    ): PrivateMessageInterface;
}
