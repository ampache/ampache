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

namespace Ampache\Module\Api\Ajax\Handler;

use Ampache\Module\Database\Query\BrowseFactoryInterface;
use Ampache\Module\Database\Query\Smartlist;
use Ampache\Module\Util\RequestParserInterface;
use Ampache\Repository\CollectionRepositoryInterface;
use Ampache\Repository\Model\Collection;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\Model\User;
use PHPUnit\Framework\TestCase;

class CollectionAjaxHandlerTest extends TestCase
{
    private Smartlist $smartlist;

    // a smartlist id is a plain request value, so resolving a private one copies someone else's songs
    public function testAppendingAPrivateSmartlistAddsNothing(): void
    {
        $collection = $this->appendSmartlist(false);

        $collection->expects(self::never())
            ->method('add_item');

        $this->handle($collection);
    }

    public function testAppendingAVisibleSmartlistAddsItsSongs(): void
    {
        $collection = $this->appendSmartlist(true);

        $collection->expects(self::once())
            ->method('add_item')
            ->with(7, 'song')
            ->willReturn(false);

        $this->handle($collection);
    }

    private function appendSmartlist(bool $visible): Collection
    {
        $this->smartlist = $this->createMock(Smartlist::class);
        $this->smartlist->method('isNew')->willReturn(false);
        $this->smartlist->method('isVisible')->willReturn($visible);
        $this->smartlist->method('get_medias')->willReturn([['object_id' => 7, 'object_type' => 'song']]);

        $collection = $this->createMock(Collection::class);
        $collection->method('has_collaborate')->willReturn(true);
        $collection->method('acceptsType')->willReturn(true);

        return $collection;
    }

    private function handle(Collection $collection): void
    {
        $requestParser = $this->createMock(RequestParserInterface::class);
        $requestParser->method('getFromRequest')->willReturnMap([
            ['action', 'append_item'],
            ['collection_id', '3'],
            ['item_type', 'search'],
            ['item_id', '9'],
        ]);
        $collectionRepository = $this->createMock(CollectionRepositoryInterface::class);
        $collectionRepository->method('findById')->willReturn($collection);
        $collectionRepository->method('objectExists')->willReturn(true);
        $modelFactory = $this->createMock(ModelFactoryInterface::class);
        $modelFactory->method('createSmartlist')->with(9)->willReturn($this->smartlist);

        $subject = new CollectionAjaxHandler(
            $requestParser,
            $collectionRepository,
            $this->createMock(BrowseFactoryInterface::class),
            $modelFactory,
        );

        ob_start();
        $subject->handle($this->createMock(User::class));
        ob_end_clean();
    }
}
