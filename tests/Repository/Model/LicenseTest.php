<?php

declare(strict_types=1);

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

namespace Ampache\Repository\Model;

use Ampache\Repository\LicenseRepositoryInterface;
use Generator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class LicenseTest extends TestCase
{
    private LicenseRepositoryInterface&MockObject $licenseRepository;

    private License $subject;

    protected function setUp(): void
    {
        $this->licenseRepository = $this->createMock(LicenseRepositoryInterface::class);

        $this->subject = new License(
            $this->licenseRepository,
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
        $licenseId = 666;

        $this->licenseRepository->expects(static::once())
            ->method('persist')
            ->with($this->subject)
            ->willReturn($licenseId);

        $this->subject->save();

        static::assertFalse(
            $this->subject->isNew()
        );
        static::assertSame(
            $licenseId,
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
        yield ['getName', 'setName', '', 'some-name'];
        yield ['getDescription', 'setDescription', '', 'some-description'];
        yield ['getExternalLink', 'setExternalLink', '', 'some-link'];
    }

    public function testGetLinkFormattedReturnsFormattedExternalLink(): void
    {
        $link = 'some-link';
        $name = 'some-name';

        $this->subject->setName($name);
        $this->subject->setExternalLink($link);

        static::assertSame(
            sprintf(
                '<a href="%s">%s</a>',
                $link,
                $name
            ),
            $this->subject->getLinkFormatted()
        );
    }

    public function testGetLinkFormattedReturnsNameIfLinkIsEmpty(): void
    {
        $name = 'some-name';

        $this->subject->setName($name);

        static::assertSame(
            $name,
            $this->subject->getLinkFormatted()
        );
    }
}
