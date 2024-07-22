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

namespace Ampache\Repository;

use Ampache\Module\Database\DatabaseConnectionInterface;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\Model\PrivateMessageInterface;
use Ampache\Repository\Model\User;
use PDOStatement;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class PrivateMessageRepositoryTest extends TestCase
{
    private ModelFactoryInterface&MockObject $modelFactory;

    private DatabaseConnectionInterface&MockObject $connection;

    private PrivateMessageRepository $subject;

    protected function setUp(): void
    {
        $this->modelFactory = $this->createMock(ModelFactoryInterface::class);
        $this->connection   = $this->createMock(DatabaseConnectionInterface::class);

        $this->subject = new PrivateMessageRepository(
            $this->modelFactory,
            $this->connection
        );
    }

    public function testGetUnreadCountReturnsValue(): void
    {
        $user = $this->createMock(User::class);

        $userId = 666;
        $value  = 42;

        $user->expects(static::once())
            ->method('getId')
            ->willReturn($userId);

        $this->connection->expects(static::once())
            ->method('fetchOne')
            ->with(
                'SELECT count(`id`) as `amount` FROM `user_pvmsg` WHERE `to_user` = ? AND `is_read` = \'0\'',
                [$userId]
            )
            ->willReturn($value);

        static::assertSame(
            $value,
            $this->subject->getUnreadCount($user)
        );
    }

    public function testGetChatMessagesReturnsData(): void
    {
        $since    = 123;
        $objectId = 42;

        $result = $this->createMock(PDOStatement::class);

        $this->connection->expects(static::once())
            ->method('query')
            ->with(
                'SELECT `id` FROM `user_pvmsg` WHERE `to_user` = 0  AND `user_pvmsg`.`creation_date` > ? ORDER BY `user_pvmsg`.`creation_date` DESC',
                [$since]
            )
            ->willReturn($result);

        $result->expects(static::exactly(2))
            ->method('fetchColumn')
            ->willReturn((string) $objectId, false);

        static::assertSame(
            [$objectId],
            $this->subject->getChatMessages($since)
        );
    }

    public function testCleanChatMessagesCleans(): void
    {
        $days = 666;

        $this->connection->expects(static::once())
            ->method('query')
            ->with(
                sprintf(
                    'DELETE FROM `user_pvmsg` WHERE `to_user` = 0 AND `creation_date` <= UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL %d day))',
                    $days
                )
            );

        $this->subject->cleanChatMessages($days);
    }

    public function testSetIsReadSetsStateForMessage(): void
    {
        $message = $this->createMock(PrivateMessageInterface::class);

        $messageId = 666;

        $message->expects(static::once())
            ->method('getId')
            ->willReturn($messageId);

        $this->connection->expects(static::once())
            ->method('query')
            ->with(
                'UPDATE `user_pvmsg` SET `is_read` = ? WHERE `id` = ?',
                [1, $messageId]
            );

        $this->subject->setIsRead($message, 1);
    }

    public function testDeleteDeletes(): void
    {
        $messageId = 666;

        $message = $this->createMock(PrivateMessageInterface::class);

        $message->expects(static::once())
            ->method('getId')
            ->willReturn($messageId);

        $this->connection->expects(static::once())
            ->method('query')
            ->with(
                'DELETE FROM `user_pvmsg` WHERE `id` = ?',
                [$messageId]
            );

        $this->subject->delete($message);
    }

    public function testFindByIdReturnsNullIfObjectWasNotFound(): void
    {
        $messageId = 666;

        $message = $this->createMock(PrivateMessageInterface::class);

        $message->expects(static::once())
            ->method('isNew')
            ->willReturn(true);

        $this->modelFactory->expects(static::once())
            ->method('createPrivateMsg')
            ->with($messageId)
            ->willReturn($message);

        static::assertNull(
            $this->subject->findById($messageId)
        );
    }

    public function testFindByIdReturnsObject(): void
    {
        $messageId = 666;

        $message = $this->createMock(PrivateMessageInterface::class);

        $message->expects(static::once())
            ->method('isNew')
            ->willReturn(false);

        $this->modelFactory->expects(static::once())
            ->method('createPrivateMsg')
            ->with($messageId)
            ->willReturn($message);

        static::assertSame(
            $message,
            $this->subject->findById($messageId)
        );
    }

    public function testCreateCreatesPmWithoutSender(): void
    {
        $recipient = $this->createMock(User::class);

        $subject    = 'some-subject';
        $message    = 'some-message';
        $from_user  = 666;
        $insertedId = 42;

        $recipient->expects(static::once())
            ->method('getId')
            ->willReturn($from_user);

        $this->connection->expects(static::once())
            ->method('query')
            ->with(
                'INSERT INTO `user_pvmsg` (`subject`, `message`, `from_user`, `to_user`, `creation_date`, `is_read`) VALUES (?, ?, ?, ?, UNIX_TIMESTAMP(), 0)',
                [
                    $subject,
                    $message,
                    $from_user,
                    0,
                ]
            );
        $this->connection->expects(static::once())
            ->method('getLastInsertedId')
            ->willReturn($insertedId);

        static::assertSame(
            $insertedId,
            $this->subject->create(null, $recipient, $subject, $message)
        );
    }

    public function testCreateCreatesPmWithSender(): void
    {
        $recipient = $this->createMock(User::class);
        $sender    = $this->createMock(User::class);

        $subject    = 'some-subject';
        $message    = 'some-message';
        $from_user  = 666;
        $insertedId = 42;
        $to_user    = 123;

        $recipient->expects(static::once())
            ->method('getId')
            ->willReturn($from_user);

        $sender->expects(static::once())
            ->method('getId')
            ->willReturn($to_user);

        $this->connection->expects(static::once())
            ->method('query')
            ->with(
                'INSERT INTO `user_pvmsg` (`subject`, `message`, `from_user`, `to_user`, `creation_date`, `is_read`) VALUES (?, ?, ?, ?, UNIX_TIMESTAMP(), 0)',
                [
                    $subject,
                    $message,
                    $from_user,
                    $to_user,
                ]
            );
        $this->connection->expects(static::once())
            ->method('getLastInsertedId')
            ->willReturn($insertedId);

        static::assertSame(
            $insertedId,
            $this->subject->create($sender, $recipient, $subject, $message)
        );
    }
}
