<?php

declare(strict_types=1);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2026
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

namespace Ampache\Repository\Model;

use Ampache\Module\Database\Exception\QueryFailedException;
use Ampache\Repository\ShareRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

class ShareTest extends TestCase
{
    private ContainerInterface&MockObject $dic;
    private ShareRepositoryInterface&MockObject $shareRepository;

    public function testUpdateAppliesTheDataAndPersists(): void
    {
        $user = $this->createMock(User::class);

        $subject = new Share();

        $subject->id          = 666;
        $subject->description = 'old-description';

        $this->shareRepository->expects(static::once())
            ->method('update')
            ->with($subject, $user);

        self::assertTrue(
            $subject->update(
                [
                    'max_counter' => '42',
                    'expire' => '7',
                    'allow_stream' => '1',
                    'allow_download' => '0',
                    'description' => 'new-description',
                ],
                $user
            )
        );

        // the request data arrives as strings, so the model is what gives them their real types
        self::assertSame(42, $subject->max_counter);
        self::assertSame(7, $subject->expire_days);
        self::assertTrue($subject->allow_stream);
        self::assertFalse($subject->allow_download);
        self::assertSame('new-description', $subject->description);
    }

    public function testUpdateKeepsTheCurrentDescriptionWhenNoneIsSupplied(): void
    {
        $user = $this->createMock(User::class);

        $subject = new Share();

        $subject->id          = 666;
        $subject->description = 'old-description';

        $this->shareRepository->expects(static::once())
            ->method('update');

        self::assertTrue(
            $subject->update(
                [
                    'max_counter' => '0',
                    'expire' => '0',
                    'allow_stream' => '0',
                    'allow_download' => '0',
                ],
                $user
            )
        );

        self::assertSame('old-description', $subject->description);
    }

    public function testUpdateReturnsFalseIfTheWriteFailed(): void
    {
        $user = $this->createMock(User::class);

        $subject = new Share();

        $subject->id = 666;

        $this->shareRepository->expects(static::once())
            ->method('update')
            ->willThrowException(new QueryFailedException('some-error'));

        self::assertFalse(
            $subject->update(
                [
                    'max_counter' => '42',
                    'expire' => '7',
                    'allow_stream' => '1',
                    'allow_download' => '1',
                    'description' => 'some-description',
                ],
                $user
            )
        );
    }

    protected function setUp(): void
    {
        $this->shareRepository = $this->createMock(ShareRepositoryInterface::class);
        $this->dic             = $this->createMock(ContainerInterface::class);

        $this->dic->method('get')
            ->with(ShareRepositoryInterface::class)
            ->willReturn($this->shareRepository);

        // the model reaches its repository through the `global $dic` bridge; phpunit.xml sets
        // backupGlobals so the real container is restored after every test
        $GLOBALS['dic'] = $this->dic;
    }
}
