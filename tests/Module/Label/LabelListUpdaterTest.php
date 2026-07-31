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

use Ampache\Repository\LabelRepositoryInterface;
use Ampache\Repository\Model\Label;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class LabelListUpdaterTest extends TestCase
{
    private LabelRepositoryInterface&MockObject $labelRepository;
    private LabelListUpdater $subject;

    public function testUpdateAddsNewlyResolvableLabel(): void
    {
        $artistId = 21;
        $labelId  = 5;
        $label    = $this->createMock(Label::class);

        $this->labelRepository->expects(static::once())
            ->method('getByArtist')
            ->with($artistId)
            ->willReturn([]);

        $this->labelRepository->expects(static::once())
            ->method('lookup')
            ->with('New Label')
            ->willReturn($labelId);

        $this->labelRepository->expects(static::once())
            ->method('findById')
            ->with($labelId)
            ->willReturn($label);

        $label->method('getId')
            ->willReturn($labelId);

        $this->labelRepository->expects(static::once())
            ->method('addArtistAssoc')
            ->with($labelId, $artistId, static::isInstanceOf(\DateTime::class));

        $result = $this->subject->update('New Label', $artistId, false);

        static::assertTrue($result);
    }

    public function testUpdateDoesNotAddLabelThatCannotBeResolved(): void
    {
        $artistId = 21;

        $this->labelRepository->expects(static::once())
            ->method('getByArtist')
            ->with($artistId)
            ->willReturn([]);

        $this->labelRepository->expects(static::once())
            ->method('lookup')
            ->with('New Label')
            ->willReturn(0);

        $this->labelRepository->expects(static::never())
            ->method('addArtistAssoc');

        $result = $this->subject->update('New Label', $artistId, false);

        static::assertTrue($result);
    }

    public function testUpdateDoesNotRemoveLabelNotInNewListWithoutOverwrite(): void
    {
        $artistId            = 21;
        $existingId          = 1;
        $existingLabel       = $this->createMock(Label::class);
        $existingLabel->name = 'Old Label';

        $this->labelRepository->expects(static::once())
            ->method('getByArtist')
            ->with($artistId)
            ->willReturn([$existingId => 'Old Label']);

        $this->labelRepository->expects(static::once())
            ->method('findById')
            ->with($existingId)
            ->willReturn($existingLabel);

        $this->labelRepository->expects(static::never())
            ->method('removeArtistAssoc');

        $result = $this->subject->update('New Label', $artistId, false);

        static::assertTrue($result);
    }

    public function testUpdateKeepsExistingLabelStillPresentInNewList(): void
    {
        $artistId            = 21;
        $existingId          = 1;
        $existingLabel       = $this->createMock(Label::class);
        $existingLabel->name = 'Existing Label';

        $this->labelRepository->expects(static::once())
            ->method('getByArtist')
            ->with($artistId)
            ->willReturn([$existingId => 'Existing Label']);

        $this->labelRepository->expects(static::once())
            ->method('findById')
            ->with($existingId)
            ->willReturn($existingLabel);

        $this->labelRepository->expects(static::never())
            ->method('removeArtistAssoc');

        $this->labelRepository->expects(static::never())
            ->method('lookup');

        $result = $this->subject->update('Existing Label', $artistId, false);

        static::assertTrue($result);
    }

    public function testUpdateRemovesLabelNotInNewListWhenOverwriting(): void
    {
        $artistId            = 21;
        $existingId          = 1;
        $existingLabel       = $this->createMock(Label::class);
        $existingLabel->name = 'Old Label';

        $this->labelRepository->expects(static::once())
            ->method('getByArtist')
            ->with($artistId)
            ->willReturn([$existingId => 'Old Label']);

        $this->labelRepository->expects(static::once())
            ->method('findById')
            ->with($existingId)
            ->willReturn($existingLabel);

        $existingLabel->method('getId')
            ->willReturn($existingId);

        $this->labelRepository->expects(static::once())
            ->method('removeArtistAssoc')
            ->with($existingId, $artistId);

        $result = $this->subject->update('New Label', $artistId, true);

        static::assertTrue($result);
    }

    protected function setUp(): void
    {
        $this->labelRepository = $this->createMock(LabelRepositoryInterface::class);

        $this->subject = new LabelListUpdater($this->labelRepository);
    }
}
