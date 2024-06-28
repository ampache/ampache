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
 */

namespace Ampache\Repository;

use Ampache\Module\Database\DatabaseConnectionInterface;
use Ampache\Repository\Model\User;
use DateTime;
use PDOStatement;
use PHPUnit\Framework\TestCase;
use SEEC\PhpUnit\Helper\ConsecutiveParams;

class BookmarkRepositoryTest extends TestCase
{
    use ConsecutiveParams;

    private DatabaseConnectionInterface $connection;

    private BookmarkRepository $subject;

    protected function setUp(): void
    {
        $this->connection = $this->createMock(DatabaseConnectionInterface::class);

        $this->subject = new BookmarkRepository(
            $this->connection
        );
    }

    public function testGetByUserReturnsListOfValues(): void
    {
        $user   = $this->createMock(User::class);
        $result = $this->createMock(PDOStatement::class);

        $userId     = 666;
        $bookmarkId = 42;

        $user->expects(static::once())
            ->method('getId')
            ->willReturn($userId);

        $this->connection->expects(static::once())
            ->method('query')
            ->with(
                'SELECT `id` FROM `bookmark` WHERE `user` = ?',
                [
                    $userId
                ]
            )
            ->willReturn($result);

        $result->expects(static::exactly(2))
            ->method('fetchColumn')
            ->willReturn((string) $bookmarkId, false);

        static::assertSame(
            [$bookmarkId],
            $this->subject->getByUser($user)
        );
    }

    public function testGetByUserAndCommentReturnsValue(): void
    {
        $user   = $this->createMock(User::class);
        $result = $this->createMock(PDOStatement::class);

        $userId     = 666;
        $bookmarkId = 42;
        $comment    = 'some-comment';

        $user->expects(static::once())
            ->method('getId')
            ->willReturn($userId);

        $this->connection->expects(static::once())
            ->method('query')
            ->with(
                'SELECT `id` FROM `bookmark` WHERE `user` = ? AND `comment` = ?',
                [
                    $userId,
                    $comment
                ]
            )
            ->willReturn($result);

        $result->expects(static::exactly(2))
            ->method('fetchColumn')
            ->willReturn((string) $bookmarkId, false);

        static::assertSame(
            [$bookmarkId],
            $this->subject->getByUserAndComment($user, $comment)
        );
    }

    public function testDeleteDeletesBookmark(): void
    {
        $bookmarkId = 666;

        $this->connection->expects(static::once())
            ->method('query')
            ->with(
                'DELETE FROM `bookmark` WHERE `id` = ?',
                [
                    $bookmarkId
                ]
            );

        $this->subject->delete($bookmarkId);
    }

    public function testCollectGarbageCollects(): void
    {
        $this->connection->expects(static::exactly(3))
            ->method('query')
            ->with(...self::withConsecutive(
                [
                    'DELETE FROM `bookmark` USING `bookmark` LEFT JOIN `song` ON `song`.`id` = `bookmark`.`object_id` WHERE `bookmark`.`object_type` = \'song\' AND `song`.`id` IS NULL;',
                ],
                [
                    'DELETE FROM `bookmark` USING `bookmark` LEFT JOIN `video` ON `video`.`id` = `bookmark`.`object_id` WHERE `bookmark`.`object_type` = \'video\' AND `video`.`id` IS NULL;',
                ],
                [
                    'DELETE FROM `bookmark` USING `bookmark` LEFT JOIN `podcast_episode` ON `podcast_episode`.`id` = `bookmark`.`object_id` WHERE `bookmark`.`object_type` = \'podcast_episode\' AND `podcast_episode`.`id` IS NULL;',
                ]
            ));

        $this->subject->collectGarbage();
    }

    public function testUpdateUpdates(): void
    {
        $bookmarkId = 666;
        $position   = 42;
        $date       = new DateTime();

        $this->connection->expects(static::once())
            ->method('query')
            ->with(
                'UPDATE `bookmark` SET `position` = ?, `update_date` = ? WHERE `id` = ?',
                [$position, $date->getTimestamp(), $bookmarkId]
            );

        $this->subject->update(
            $bookmarkId,
            $position,
            $date,
        );
    }

    public function testMigrateMigrates(): void
    {
        $objectType  = 'some-type';
        $oldObjectId = 666;
        $newObjectId = 42;

        $this->connection->expects(static::once())
            ->method('query')
            ->with(
                'UPDATE IGNORE `bookmark` SET `object_id` = ? WHERE `object_id` = ? AND `object_type` = ?',
                [$newObjectId, $oldObjectId, ucfirst($objectType)]
            );

        $this->subject->migrate($objectType, $oldObjectId, $newObjectId);
    }
}
