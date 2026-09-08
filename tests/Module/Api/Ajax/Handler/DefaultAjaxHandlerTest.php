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
use Ampache\Module\Playback\Tmp_Playlist;
use Ampache\Module\Util\RequestParserInterface;
use Ampache\Module\Util\UiInterface;
use Ampache\Repository\AlbumRepositoryInterface;
use Ampache\Repository\Model\LibraryItemLoaderInterface;
use Ampache\Repository\Model\Playlist;
use Ampache\Repository\Model\User;
use Ampache\Repository\SongRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class DefaultAjaxHandlerTest extends TestCase
{
    private AlbumRepositoryInterface&MockObject $albumRepository;
    private BrowseFactoryInterface&MockObject $browseFactory;
    private LibraryItemLoaderInterface&MockObject $libraryItemLoader;
    private RequestParserInterface&MockObject $requestParser;
    private SongRepositoryInterface&MockObject $songRepository;
    private DefaultAjaxHandler $subject;
    private UiInterface&MockObject $ui;

    public function testBasketExpandsAPrivateListTheViewerCollaboratesOn(): void
    {
        $medias   = [['object_type' => 'song', 'object_id' => 9]];
        $playlist = $this->playlist('private', true);
        $playlist->expects(static::once())
            ->method('get_medias')
            ->willReturn($medias);

        static::assertSame($medias, $this->runBasket($playlist));
    }

    public function testBasketExpandsAPublicList(): void
    {
        $medias   = [['object_type' => 'song', 'object_id' => 7]];
        $playlist = $this->playlist('public', false);
        $playlist->expects(static::once())
            ->method('get_medias')
            ->willReturn($medias);

        static::assertSame($medias, $this->runBasket($playlist));
    }

    public function testBasketRefusesToExpandAPrivateListTheViewerCannotSee(): void
    {
        $playlist = $this->playlist('private', false);
        // the tell that the guard fired: the members are never even read
        $playlist->expects(static::never())
            ->method('get_medias');

        static::assertSame([], $this->runBasket($playlist));
    }

    protected function setUp(): void
    {
        $this->requestParser     = $this->createMock(RequestParserInterface::class);
        $this->albumRepository   = $this->createMock(AlbumRepositoryInterface::class);
        $this->songRepository    = $this->createMock(SongRepositoryInterface::class);
        $this->ui                = $this->createMock(UiInterface::class);
        $this->browseFactory     = $this->createMock(BrowseFactoryInterface::class);
        $this->libraryItemLoader = $this->createMock(LibraryItemLoaderInterface::class);

        $this->subject = new DefaultAjaxHandler(
            $this->requestParser,
            $this->albumRepository,
            $this->songRepository,
            $this->ui,
            $this->browseFactory,
            $this->libraryItemLoader,
        );
    }

    private function playlist(string $type, bool $collaborates): Playlist&MockObject
    {
        $playlist       = $this->createMock(Playlist::class);
        $playlist->type = $type;
        $playlist->method('has_collaborate')
            ->willReturn($collaborates);

        return $playlist;
    }

    /**
     * Runs the basket action for one playlist id and returns what was handed to the queue.
     *
     * @return array<mixed>
     */
    private function runBasket(Playlist&MockObject $playlist): array
    {
        $this->requestParser->method('getFromRequest')
            ->willReturnMap([
                ['action', 'basket'],
                ['type', ''],
                ['object_type', 'playlist'],
                ['id', '42'],
                ['object_id', ''],
            ]);

        $this->libraryItemLoader->method('load')
            ->willReturn($playlist);

        $handed = null;
        $queue  = $this->createMock(Tmp_Playlist::class);
        $queue->method('add_medias')
            ->willReturnCallback(static function (array $medias) use (&$handed): void {
                $handed = $medias;
            });

        $user = $this->createMock(User::class);
        $user->method('getPlaylist')
            ->willReturn($queue);

        ob_start();
        $this->subject->handle($user);
        ob_end_clean();

        return $handed ?? [];
    }
}
