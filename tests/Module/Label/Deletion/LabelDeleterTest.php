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

namespace Ampache\Module\Label\Deletion;

use Ampache\Module\Art\ArtCleanupInterface;
use Ampache\Repository\FolderRepositoryInterface;
use Ampache\Repository\LabelRepositoryInterface;
use Ampache\Repository\Model\Label;
use Ampache\Repository\ShoutRepositoryInterface;
use Ampache\Repository\UserActivityRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class LabelDeleterTest extends TestCase
{
    private ArtCleanupInterface&MockObject $artCleanup;
    private FolderRepositoryInterface&MockObject $folderRepository;
    private LabelRepositoryInterface&MockObject $labelRepository;
    private ShoutRepositoryInterface&MockObject $shoutRepository;
    private LabelDeleter $subject;
    private UserActivityRepositoryInterface&MockObject $userActivityRepository;

    public function testDeleteRemovesLabelAndCascadesGarbageCollection(): void
    {
        $label   = $this->createMock(Label::class);
        $labelId = 21;

        $label->method('getId')
            ->willReturn($labelId);

        $this->labelRepository->expects(static::once())
            ->method('delete')
            ->with($labelId);

        $this->artCleanup->expects(static::once())
            ->method('collectGarbageForObject')
            ->with('label', $labelId);

        $this->shoutRepository->expects(static::once())
            ->method('collectGarbage')
            ->with('label', $labelId);

        $this->userActivityRepository->expects(static::once())
            ->method('collectGarbage')
            ->with('label', $labelId);

        $this->folderRepository->expects(static::once())
            ->method('collectGarbage');

        $this->subject->delete($label);
    }

    protected function setUp(): void
    {
        $this->shoutRepository        = $this->createMock(ShoutRepositoryInterface::class);
        $this->labelRepository        = $this->createMock(LabelRepositoryInterface::class);
        $this->userActivityRepository = $this->createMock(UserActivityRepositoryInterface::class);
        $this->artCleanup             = $this->createMock(ArtCleanupInterface::class);
        $this->folderRepository       = $this->createMock(FolderRepositoryInterface::class);

        $this->subject = new LabelDeleter(
            $this->shoutRepository,
            $this->labelRepository,
            $this->userActivityRepository,
            $this->artCleanup,
            $this->folderRepository,
        );
    }
}
