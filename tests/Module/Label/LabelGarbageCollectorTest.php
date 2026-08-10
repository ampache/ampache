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
 *
 */

namespace Ampache\Module\Label;

use Ampache\Module\Label\Deletion\LabelDeleterInterface;
use Ampache\Repository\LabelRepositoryInterface;
use Ampache\Repository\Model\Label;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class LabelGarbageCollectorTest extends TestCase
{
    private LabelDeleterInterface&MockObject $labelDeleter;
    private LabelNameFilterInterface&MockObject $labelNameFilter;
    private LabelRepositoryInterface&MockObject $labelRepository;
    private LabelGarbageCollector $subject;

    public function testCollectDeletesOnlyThePlaceholderLabels(): void
    {
        $placeholder       = new Label();
        $placeholder->id   = 5;
        $placeholder->user = 0;

        $this->labelRepository->expects(static::once())
            ->method('getAll')
            ->willReturn([5 => '[no label]', 9 => 'Warp Records']);

        $this->labelNameFilter->method('isIgnored')
            ->willReturnMap([
                ['[no label]', true],
                ['Warp Records', false],
            ]);

        $this->labelRepository->expects(static::once())
            ->method('findById')
            ->with(5)
            ->willReturn($placeholder);

        $this->labelDeleter->expects(static::once())
            ->method('delete')
            ->with($placeholder);

        $this->subject->collect();
    }

    public function testCollectKeepsAPlaceholderNameAUserCreatedByHand(): void
    {
        $owned       = new Label();
        $owned->id   = 5;
        $owned->user = 42;

        $this->labelRepository->expects(static::once())
            ->method('getAll')
            ->willReturn([5 => '[no label]']);

        $this->labelNameFilter->method('isIgnored')
            ->willReturn(true);

        $this->labelRepository->expects(static::once())
            ->method('findById')
            ->with(5)
            ->willReturn($owned);

        // removing what somebody deliberately entered is not this sweep's job
        $this->labelDeleter->expects(static::never())
            ->method('delete');

        $this->subject->collect();
    }

    public function testCollectSkipsALabelThatVanishedBeforeItCouldBeLoaded(): void
    {
        $this->labelRepository->expects(static::once())
            ->method('getAll')
            ->willReturn([5 => '[no label]']);

        $this->labelNameFilter->method('isIgnored')
            ->willReturn(true);

        $this->labelRepository->expects(static::once())
            ->method('findById')
            ->with(5)
            ->willReturn(null);

        $this->labelDeleter->expects(static::never())
            ->method('delete');

        $this->subject->collect();
    }

    protected function setUp(): void
    {
        $this->labelRepository = $this->createMock(LabelRepositoryInterface::class);
        $this->labelNameFilter = $this->createMock(LabelNameFilterInterface::class);
        $this->labelDeleter    = $this->createMock(LabelDeleterInterface::class);

        $this->subject = new LabelGarbageCollector(
            $this->labelRepository,
            $this->labelNameFilter,
            $this->labelDeleter,
        );
    }
}
