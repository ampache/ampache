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

namespace Ampache\Repository;

use Ampache\Config\AmpConfig;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\Model\PrivateMessageInterface;
use Ampache\Repository\Model\PrivateMsg;
use Ampache\Module\System\Dba;
use Ampache\Repository\Exception\ItemNotFoundException;

final class PrivateMessageRepository implements PrivateMessageRepositoryInterface
{
    private ModelFactoryInterface $modelFactory;

    public function __construct(
        ModelFactoryInterface $modelFactory
    ) {
        $this->modelFactory = $modelFactory;
    }

    /**
     * Get the user received private messages.
     *
     * @param int $userId
     * @return int
     */
    public function getUnreadCount(
        int $userId
    ): int {
        $sql    = "SELECT count(`id`) as amount FROM `user_pvmsg` WHERE `to_user` = ? AND `is_read` = '0'";
        $params = array($userId);

        $db_results = Dba::read($sql, $params);

        return (int) Dba::fetch_assoc($db_results)['amount'];
    }

    /**
     * Get the subsonic chat messages.
     *
     * @return int[]
     */
    public function getChatMessages(int $since = 0): array
    {
        if (!AmpConfig::get('sociable')) {
            return array();
        }

        $sql = "SELECT `id` FROM `user_pvmsg` WHERE `to_user` = 0 ";
        $sql .= " AND `user_pvmsg`.`creation_date` > " . (string)$since;
        $sql .= " ORDER BY `user_pvmsg`.`creation_date` DESC";

        $db_results = Dba::read($sql);
        $results    = array();
        while ($row = Dba::fetch_assoc($db_results)) {
            $results[] = (int) $row['id'];
        }

        return $results;
    }

    /**
     * Clear old messages from the subsonic chat message list.
     */
    public function cleanChatMessages(int $days = 30): void
    {
        $sql = "DELETE FROM `user_pvmsg` WHERE `to_user` = 0 AND ";
        $sql .= "`creation_date` <= UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL " . (string)$days . " day))";
        Dba::write($sql);
    }

    /**
     * Sends a subsonic chat message
     */
    public function sendChatMessage(string $message, int $userId): ?int
    {
        if (!AmpConfig::get('sociable')) {
            return null;
        }

        $sql = "INSERT INTO `user_pvmsg` (`subject`, `message`, `from_user`, `to_user`, `creation_date`, `is_read`) ";
        $sql .= "VALUES (?, ?, ?, ?, ?, ?)";
        if (Dba::write($sql, array(null, $message, $userId, 0, time(), 0))) {
            return (int) Dba::insert_id();
        }

        return null;
    }

    public function setIsRead(int $privateMessageId, int $state): void
    {
        Dba::write(
            'UPDATE `user_pvmsg` SET `is_read` = ? WHERE `id` = ?',
            [$state, $privateMessageId]
        );
    }

    public function delete(int $privateMessageId): void
    {
        Dba::write(
            'DELETE FROM `user_pvmsg` WHERE `id` = ?',
            [$privateMessageId]
        );
    }

    /**
     * Creates a private message and returns the id of the newly created object
     */
    public function create(
        int $senderUserId,
        int $recipientUserId,
        string $subject,
        string $message
    ): ?int {
        $sql = 'INSERT INTO `user_pvmsg` (`subject`, `message`, `from_user`, `to_user`, `creation_date`, `is_read`) VALUES (?, ?, ?, ?, ?, ?)';

        if (Dba::write($sql, [$subject, $message, $senderUserId, $recipientUserId, time(), 0])) {
            return (int) Dba::insert_id();
        }

        return null;
    }

    /**
     * @throws ItemNotFoundException
     */
    public function getById(
        int $privateMessageId
    ): PrivateMessageInterface {
        $item = $this->modelFactory->createPrivateMsg($privateMessageId);
        if ($item->isNew() === true) {
            throw new ItemNotFoundException((string) $privateMessageId);
        }

        return $item;
    }
}
