<?php
/*
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

declare(strict_types=1);

namespace Ampache\Module\Authorization;

use Ampache\MockeryTestCase;
use Ampache\Module\Authorization\Exception\AclItemDuplicationException;
use Ampache\Module\Authorization\Exception\InvalidEndIpException;
use Ampache\Module\Authorization\Exception\InvalidIpRangeException;
use Ampache\Module\Authorization\Exception\InvalidStartIpException;
use Ampache\Repository\AccessRepositoryInterface;
use Mockery\MockInterface;

class AccessListManagerTest extends MockeryTestCase
{
    /** @var MockInterface|AccessRepositoryInterface|null */
    private MockInterface $accessRepository;

    private ?AccessListManager $subject;

    public function setUp(): void
    {
        $this->accessRepository = $this->mock(AccessRepositoryInterface::class);

        $this->subject = new AccessListManager(
            $this->accessRepository
        );
    }

    public function testUpdateThrowsExceptionOnInvalidStartIp(): void
    {
        $this->expectException(InvalidStartIpException::class);

        $this->subject->update(
            111,
            '666',
            '1.2.3.4',
            'some-name',
            42,
            33,
            'some-type'
        );
    }

    public function testUpdateThrowsExceptionOnInvalidEndIp(): void
    {
        $this->expectException(InvalidEndIpException::class);

        $this->subject->update(
            111,
            '1.2.3.4',
            '666',
            'some-name',
            42,
            33,
            'some-type'
        );
    }

    public function testUpdateThrowsExceptionOnInvalidIpRange(): void
    {
        $this->expectException(InvalidIpRangeException::class);

        $this->subject->update(
            111,
            '::',
            '1.2.3.4',
            'some-name',
            42,
            33,
            'some-type'
        );
    }

    public function testUpdateUpdatesEntry(): void
    {
        $accessId = 111;
        $startIp  = '1.2.3.4';
        $endIp    = '2.3.4.5';
        $name     = 'some-name';
        $userId   = 42;
        $level    = 666;
        $type     = AccessLevelEnum::TYPE_INTERFACE;

        $this->accessRepository->shouldReceive('update')
            ->with(
                $accessId,
                inet_pton($startIp),
                inet_pton($endIp),
                $name,
                $userId,
                $level,
                $type
            )
            ->once();

        $this->subject->update(
            $accessId,
            $startIp,
            $endIp,
            $name,
            $userId,
            $level,
            $type
        );
    }

    public function testCreateCreatesEntry(): void
    {
        $startIp        = '1.2.3.4';
        $endIp          = '2.3.4.5';
        $name           = 'some-name';
        $userId         = 42;
        $level          = 666;
        $type           = AccessLevelEnum::TYPE_INTERFACE;
        $additionalType = 'all';

        $this->accessRepository->shouldReceive('exists')
            ->with(
                inet_pton($startIp),
                inet_pton($endIp),
                $type,
                $userId
            )
            ->once()
            ->andReturnFalse();
        $this->accessRepository->shouldReceive('exists')
            ->with(
                inet_pton($startIp),
                inet_pton($endIp),
                AccessLevelEnum::TYPE_STREAM,
                $userId
            )
            ->once()
            ->andReturnFalse();
        $this->accessRepository->shouldReceive('exists')
            ->with(
                inet_pton($startIp),
                inet_pton($endIp),
                AccessLevelEnum::TYPE_INTERFACE,
                $userId
            )
            ->once()
            ->andReturnFalse();
        $this->accessRepository->shouldReceive('create')
            ->with(
                inet_pton($startIp),
                inet_pton($endIp),
                $name,
                $userId,
                $level,
                $type
            )
            ->once();
        $this->accessRepository->shouldReceive('create')
            ->with(
                inet_pton($startIp),
                inet_pton($endIp),
                $name,
                $userId,
                $level,
                AccessLevelEnum::TYPE_STREAM
            )
            ->once();
        $this->accessRepository->shouldReceive('create')
            ->with(
                inet_pton($startIp),
                inet_pton($endIp),
                $name,
                $userId,
                $level,
                AccessLevelEnum::TYPE_INTERFACE
            )
            ->once();

        $this->subject->create(
            $startIp,
            $endIp,
            $name,
            $userId,
            $level,
            $type,
            $additionalType
        );
    }

    public function testCreateThrowsExceptionOnDuplicateEntry(): void
    {
        $this->expectException(AclItemDuplicationException::class);

        $startIp        = '1.2.3.4';
        $endIp          = '2.3.4.5';
        $name           = 'some-name';
        $userId         = 42;
        $level          = 666;
        $type           = AccessLevelEnum::TYPE_INTERFACE;
        $additionalType = 'all';

        $this->accessRepository->shouldReceive('exists')
            ->with(
                inet_pton($startIp),
                inet_pton($endIp),
                $type,
                $userId
            )
            ->once()
            ->andReturnTrue();

        $this->subject->create(
            $startIp,
            $endIp,
            $name,
            $userId,
            $level,
            $type,
            $additionalType
        );
    }
}
