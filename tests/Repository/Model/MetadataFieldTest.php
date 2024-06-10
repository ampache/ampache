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

namespace Ampache\Repository\Model;

use Ampache\Repository\MetadataFieldRepositoryInterface;
use Generator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class MetadataFieldTest extends TestCase
{
    private MetadataFieldRepositoryInterface $metadataFieldRepository;

    private MetadataField $subject;

    protected function setUp(): void
    {
        $this->metadataFieldRepository = $this->createMock(MetadataFieldRepositoryInterface::class);

        $this->subject = new MetadataField(
            $this->metadataFieldRepository
        );
    }

    public function testIsNewReturnsTrueINew(): void
    {
        static::assertTrue(
            $this->subject->isNew()
        );
    }

    public function testIsNewReturnsFalseAfterSaving(): void
    {
        $id = 666;

        $this->metadataFieldRepository->expects(static::once())
            ->method('persist')
            ->with($this->subject)
            ->willReturn($id);

        static::assertSame(
            0,
            $this->subject->getId()
        );

        $this->subject->save();

        static::assertFalse(
            $this->subject->isNew()
        );
        static::assertSame(
            $id,
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

        yield ['isPublic', 'setPublic', true, false];
    }
}
