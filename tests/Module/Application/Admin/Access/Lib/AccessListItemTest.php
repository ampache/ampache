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

declare(strict_types=1);

namespace Ampache\Module\Application\Admin\Access\Lib;

use Ampache\MockeryTestCase;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\Model\User;
use Ampache\Module\Authorization\Access;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\DataProvider;

class AccessListItemTest extends MockeryTestCase
{
    private MockInterface&Access $access;

    private MockInterface&ModelFactoryInterface $modelFactory;

    private AccessListItem $subject;

    public function setUp(): void
    {
        $this->access       = $this->mock(Access::class);
        $this->modelFactory = $this->mock(ModelFactoryInterface::class);

        $this->subject = new AccessListItem(
            $this->modelFactory,
            $this->access
        );
    }

    #[DataProvider(methodName: 'levelNameDataProvider')]
    public function testGetLevelNameReturnsLabel(
        int $level,
        string $label
    ): void {
        $this->access->level = $level;

        $this->assertSame(
            $label,
            $this->subject->getLevelName()
        );
    }

    public static function levelNameDataProvider(): array
    {
        return [
            [99, 'All'],
            [5, 'View'],
            [25, 'Read'],
            [50, 'Read/Write'],
            [-666, ''],
        ];
    }

    public function testGetUserNameReturnsDefault(): void
    {
        $userId = -1;

        $this->access->user = (string)$userId;

        $this->assertSame(
            'All',
            $this->subject->getUserName()
        );
    }

    public function testGetUserNameReturnsName(): void
    {
        $userId       = 666;
        $userFullName = 'fullname';
        $userName     = 'username';

        $user = $this->mock(User::class);

        $user->fullname = $userFullName;
        $user->username = $userName;

        $this->access->user = (string)$userId;

        $this->modelFactory->shouldReceive('createUser')
            ->with($userId)
            ->once()
            ->andReturn($user);

        $this->assertSame(
            sprintf('%s (%s)', $userFullName, $userName),
            $this->subject->getUserName()
        );
    }

    #[DataProvider(methodName: 'typeNameDataProvider')]
    public function testGetTypeNameReturnLabel(
        string $typeId,
        string $label
    ): void {
        $this->access->type = $typeId;

        $this->assertSame(
            $label,
            $this->subject->getTypeName()
        );
    }

    public static function typeNameDataProvider(): array
    {
        return [
            ['rpc', 'API/RPC'],
            ['network', 'Local Network Definition'],
            ['interface', 'Web Interface'],
            ['stream', 'Stream Access'],
            ['foobar', 'Stream Access'],
        ];
    }

    public function testGetStartIpReturnsEmptyStringIfConversionFails(): void
    {
        $this->access->start = 'foobar';

        $this->assertSame(
            '',
            $this->subject->getStartIp()
        );
    }

    public function testGetStartIpReturnsIpReadable(): void
    {
        $ip = '1.2.3.4';

        $this->access->start = inet_pton($ip);

        $this->assertSame(
            $ip,
            $this->subject->getStartIp()
        );
    }

    public function testGetEndIpReturnsEmptyStringIfConversionFails(): void
    {
        $this->access->end = 'foobar';

        $this->assertSame(
            '',
            $this->subject->getEndIp()
        );
    }

    public function testGetEndIpReturnsIpReadable(): void
    {
        $ip = '1.2.3.4';

        $this->access->end = inet_pton($ip);

        $this->assertSame(
            $ip,
            $this->subject->getEndIp()
        );
    }

    public function testGetNameReturnsValue(): void
    {
        $value = 'some-value';

        $this->access->name = $value;

        $this->assertSame(
            $value,
            $this->subject->getName()
        );
    }

    public function testGetIdReturnsValue(): void
    {
        $value = 42;

        $this->access->id = $value;

        $this->assertSame(
            $value,
            $this->subject->getId()
        );
    }

    public function testGetLevelReturnsValue(): void
    {
        $value = 42;

        $this->access->level = $value;

        $this->assertSame(
            $value,
            $this->subject->getLevel()
        );
    }

    public function testGetTypeReturnsValue(): void
    {
        $value = 'some-value';

        $this->access->type = $value;

        $this->assertSame(
            $value,
            $this->subject->getType()
        );
    }

    public function testGetUserIdReturnsValue(): void
    {
        $value = 42;

        $this->access->user = $value;

        $this->assertSame(
            $value,
            $this->subject->getUserId()
        );
    }
}
