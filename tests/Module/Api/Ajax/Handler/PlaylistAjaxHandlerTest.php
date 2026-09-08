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
 */

namespace Ampache\Module\Api\Ajax\Handler;

use Ampache\Module\Database\Query\BrowseFactoryInterface;
use Ampache\Module\Util\RequestParserInterface;
use Ampache\Repository\Model\LibraryItemEnum;
use Ampache\Repository\Model\LibraryItemLoaderInterface;
use Ampache\Repository\Model\Playlist;
use Ampache\Repository\Model\User;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Appending to a playlist takes the ids the caller names. A private list the caller cannot see is not
 * one of them, or its whole content lands in a playlist they own and can read at leisure.
 */
class PlaylistAjaxHandlerTest extends TestCase
{
    private LibraryItemLoaderInterface&MockObject $libraryItemLoader;
    private RequestParserInterface&MockObject $requestParser;
    private PlaylistAjaxHandler $subject;

    public function testAppendRefusesAPrivateSourceTheCallerCannotSee(): void
    {
        $target = $this->target();
        $source = $this->source('private', false);

        // the tell that the guard fired: the members are never read, and nothing is appended
        $source->expects(static::never())
            ->method('get_medias');
        $target->expects(static::never())
            ->method('add_medias');

        $this->append($target, $source);
    }

    public function testAppendTakesAPrivateSourceTheCallerCollaboratesOn(): void
    {
        $medias = [['object_type' => 'song', 'object_id' => 3]];
        $target = $this->target();
        $source = $this->source('private', true);

        $source->method('get_medias')
            ->willReturn($medias);
        $target->expects(static::once())
            ->method('add_medias')
            ->with($medias)
            ->willReturn(true);

        $this->append($target, $source);
    }

    public function testAppendTakesAPublicSource(): void
    {
        $medias = [['object_type' => 'song', 'object_id' => 5]];
        $target = $this->target();
        $source = $this->source('public', false);

        $source->method('get_medias')
            ->willReturn($medias);
        $target->expects(static::once())
            ->method('add_medias')
            ->with($medias)
            ->willReturn(true);

        $this->append($target, $source);
    }

    protected function setUp(): void
    {
        $this->requestParser     = $this->createMock(RequestParserInterface::class);
        $this->libraryItemLoader = $this->createMock(LibraryItemLoaderInterface::class);

        $this->subject = new PlaylistAjaxHandler(
            $this->requestParser,
            $this->createMock(BrowseFactoryInterface::class),
            $this->libraryItemLoader,
        );

        $this->requestParser->method('getFromRequest')
            ->willReturn('append_item');

        $_REQUEST['playlist_id'] = '10';
        $_REQUEST['item_type']   = 'playlist';
        $_REQUEST['item_id']     = '42';
    }

    protected function tearDown(): void
    {
        unset($_REQUEST['playlist_id'], $_REQUEST['item_type'], $_REQUEST['item_id']);
    }

    private function append(Playlist&MockObject $target, Playlist&MockObject $source): void
    {
        $this->libraryItemLoader->method('load')
            ->willReturnCallback(
                static fn(LibraryItemEnum $type, int $id): ?Playlist => ($id === 10) ? $target : $source
            );

        ob_start();
        $this->subject->handle($this->createMock(User::class));
        ob_end_clean();
    }

    private function source(string $type, bool $collaborates): Playlist&MockObject
    {
        $source       = $this->createMock(Playlist::class);
        $source->type = $type;
        $source->method('has_collaborate')
            ->willReturn($collaborates);

        return $source;
    }

    private function target(): Playlist&MockObject
    {
        $target     = $this->createMock(Playlist::class);
        $target->id = 10;
        $target->method('has_collaborate')
            ->willReturn(true);

        return $target;
    }
}
