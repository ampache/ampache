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

namespace Ampache\Module\Metadata;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Repository\MetadataFieldRepositoryInterface;
use Ampache\Repository\MetadataRepositoryInterface;
use Ampache\Repository\Model\Metadata;
use Ampache\Repository\Model\MetadataField;
use ArrayIterator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use SEEC\PhpUnit\Helper\ConsecutiveParams;

class MetadataManagerTest extends TestCase
{
    use ConsecutiveParams;

    private MetadataRepositoryInterface&MockObject $metadataRepository;

    private MetadataFieldRepositoryInterface&MockObject $metadataFieldRepository;

    private ConfigContainerInterface&MockObject $configContainer;

    private MetadataManager $subject;

    protected function setUp(): void
    {
        $this->metadataRepository      = $this->createMock(MetadataRepositoryInterface::class);
        $this->metadataFieldRepository = $this->createMock(MetadataFieldRepositoryInterface::class);
        $this->configContainer         = $this->createMock(ConfigContainerInterface::class);

        $this->subject = new MetadataManager(
            $this->metadataRepository,
            $this->metadataFieldRepository,
            $this->configContainer
        );
    }

    public function testGetMetadataReturnsEmptyResultIfDisabled(): void
    {
        $item = $this->createMock(MetadataEnabledInterface::class);

        static::assertSame(
            [],
            iterator_to_array($this->subject->getMetadata($item))
        );
    }

    public function testGetMetadataReturnsMetadata(): void
    {
        $item     = $this->createMock(MetadataEnabledInterface::class);
        $metadata = $this->createMock(Metadata::class);

        $result   = new ArrayIterator([$metadata]);
        $itemId   = 666;
        $itemType = 'some-type';

        $item->expects(static::once())
            ->method('getId')
            ->willReturn($itemId);
        $item->expects(static::once())
            ->method('getMetadataItemType')
            ->willReturn($itemType);

        $this->configContainer->expects(static::once())
            ->method('isFeatureEnabled')
            ->with(ConfigurationKeyEnum::ENABLE_CUSTOM_METADATA)
            ->willReturn(true);

        $this->metadataRepository->expects(static::once())
            ->method('findByObjectIdAndType')
            ->with($itemId, $itemType)
            ->willReturn($result);

        static::assertSame(
            [$metadata],
            iterator_to_array($this->subject->getMetadata($item))
        );
    }

    public function testDeleteMetadataDeletes(): void
    {
        $item = $this->createMock(Metadata::class);

        $this->metadataRepository->expects(static::once())
            ->method('remove')
            ->with($item);

        $this->subject->deleteMetadata($item);
    }

    public function testGetDisabledMetadataFieldsReturnsData(): void
    {
        $fieldId1 = 666;
        $fieldId2 = 42;
        $name1    = 'some-name1';
        $name2    = 'some-name2';
        $name3    = 'some-name3';

        $field = $this->createMock(MetadataField::class);

        $this->configContainer->expects(static::exactly(2))
            ->method('get')
            ->with(...self::withConsecutive(
                [ConfigurationKeyEnum::DISABLED_CUSTOM_METADATA_FIELDS],
                [ConfigurationKeyEnum::DISABLED_CUSTOM_METADATA_FIELDS_INPUT],
            ))
            ->willReturn(
                $fieldId1 . ',' . $fieldId2,
                $name2 . ',' . $name3,
            );

        $this->metadataFieldRepository->expects(static::exactly(2))
            ->method('findById')
            ->with(...self::withConsecutive(
                [$fieldId1],
                [$fieldId2]
            ))
            ->willReturn($field, null);

        $field->expects(static::once())
            ->method('getName')
            ->willReturn($name1);

        static::assertSame(
            [$name1, $name2, $name3],
            $this->subject->getDisabledMetadataFields()
        );
        // test caching
        static::assertSame(
            [$name1, $name2, $name3],
            $this->subject->getDisabledMetadataFields()
        );
    }

    public function testAddMetadataCreatesNewItem(): void
    {
        $item          = $this->createMock(MetadataEnabledInterface::class);
        $metadata      = $this->createMock(Metadata::class);
        $metadataField = $this->createMock(MetadataField::class);

        $name     = 'some-name';
        $data     = 'some-data';
        $itemId   = 666;
        $itemType = 'some-type';

        $this->metadataRepository->expects(static::once())
            ->method('prototype')
            ->willReturn($metadata);
        $this->metadataFieldRepository->expects(static::once())
            ->method('findByName')
            ->with($name)
            ->willReturn(null);
        $this->metadataFieldRepository->expects(static::once())
            ->method('prototype')
            ->willReturn($metadataField);

        $item->expects(static::once())
            ->method('getId')
            ->willReturn($itemId);
        $item->expects(static::once())
            ->method('getMetadataItemType')
            ->willReturn($itemType);

        $metadataField->expects(static::once())
            ->method('setName')
            ->with($name);
        $metadataField->expects(static::once())
            ->method('save');

        $metadata->expects(static::once())
            ->method('setField')
            ->with($metadataField)
            ->willReturnSelf();
        $metadata->expects(static::once())
            ->method('setObjectId')
            ->with($itemId)
            ->willReturnSelf();
        $metadata->expects(static::once())
            ->method('setType')
            ->with($itemType)
            ->willReturnSelf();
        $metadata->expects(static::once())
            ->method('setData')
            ->with($data)
            ->willReturnSelf();
        $metadata->expects(static::once())
            ->method('save');

        $this->subject->addMetadata(
            $item,
            $name,
            $data
        );
    }

    public function testUpdateOrAddMetadataAdds(): void
    {
        $item          = $this->createMock(MetadataEnabledInterface::class);
        $metadata      = $this->createMock(Metadata::class);
        $metadataField = $this->createMock(MetadataField::class);

        $name     = 'some-name';
        $data     = 'some-data';
        $itemId   = 666;
        $itemType = 'some-type';

        $item->expects(static::exactly(2))
            ->method('getId')
            ->willReturn($itemId);
        $item->expects(static::exactly(2))
            ->method('getMetadataItemType')
            ->willReturn($itemType);

        $this->metadataFieldRepository->expects(static::exactly(2))
            ->method('findByName')
            ->with($name)
            ->willReturn($metadataField);

        $this->metadataRepository->expects(static::once())
            ->method('findByObjectIdAndFieldAndType')
            ->with($itemId, $metadataField, $itemType)
            ->willReturn(null);
        $this->metadataRepository->expects(static::once())
            ->method('prototype')
            ->willReturn($metadata);

        $metadata->expects(static::once())
            ->method('setField')
            ->with($metadataField)
            ->willReturnSelf();
        $metadata->expects(static::once())
            ->method('setObjectId')
            ->with($itemId)
            ->willReturnSelf();
        $metadata->expects(static::once())
            ->method('setType')
            ->with($itemType)
            ->willReturnSelf();
        $metadata->expects(static::once())
            ->method('setData')
            ->with($data)
            ->willReturnSelf();
        $metadata->expects(static::once())
            ->method('save');

        $this->subject->updateOrAddMetadata($item, $name, $data);
    }

    public function testUpdateOrAddMetadataUpdates(): void
    {
        $item          = $this->createMock(MetadataEnabledInterface::class);
        $metadata      = $this->createMock(Metadata::class);
        $metadataField = $this->createMock(MetadataField::class);

        $name     = 'some-name';
        $data     = 'some-data';
        $itemId   = 666;
        $itemType = 'some-type';

        $item->expects(static::once())
            ->method('getId')
            ->willReturn($itemId);
        $item->expects(static::once())
            ->method('getMetadataItemType')
            ->willReturn($itemType);

        $this->metadataFieldRepository->expects(static::once())
            ->method('findByName')
            ->with($name)
            ->willReturn($metadataField);

        $this->metadataRepository->expects(static::once())
            ->method('findByObjectIdAndFieldAndType')
            ->with($itemId, $metadataField, $itemType)
            ->willReturn($metadata);

        $metadata->expects(static::once())
            ->method('setData')
            ->with($data);
        $metadata->expects(static::once())
            ->method('save');

        $this->subject->updateOrAddMetadata($item, $name, $data);
    }

    public function testCollectGarbageCollects(): void
    {
        $this->metadataRepository->expects(static::once())
            ->method('collectGarbage');

        $this->metadataFieldRepository->expects(static::once())
            ->method('collectGarbage');

        $this->subject->collectGarbage();
    }
}
