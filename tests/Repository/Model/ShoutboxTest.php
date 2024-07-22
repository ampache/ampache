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

namespace Ampache\Repository\Model;

use Ampache\Repository\ShoutRepositoryInterface;
use DateTime;
use Generator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ShoutboxTest extends TestCase
{
    private ShoutRepositoryInterface&MockObject $shoutRepository;

    private Shoutbox $subject;

    protected function setUp(): void
    {
        $this->shoutRepository = $this->createMock(ShoutRepositoryInterface::class);

        $this->subject = new Shoutbox(
            $this->shoutRepository,
        );
    }

    public function testIsNewReturnsTrueIfIdIsZero(): void
    {
        static::assertTrue(
            $this->subject->isNew()
        );
    }

    public function testIsNewReturnsTrueIfIdIsNotZero(): void
    {
        $shoutId = 666;

        $this->shoutRepository->expects(static::once())
            ->method('persist')
            ->with($this->subject)
            ->willReturn($shoutId);

        $this->subject->save();

        static::assertFalse(
            $this->subject->isNew()
        );
        static::assertSame(
            $shoutId,
            $this->subject->getId()
        );
    }

    #[DataProvider(methodName: 'setterGetterDataProvider')]
    public function testGetterReturnsSetData(
        string $getterMethod,
        string $setterMethod,
        mixed $defaultValue,
        mixed $setValue
    ): void {
        static::assertSame(
            $defaultValue,
            call_user_func_array([$this->subject, $getterMethod], [])
        );

        call_user_func_array([$this->subject, $setterMethod], [$setValue]);

        static::assertSame(
            $setValue,
            call_user_func_array([$this->subject, $getterMethod], [])
        );
    }

    public static function setterGetterDataProvider(): Generator
    {
        yield ['getOffset', 'setOffset', 0, 666];
        yield ['getObjectType', 'setObjectType', '', 'some-type'];
        yield ['getObjectId', 'setObjectId', 0, 666];
        yield ['isSticky', 'setSticky', false, true];
    }

    public function testGetUserIdReturnsSetValue(): void
    {
        $user = $this->createMock(User::class);

        $userId = 666;

        $user->expects(static::once())
            ->method('getId')
            ->willReturn($userId);

        static::assertSame(
            0,
            $this->subject->getUserId()
        );

        $this->subject->setUser($user);

        static::assertSame(
            $userId,
            $this->subject->getUserId()
        );
    }

    public function testGetTextReturnsSetText(): void
    {
        $text = '<div>AGGI AGGI é«ü%>?&</div>';

        static::assertSame(
            '',
            $this->subject->getText()
        );

        $this->subject->setText($text);

        static::assertSame(
            strip_tags(htmlspecialchars($text)),
            $this->subject->getText()
        );
    }

    public function testGetDateReturnsSetDate(): void
    {
        static::assertSame(
            0,
            $this->subject->getDate()->getTimestamp()
        );

        $date = new DateTime();

        $this->subject->setDate($date);

        static::assertSame(
            $date->getTimestamp(),
            $this->subject->getDate()->getTimestamp()
        );
    }
}
