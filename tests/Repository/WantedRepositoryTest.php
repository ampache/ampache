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
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\Model\Wanted;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Result;
use Mockery\MockInterface;

class WantedRepositoryTest extends MockeryTestCase
{
    /** @var MockInterface|ModelFactoryInterface */
    private MockInterface $modelFactory;

    /** @var MockInterface|Connection */
    private MockInterface $database;

    private WantedRepository $subject;

    public function setUp(): void
    {
        $this->modelFactory = $this->mock(ModelFactoryInterface::class);
        $this->database     = $this->mock(Connection::class);

        $this->subject = new WantedRepository(
            $this->modelFactory,
            $this->database
        );
    }

    public function testGetAllReturnsListOfIds(): void
    {
        $wantedId = 666;
        $userId   = 42;

        $result = $this->mock(Result::class);

        $this->database->shouldReceive('executeQuery')
            ->with(
                'SELECT `id` FROM `wanted` WHERE `user` = ?',
                [$userId]
            )
            ->once()
            ->andReturn($result);

        $result->shouldReceive('fetchOne')
            ->withNoArgs()
            ->times(2)
            ->andReturn((string) $wantedId, false);

        $this->assertSame(
            [$wantedId],
            $this->subject->getAll($userId)
        );
    }

    public function testFindReturnsNullIfNothingWasFound(): void
    {
        $mbId   = 'some-id';
        $userId = 42;

        $result = $this->mock(Result::class);

        $this->database->shouldReceive('executeQuery')
            ->with(
                'SELECT `id` FROM `wanted` WHERE `mbid` = ? AND `user` = ?',
                [$mbId, $userId]
            )
            ->once()
            ->andReturn($result);

        $result->shouldReceive('fetchOne')
            ->withNoArgs()
            ->once()
            ->andReturnFalse();

        $this->assertNull(
            $this->subject->find($mbId, $userId)
        );
    }

    public function testFindReturnsFinding(): void
    {
        $mbId     = 'some-id';
        $userId   = 42;
        $wantedId = 33;

        $result = $this->mock(Result::class);

        $this->database->shouldReceive('executeQuery')
            ->with(
                'SELECT `id` FROM `wanted` WHERE `mbid` = ? AND `user` = ?',
                [$mbId, $userId]
            )
            ->once()
            ->andReturn($result);

        $result->shouldReceive('fetchOne')
            ->withNoArgs()
            ->once()
            ->andReturn((string) $wantedId);

        $this->assertSame(
            $wantedId,
            $this->subject->find($mbId, $userId)
        );
    }

    public function testDeleteByMusicbrainzIdDeletes(): void
    {
        $mbId   = 'some-id';
        $userId = 666;

        $this->database->shouldReceive('executeQuery')
            ->with(
                'DELETE FROM `wanted` WHERE `mbid` = ? AND `user` = ?',
                [$mbId, $userId]
            )
            ->once();

        $this->subject->deleteByMusicbrainzId($mbId, $userId);
    }

    public function testGetAcceptedCountReturnsValue(): void
    {
        $amount = 666;

        $result = $this->mock(Result::class);

        $this->database->shouldReceive('executeQuery')
            ->with(
                'SELECT COUNT(`id`) FROM `wanted` WHERE `accepted` = 1'
            )
            ->once()
            ->andReturn($result);

        $result->shouldReceive('fetchOne')
            ->withNoArgs()
            ->once()
            ->andReturn((string) $amount);

        $this->assertSame(
            $amount,
            $this->subject->getAcceptedCount()
        );
    }

    public function testGetByIdReturnsEmptyArrayIfIdIsNotValid(): void
    {
        $this->assertSame(
            [],
            $this->subject->getById(0)
        );
    }

    public function testGetByIdReturnsEmptyErrorIfNothingWasFound(): void
    {
        $wantedId = 666;

        $result = $this->mock(Result::class);

        $this->database->shouldReceive('executeQuery')
            ->with(
                'SELECT * FROM `wanted` WHERE `id`= ?',
                [$wantedId]
            )
            ->once()
            ->andReturn($result);

        $result->shouldReceive('fetchAssociative')
            ->withNoArgs()
            ->once()
            ->andReturnFalse();

        $this->assertSame(
            [],
            $this->subject->getById($wantedId)
        );
    }

    public function testGetByIdReturnsFinding(): void
    {
        $wantedId = 666;
        $data     = ['some' => 'data'];

        $result = $this->mock(Result::class);

        $this->database->shouldReceive('executeQuery')
            ->with(
                'SELECT * FROM `wanted` WHERE `id`= ?',
                [$wantedId]
            )
            ->once()
            ->andReturn($result);

        $result->shouldReceive('fetchAssociative')
            ->withNoArgs()
            ->once()
            ->andReturn($data);

        $this->assertSame(
            $data,
            $this->subject->getById($wantedId)
        );
    }

    public function testAddAdds(): void
    {
        $mbId       = 'some-id';
        $artistId   = 666;
        $artistMbId = 'some-artist-mbid';
        $name       = 'some-name';
        $year       = 42;
        $userId     = 33;
        $wantedId   = 12345;

        $wanted = $this->mock(Wanted::class);

        $this->database->shouldReceive('executeQuery')
            ->with(
                'INSERT INTO `wanted` (`user`, `artist`, `artist_mbid`, `mbid`, `name`, `year`, `date`, `accepted`) VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
                \Mockery::type('array')
            )
            ->once();
        $this->database->shouldReceive('lastInsertId')
            ->withNoArgs()
            ->once()
            ->andReturn((string) $wantedId);

        $this->modelFactory->shouldReceive('createWanted')
            ->with($wantedId)
            ->once()
            ->andReturn($wanted);

        $wanted->shouldReceive('accept')
            ->withNoArgs()
            ->once();

        $this->subject->add(
            $mbId,
            $artistId,
            $artistMbId,
            $name,
            $year,
            $userId,
            true
        );
    }

    public function testGetByMusicbrainzIdReturnsValue(): void
    {
        $mbId     = 'some-mb-id';
        $wantedId = 666;

        $result = $this->mock(Result::class);

        $this->database->shouldReceive('executeQuery')
            ->with(
                'SELECT `id` FROM `wanted` WHERE `mbid` = ?',
                [$mbId]
            )
            ->once()
            ->andReturn($result);

        $result->shouldReceive('fetchOne')
            ->withNoArgs()
            ->once()
            ->andReturn((string) $wantedId);

        $this->assertSame(
            $wantedId,
            $this->subject->getByMusicbrainzId($mbId)
        );
    }
}
