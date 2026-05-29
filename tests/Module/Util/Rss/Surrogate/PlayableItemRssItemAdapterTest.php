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

namespace Ampache\Module\Util\Rss\Surrogate;

use Ampache\Repository\Model\LibraryItemLoaderInterface;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\Model\playable_item;
use Ampache\Repository\Model\User;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class PlayableItemRssItemAdapterTest extends TestCase
{
    private LibraryItemLoaderInterface&MockObject $libraryItemLoader;

    private ModelFactoryInterface&MockObject $modelFactory;

    private playable_item&MockObject $playable;

    private User&MockObject $user;

    private PlayableItemRssItemAdapter $subject;

    protected function setUp(): void
    {
        $this->libraryItemLoader = $this->createMock(LibraryItemLoaderInterface::class);
        $this->modelFactory      = $this->createMock(ModelFactoryInterface::class);
        $this->playable          = $this->createMock(playable_item::class);
        $this->user              = $this->createMock(User::class);

        $this->subject = new PlayableItemRssItemAdapter(
            $this->libraryItemLoader,
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

        self::assertSame(
            $title,
            $this->subject->getTitle()
        );
    }

    public function testHasImageReturnsFalseIfNotAvailable(): void
    {
        $this->playable->expects(static::once())
            ->method('has_art')
            ->willReturn(false);

        self::assertFalse(
            $this->subject->hasImage()
        );
    }

    public function testHasImageReturnsTrueIfAvailable(): void
    {
        $this->playable->expects(static::once())
            ->method('has_art')
            ->willReturn(true);

        self::assertTrue(
            $this->subject->hasImage()
        );
    }

    public function testHasSummaryReturnsTrueIfAvailable(): void
    {
        $this->playable->expects(static::once())
            ->method('get_description')
            ->willReturn('snafu');

        self::assertTrue(
            $this->subject->hasSummary()
        );
    }

    public function testHasSummaryReturnsFalseIfNotAvailable(): void
    {
        $this->playable->expects(static::once())
            ->method('get_description')
            ->willReturn('');

        self::assertFalse(
            $this->subject->hasSummary()
        );
    }

    public function testGetSummaryReturnsValue(): void
    {
        $value = 'snafu';

        $this->playable->expects(static::once())
            ->method('get_description')
            ->willReturn($value);

        self::assertSame(
            $value,
            $this->subject->getSummary()
        );
    }

    public function testHasOwnerReturnsTrueIfAvailable(): void
    {
        $this->playable->expects(static::once())
            ->method('get_user_owner')
            ->willReturn(666);

        self::assertTrue(
            $this->subject->hasOwner()
        );
    }

    public function testHasOwnerReturnsFalseIfNotAvailable(): void
    {
        $this->playable->expects(static::once())
            ->method('get_user_owner')
            ->willReturn(null);

        self::assertFalse(
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

        self::assertSame(
            $name,
            $this->subject->getOwnerName()
        );
    }
}
