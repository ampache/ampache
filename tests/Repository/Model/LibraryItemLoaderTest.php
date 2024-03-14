<?php

declare(strict_types=1);

/**
 * vim:set softtabstop=3 shiftwidth=4 expandtab:
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

use Ampache\Repository\LabelRepositoryInterface;
use Ampache\Repository\LiveStreamRepositoryInterface;
use Ampache\Repository\PodcastRepositoryInterface;
use Generator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

class LibraryItemLoaderTest extends TestCase
{
    private ContainerInterface&MockObject $dic;

    private LibraryItemLoader $subject;

    protected function setUp(): void
    {
        $this->dic = $this->createMock(ContainerInterface::class);

        $this->subject = new LibraryItemLoader(
            $this->dic,
        );
    }

    #[DataProvider(methodName: 'loadDataProvider')]
    public function testLoadLoads(
        LibraryItemEnum $itemType,
        string $repoClassName,
        string $itemClassName
    ): void {
        $objectId = 666;

        $item = $this->createMock($itemClassName);
        $repo = $this->createMock($repoClassName);

        $item->expects(static::once())
            ->method('isNew')
            ->willReturn(false);

        $this->dic->expects(static::once())
            ->method('get')
            ->with($repoClassName)
            ->willReturn($repo);

        $repo->expects(static::once())
            ->method('findById')
            ->with($objectId)
            ->willReturn($item);

        static::assertSame(
            $item,
            $this->subject->load($itemType, $objectId, [$itemClassName]),
        );
    }

    public static function loadDataProvider(): Generator
    {
        yield [LibraryItemEnum::LABEL, LabelRepositoryInterface::class, Label::class];

        yield [LibraryItemEnum::LIVE_STREAM, LiveStreamRepositoryInterface::class, Live_Stream::class];

        yield [LibraryItemEnum::PODCAST, PodcastRepositoryInterface::class, Podcast::class];
    }

    public function testReturnsNullIfObjectWasNotFound(): void
    {
        $objectId = 666;

        $item = $this->createMock(Label::class);
        $repo = $this->createMock(LabelRepositoryInterface::class);

        $item->expects(static::once())
            ->method('isNew')
            ->willReturn(true);

        $this->dic->expects(static::once())
            ->method('get')
            ->with(LabelRepositoryInterface::class)
            ->willReturn($repo);

        $repo->expects(static::once())
            ->method('findById')
            ->with($objectId)
            ->willReturn($item);

        static::assertNull(
            $this->subject->load(LibraryItemEnum::LABEL, $objectId),
        );
    }
}
