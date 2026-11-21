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

namespace Ampache\Module\Application\Stream;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\Statistics\Stats;
use Ampache\Module\User\Activity\UserActivityPosterInterface;
use Ampache\Repository\AlbumRepositoryInterface;
use Ampache\Repository\ArtistRepositoryInterface;
use Ampache\Repository\Model\LibraryItemEnum;
use Ampache\Repository\Model\LibraryItemLoaderInterface;
use Ampache\Repository\Model\Playlist;
use Ampache\Repository\Model\User;
use Ampache\Repository\PodcastEpisodeRepositoryInterface;
use Ampache\Repository\PodcastRepositoryInterface;
use Ampache\Repository\SongRepositoryInterface;
use Ampache\Repository\UserActivityRepositoryInterface;
use Ampache\Repository\VideoRepositoryInterface;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;

class StreamItemActionTest extends TestCase
{
    // a playlist id is a plain request value, so expanding a private one streams someone else's list
    public function testAPrivatePlaylistIsNotExpanded(): void
    {
        $playlist = $this->playlist(false);

        $playlist->expects(self::never())
            ->method('get_medias');

        $this->stream($playlist);
    }

    public function testAVisiblePlaylistIsExpanded(): void
    {
        $playlist = $this->playlist(true);

        $playlist->expects(self::once())
            ->method('get_medias')
            ->willReturn([]);

        $this->stream($playlist);
    }

    protected function tearDown(): void
    {
        unset($_GET['object_id']);
    }

    private function playlist(bool $visible): Playlist
    {
        $playlist = $this->createMock(Playlist::class);
        $playlist->method('isVisible')->willReturn($visible);

        return $playlist;
    }

    private function stream(Playlist $playlist): void
    {
        $config = $this->createMock(ConfigContainerInterface::class);
        $config->method('get')->willReturn('');

        $_GET['object_id'] = '42';

        $loader = $this->createMock(LibraryItemLoaderInterface::class);
        $loader->method('load')
            ->with(LibraryItemEnum::PLAYLIST, 42)
            ->willReturn($playlist);

        $subject = new StreamItemAction(
            $this->createMock(LoggerInterface::class),
            $config,
            $loader,
            new Stats(
                $this->createMock(AlbumRepositoryInterface::class),
                $this->createMock(ArtistRepositoryInterface::class),
                $this->createMock(PodcastEpisodeRepositoryInterface::class),
                $this->createMock(PodcastRepositoryInterface::class),
                $this->createMock(SongRepositoryInterface::class),
                $this->createMock(UserActivityPosterInterface::class),
                $this->createMock(UserActivityRepositoryInterface::class),
                $this->createMock(VideoRepositoryInterface::class),
                $this->createMock(LoggerInterface::class),
            ),
        );

        $gatekeeper = $this->createMock(GuiGatekeeperInterface::class);
        $gatekeeper->method('getUser')->willReturn($this->createMock(User::class));

        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getQueryParams')->willReturn(['object_type' => 'playlist']);

        self::assertNull($subject->run($request, $gatekeeper));
    }
}
