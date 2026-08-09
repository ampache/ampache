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

namespace Ampache\Module\Util\Rss\Type;

use Ampache\Module\Util\Rss\View\PodcastRssFeedView;
use Ampache\Repository\Model\library_item;
use Ampache\Repository\Model\LibraryItemLoaderInterface;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\Model\User;
use PHPUnit\Framework\TestCase;

class LibraryItemFeedTest extends TestCase
{
    private LibraryItemFeed $subject;

    public function testCreateViewReturnsThePodcastChannelView(): void
    {
        self::assertInstanceOf(
            PodcastRssFeedView::class,
            $this->subject->createView()
        );
    }

    protected function setUp(): void
    {
        $this->subject = new LibraryItemFeed(
            $this->createMock(ModelFactoryInterface::class),
            $this->createMock(LibraryItemLoaderInterface::class),
            $this->createMock(User::class),
            $this->createMock(library_item::class)
        );
    }
}
