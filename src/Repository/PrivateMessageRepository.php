<?php

declare(strict_types=1);

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

use Ampache\Module\Database\DatabaseConnectionInterface;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\Model\PrivateMessageInterface;
use Ampache\Repository\Model\User;

/**
 * Manages database access related to private-message
 *
 * Table: `user_pvmsg`
 */
final readonly class PrivateMessageRepository implements PrivateMessageRepositoryInterface
{
    public function __construct(
        private ModelFactoryInterface $modelFactory,
        private DatabaseConnectionInterface $connection
    ) {
    }

    /**
     * Get the user received private messages.
     */
    public function getUnreadCount(
        User $user
    ): int {
        return (int) $this->connection->fetchOne(
            'SELECT count(`id`) as `amount` FROM `user_pvmsg` WHERE `to_user` = ? AND `is_read` = \'0\'',
            [$user->getId()]
        );
    }

    /**
     * Get the subsonic chat messages.
     *
     * @return list<int>
     */
    public function getChatMessages(int $since = 0): array
    {
        $result = $this->connection->query(
            'SELECT `id` FROM `user_pvmsg` WHERE `to_user` = 0  AND `user_pvmsg`.`creation_date` > ? ORDER BY `user_pvmsg`.`creation_date` DESC',
            [$since]
        );

        $ids = [];
        while ($rowId = $result->fetchColumn()) {
            $ids[] = (int) $rowId;
        }

        return $ids;
    }

    /**
     * Clear old messages from the subsonic chat message list.
     */
    public function cleanChatMessages(int $days = 30): void
    {
        $this->connection->query(
            sprintf(
                'DELETE FROM `user_pvmsg` WHERE `to_user` = 0 AND `creation_date` <= UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL %d day))',
                $days
            )
        );
    }

    public function setIsRead(PrivateMessageInterface $message, int $state): void
    {
        $this->connection->query(
            'UPDATE `user_pvmsg` SET `is_read` = ? WHERE `id` = ?',
            [$state, $message->getId()]
        );
    }

    public function delete(PrivateMessageInterface $message): void
    {
        $this->connection->query(
            'DELETE FROM `user_pvmsg` WHERE `id` = ?',
            [$message->getId()]
        );
    }

    /**
     * Creates a private message and returns the id of the newly created object
     */
    public function create(
        ?User $recipient,
        User $sender,
        string $subject,
        string $message
    ): int {
        $toUserId = 0;

        if ($recipient !== null) {
            $toUserId = $recipient->getId();
        }

        $this->connection->query(
            'INSERT INTO `user_pvmsg` (`subject`, `message`, `from_user`, `to_user`, `creation_date`, `is_read`) VALUES (?, ?, ?, ?, UNIX_TIMESTAMP(), 0)',
            [
                $subject,
                $message,
                $sender->getId(),
                $toUserId,
            ]
        );

        return $this->connection->getLastInsertedId();
    }

    public function findById(
        int $privateMessageId
    ): ?PrivateMessageInterface {
        $item = $this->modelFactory->createPrivateMsg($privateMessageId);
        if ($item->isNew()) {
            return null;
        }

        return $item;
    }
}
