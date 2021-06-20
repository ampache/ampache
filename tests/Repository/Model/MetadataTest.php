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

namespace Ampache\Repository\Model;

use Ampache\MockeryTestCase;
use Ampache\Repository\MetadataRepositoryInterface;
use Mockery\MockInterface;

class MetadataTest extends MockeryTestCase
{
    private MockInterface $metadataRepository;

    private MockInterface $modelFactory;

    private Metadata $subject;

    private int $id = 666;

    public function setUp(): void
    {
        $this->metadataRepository = $this->mock(MetadataRepositoryInterface::class);
        $this->modelFactory       = $this->mock(ModelFactoryInterface::class);

        $this->subject = new Metadata(
            $this->metadataRepository,
            $this->modelFactory,
            $this->id
        );
    }

    /**
     * @dataProvider getterDataProvider
     */
    public function testGetter(
        string $methodName,
        string $key,
        string $value,
        $expectedValue
    ): void {
        $this->metadataRepository->shouldReceive('getDbData')
            ->with($this->id)
            ->once()
            ->andReturn([$key => $value]);

        $this->assertSame(
            $expectedValue,
            call_user_func([$this->subject, $methodName])
        );
    }

    public function getterDataProvider(): array
    {
        return [
            ['getId', 'id', '666', 666],
            ['getObjectId', 'object_id', '42', 42],
            ['getFieldId', 'field', '33', 33],
            ['getData', 'data', 'some-data', 'some-data'],
            ['getType', 'type', 'some-type', 'some-type'],
        ];
    }

    public function testGetFieldReturnsFieldInstance(): void
    {
        $fieldId = 666;
        $field   = $this->mock(MetadataFieldInterface::class);

        $this->metadataRepository->shouldReceive('getDbData')
            ->with($this->id)
            ->once()
            ->andReturn(['field' => $fieldId]);

        $this->modelFactory->shouldReceive('createMetadataField')
            ->with($fieldId)
            ->once()
            ->andReturn($field);

        $this->assertSame(
            $field,
            $this->subject->getField()
        );
    }
}
