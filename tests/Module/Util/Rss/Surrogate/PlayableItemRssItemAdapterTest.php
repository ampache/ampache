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

namespace Ampache\Module\Util\Rss\Surrogate;

use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\Model\playable_item;
use Ampache\Repository\Model\User;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class PlayableItemRssItemAdapterTest extends TestCase
{
    private ModelFactoryInterface&MockObject $modelFactory;

    private playable_item&MockObject $playable;

    private User&MockObject $user;

    private PlayableItemRssItemAdapter $subject;

    protected function setUp(): void
    {
        $this->modelFactory = $this->createMock(ModelFactoryInterface::class);
        $this->playable     = $this->createMock(playable_item::class);
        $this->user         = $this->createMock(User::class);

        $this->subject = new PlayableItemRssItemAdapter(
            $this->modelFactory,
            $this->playable,
            $this->user,
        );
    }

    public function testGetTitleReturnsValue(): void
    {
        $title = 'some-title';

        $this->playable->expects(static::once())
            ->method('get_fullname')
            ->willReturn($title);

        static::assertSame(
            sprintf('%s Podcast', $title),
            $this->subject->getTitle()
        );
    }

    public function testHasImageReturnsFalseIfNotAvailable(): void
    {
        $this->playable->expects(static::once())
            ->method('has_art')
            ->willReturn(false);

        static::assertFalse(
            $this->subject->hasImage()
        );
    }

    public function testHasImageReturnsTrueIfAvailable(): void
    {
        $this->playable->expects(static::once())
            ->method('has_art')
            ->willReturn(true);

        static::assertTrue(
            $this->subject->hasImage()
        );
    }

    public function testHasSummaryReturnsTrueIfAvailable(): void
    {
        $this->playable->expects(static::once())
            ->method('get_description')
            ->willReturn('snafu');

        static::assertTrue(
            $this->subject->hasSummary()
        );
    }

    public function testHasSummaryReturnsFalseIfNotAvailable(): void
    {
        $this->playable->expects(static::once())
            ->method('get_description')
            ->willReturn('');

        static::assertFalse(
            $this->subject->hasSummary()
        );
    }

    public function testGetSummaryReturnsValue(): void
    {
        $value = 'snafu';

        $this->playable->expects(static::once())
            ->method('get_description')
            ->willReturn($value);

        static::assertSame(
            $value,
            $this->subject->getSummary()
        );
    }

    public function testHasOwnerReturnsTrueIfAvailable(): void
    {
        $this->playable->expects(static::once())
            ->method('get_user_owner')
            ->willReturn(666);

        static::assertTrue(
            $this->subject->hasOwner()
        );
    }

    public function testHasOwnerReturnsFalseIfNotAvailable(): void
    {
        $this->playable->expects(static::once())
            ->method('get_user_owner')
            ->willReturn(null);

        static::assertFalse(
            $this->subject->hasOwner()
        );
    }

    public function testGetOwnerNameReturnsValue(): void
    {
        $user = $this->createMock(User::class);

        $userId = 666;
        $name   = 'some-name';

        $this->playable->expects(static::once())
            ->method('get_user_owner')
            ->willReturn($userId);

        $this->modelFactory->expects(static::once())
            ->method('createUser')
            ->with($userId)
            ->willReturn($user);

        $user->expects(static::once())
            ->method('get_fullname')
            ->willReturn($name);

        static::assertSame(
            $name,
            $this->subject->getOwnerName()
        );
    }
}
