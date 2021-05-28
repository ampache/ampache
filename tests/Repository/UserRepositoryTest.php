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
 *
 */

declare(strict_types=1);

namespace Ampache\Repository;

use Ampache\MockeryTestCase;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\Model\User;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Result;
use Mockery\MockInterface;

class UserRepositoryTest extends MockeryTestCase
{
    private MockInterface $database;

    private MockInterface $modelFactory;

    private UserRepository $subject;

    public function setUp(): void
    {
        $this->database     = $this->mock(Connection::class);
        $this->modelFactory = $this->mock(ModelFactoryInterface::class);

        $this->subject = new UserRepository(
            $this->database,
            $this->modelFactory
        );
    }

    public function testFindByRssTokenReturnsNullIfNothingWasFound(): void
    {
        $token = 'some-token';

        $this->database->shouldReceive('fetchOne')
            ->with(
                'SELECT `id` FROM `user` WHERE `rsstoken` = ?',
                [$token]
            )
            ->once()
            ->andReturnFalse();

        $this->assertNull(
            $this->subject->findByRssToken($token)
        );
    }

    public function testFindByRssTokenReturnsUser(): void
    {
        $token  = 'some-token';
        $userId = 666;

        $user = $this->mock(User::class);

        $this->database->shouldReceive('fetchOne')
            ->with(
                'SELECT `id` FROM `user` WHERE `rsstoken` = ?',
                [$token]
            )
            ->once()
            ->andReturn((string) $userId);

        $this->modelFactory->shouldReceive('createUser')
            ->with($userId)
            ->once()
            ->andReturn($user);

        $this->assertSame(
            $user,
            $this->subject->findByRssToken($token)
        );
    }

    public function testFindByUserNameReturnsNullIfNothingWasFound(): void
    {
        $name = 'some-name';

        $this->database->shouldReceive('fetchOne')
            ->with(
                'SELECT `id` FROM `user` WHERE `username`= ?',
                [$name]
            )
            ->once()
            ->andReturnFalse();

        $this->assertNull(
            $this->subject->findByUsername($name)
        );
    }

    public function testFindByUserNameReturnsUserId(): void
    {
        $name   = 'some-name';
        $userId = 666;

        $this->database->shouldReceive('fetchOne')
            ->with(
                'SELECT `id` FROM `user` WHERE `username`= ?',
                [$name]
            )
            ->once()
            ->andReturn((string) $userId);

        $this->assertSame(
            $userId,
            $this->subject->findByUsername($name)
        );
    }

    public function testGetValidReturnsListWithDisabled(): void
    {
        $result = $this->mock(Result::class);

        $this->database->shouldReceive('executeQuery')
            ->with(
                'SELECT `id` FROM `user`'
            )
            ->once()
            ->andReturn($result);

        $result->shouldReceive('fetchOne')
            ->withNoArgs()
            ->once()
            ->andReturnFalse();

        $this->assertSame(
            [],
            $this->subject->getValid(true)
        );
    }

    public function testGetValidReturnsListWithouyDisabled(): void
    {
        $result = $this->mock(Result::class);

        $userId = 666;

        $this->database->shouldReceive('executeQuery')
            ->with(
                'SELECT `id` FROM `user` WHERE `disabled` = \'0\''
            )
            ->once()
            ->andReturn($result);

        $result->shouldReceive('fetchOne')
            ->withNoArgs()
            ->twice()
            ->andReturn($userId, false);

        $this->assertSame(
            [$userId],
            $this->subject->getValid()
        );
    }

    public function testFindByEmailReturnsNullIfNothingWasFound(): void
    {
        $email = 'some-email';

        $this->database->shouldReceive('fetchOne')
            ->with(
                'SELECT `id` FROM `user` WHERE `email` = ?',
                [$email]
            )
            ->once()
            ->andReturnFalse();

        $this->assertNull(
            $this->subject->findByEmail($email)
        );
    }

    public function testFindByEmailReturnsUser(): void
    {
        $email  = 'some-email';
        $userId = 666;

        $user = $this->mock(User::class);

        $this->database->shouldReceive('fetchOne')
            ->with(
                'SELECT `id` FROM `user` WHERE `email` = ?',
                [$email]
            )
            ->once()
            ->andReturn((string) $userId);

        $this->modelFactory->shouldReceive('createUser')
            ->with($userId)
            ->once()
            ->andReturn($user);

        $this->assertSame(
            $user,
            $this->subject->findByEmail($email)
        );
    }

    public function testFindByWebsiteReturnsNullIfNothingWasFound(): void
    {
        $website = 'some-website';

        $this->database->shouldReceive('fetchOne')
            ->with(
                'SELECT `id` FROM `user` WHERE `website` = ? LIMIT 1',
                [$website]
            )
            ->once()
            ->andReturnFalse();

        $this->assertNull(
            $this->subject->findByWebsite($website)
        );
    }

    public function testFindByWebsiteReturnsUser(): void
    {
        $website = 'some-website/';
        $userId  = 666;

        $user = $this->mock(User::class);

        $this->database->shouldReceive('fetchOne')
            ->with(
                'SELECT `id` FROM `user` WHERE `website` = ? LIMIT 1',
                [rtrim($website, '/')]
            )
            ->once()
            ->andReturn((string) $userId);

        $this->modelFactory->shouldReceive('createUser')
            ->with($userId)
            ->once()
            ->andReturn($user);

        $this->assertSame(
            $user,
            $this->subject->findByWebsite($website)
        );
    }
}
