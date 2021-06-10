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

use Ampache\MockeryTestCase;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Result;
use Mockery\MockInterface;

class UserPlaylistRepositoryTest extends MockeryTestCase
{
    private MockInterface $database;

    private UserPlaylistRepository $subject;

    public function setUp(): void
    {
        $this->database = $this->mock(Connection::class);

        $this->subject = new UserPlaylistRepository(
            $this->database
        );
    }

    public function testGetItemsByUserReturnsData(): void
    {
        $userId       = 666;
        $id           = 42;
        $objectType   = 'some-type';
        $objectId     = 555;
        $track        = 21;
        $currentTrack = 33;
        $currentTime  = 1234;

        $result = $this->mock(Result::class);

        $this->database->shouldReceive('executeQuery')
            ->with(
                'SELECT `id`, `object_type`, `object_id`, `track`, `current_track`, `current_time`  FROM `user_playlist` WHERE `user` = ? ORDER BY `track`, `id`',
                [$userId]
            )
            ->once()
            ->andReturn($result);

        $result->shouldReceive('fetchAssociative')
            ->withNoArgs()
            ->twice()
            ->andReturn(
                [
                    'id' => (string) $id,
                    'object_type' => $objectType,
                    'object_id' => (string) $objectId,
                    'track' => (string) $track,
                    'current_track' => (string) $currentTrack,
                    'current_time' => (string) $currentTime,
                ],
                false
            );

        $this->assertSame(
            [[
                'id' => $id,
                'object_type' => $objectType,
                'object_id' => $objectId,
                'track' => $track,
                'current_track' => $currentTrack,
                'current_time' => $currentTime,
            ]],
            $this->subject->getItemsByUser($userId)
        );
    }

    public function testGetCurrentObjectByUserReturnsNullIfNothingWasFound(): void
    {
        $userId = 666;

        $this->database->shouldReceive('fetchAssociative')
            ->with(
                'SELECT `id`, `object_type`, `object_id`, `track`, `current_track`, `current_time` FROM `user_playlist` WHERE `user`= ? AND `current_track` = 1 LIMIT 1',
                [$userId]
            )
            ->once()
            ->andReturnFalse();

        $this->assertNull(
            $this->subject->getCurrentObjectByUser($userId)
        );
    }

    public function testGetCurrentObjectByUserReturnsData(): void
    {
        $userId       = 666;
        $id           = 42;
        $objectType   = 'some-type';
        $objectId     = 21;
        $track        = 33;
        $currentTrack = 123;
        $currentTime  = 456;

        $this->database->shouldReceive('fetchAssociative')
            ->with(
                'SELECT `id`, `object_type`, `object_id`, `track`, `current_track`, `current_time` FROM `user_playlist` WHERE `user`= ? AND `current_track` = 1 LIMIT 1',
                [$userId]
            )
            ->once()
            ->andReturn([
                'id' => (string) $id,
                'object_type' => $objectType,
                'object_id' => (string) $objectId,
                'track' => (string) $track,
                'current_track' => (string) $currentTrack,
                'current_time' => (string) $currentTime,
            ]);

        $this->assertSame(
            [
                'id' => $id,
                'object_type' => $objectType,
                'object_id' => $objectId,
                'track' => $track,
                'current_track' => $currentTrack,
                'current_time' => $currentTime,
            ],
            $this->subject->getCurrentObjectByUser($userId)
        );
    }

    public function testClearClears(): void
    {
        $userId = 666;

        $this->database->shouldReceive('executeQuery')
            ->with(
                'DELETE FROM `user_playlist` WHERE `user` = ?',
                [$userId]
            )
            ->once();

        $this->subject->clear($userId);
    }

    public function testAddItemAdds(): void
    {
        $userId     = 666;
        $objectType = 'some-type';
        $objectId   = 42;
        $track      = 21;

        $this->database->shouldReceive('executeQuery')
            ->with(
                'INSERT INTO `user_playlist` (`user`, `object_type`, `object_id`, `track`) VALUES (?, ?, ?, ?)',
                [$userId, $objectType, $objectId, $track]
            )
            ->once();

        $this->subject->addItem(
            $userId,
            $objectType,
            $objectId,
            $track
        );
    }

    public function testSetCurrentObjectByUser(): void
    {
        $userId     = 666;
        $objectType = 'some-type';
        $objectId   = 42;
        $position   = 33;

        $this->database->shouldReceive('executeQuery')
            ->with(
                'UPDATE `user_playlist` SET `current_track` = 0, `current_time` = 0 WHERE `user` = ?',
                [$userId]
            )
            ->once();
        $this->database->shouldReceive('executeQuery')
            ->with(
                'UPDATE `user_playlist` SET `current_track` = 1, `current_time` = ? WHERE `object_type` = ? AND `object_id` = ? AND `user` = ? LIMIT 1',
                [$position, $objectType, $objectId, $userId]
            )
            ->once();

        $this->subject->setCurrentObjectByUser(
            $userId,
            $objectType,
            $objectId,
            $position
        );
    }
}
