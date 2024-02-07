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
 */

namespace Ampache\Repository\Model;

use Ampache\Repository\MetadataFieldRepositoryInterface;
use Ampache\Repository\MetadataRepositoryInterface;
use Generator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class MetadataTest extends TestCase
{
    private MetadataRepositoryInterface&MockObject $metadataRepository;

    private MetadataFieldRepositoryInterface $metadataFieldRepository;

    private Metadata $subject;

    protected function setUp(): void
    {
        $this->metadataRepository      = $this->createMock(MetadataRepositoryInterface::class);
        $this->metadataFieldRepository = $this->createMock(MetadataFieldRepositoryInterface::class);

        $this->subject = new Metadata(
            $this->metadataRepository,
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

        $this->metadataRepository->expects(static::once())
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
        yield ['getObjectId', 'setObjectId', 0, 666];
        yield ['getData', 'setData', '', 'some-data'];
        yield ['getType', 'setType', '', 'Some-type'];
    }

    public function testSetTypePerformsUcFirst(): void
    {
        $value = 'some-value';

        $this->subject->setType($value);

        static::assertSame(
            ucfirst($value),
            $this->subject->getType()
        );
    }

    public function testGetFieldReturnsSetField(): void
    {
        $field = $this->createMock(MetadataField::class);

        $this->subject->setField($field);

        static::assertSame(
            $field,
            $this->subject->getField()
        );
    }

    public function testGetFieldLoadsFieldAndCaches(): void
    {
        $field = $this->createMock(MetadataField::class);

        $fieldId = 666;

        $this->subject->setFieldId($fieldId);

        $this->metadataFieldRepository->expects(static::once())
            ->method('findById')
            ->with($fieldId)
            ->willReturn($field);

        static::assertSame(
            $field,
            $this->subject->getField()
        );
        static::assertSame(
            $field,
            $this->subject->getField()
        );
        static::assertSame(
            $fieldId,
            $this->subject->getFieldId()
        );
    }
}
