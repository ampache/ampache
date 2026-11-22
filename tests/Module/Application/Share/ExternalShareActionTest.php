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

namespace Ampache\Module\Application\Share;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Module\Application\Exception\AccessDeniedException;
use Ampache\Module\Authorization\Check\FunctionCheckerInterface;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\Share\ShareCreatorInterface;
use Ampache\Module\User\PasswordGeneratorInterface;
use Ampache\Module\Util\RequestParserInterface;
use Ampache\Plugin\AmpacheMastodon;
use Ampache\Repository\Model\Album;
use Ampache\Repository\Model\AlbumDisk;
use Ampache\Repository\Model\Artist;
use Ampache\Repository\Model\LibraryItemEnum;
use Ampache\Repository\Model\LibraryItemLoaderInterface;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\Model\Playlist;
use Ampache\Repository\Model\Song;
use Ampache\Repository\Model\User;
use Ampache\Repository\Model\Video;
use DI\FactoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;

/**
 * On an instance without logins the page is public, so what goes to the network is the page itself.
 * The viewer gets no more through this door than the page would give: a hidden item is refused here too.
 */
class ExternalShareActionTest extends TestCase
{
    private ConfigContainerInterface&MockObject $configContainer;
    private GuiGatekeeperInterface&MockObject $gatekeeper;
    private LibraryItemLoaderInterface&MockObject $libraryItemLoader;
    private ModelFactoryInterface&MockObject $modelFactory;
    private AmpacheMastodon&MockObject $plugin;
    private ServerRequestInterface&MockObject $request;
    private RequestParserInterface&MockObject $requestParser;
    private ResponseInterface&MockObject $response;
    private ShareCreatorInterface&MockObject $shareCreator;
    private ExternalShareAction $subject;
    private User&MockObject $user;

    public function testADiskOutsideTheVisitorsCatalogsIsRefused(): void
    {
        $this->publicInstance();
        $this->request('album_disk', 5);

        $disk          = $this->createMock(AlbumDisk::class);
        $disk->catalog = 9;
        $disk->method('isVisible')
            ->willReturn(true);

        $this->libraryItemLoader->method('load')
            ->willReturn($disk);

        $this->expectRefusal($disk);
    }

    public function testAMadeUpTypeIsRefusedRatherThanFailing(): void
    {
        $this->publicInstance();
        $this->request('nonsense', 2);

        $this->libraryItemLoader->expects(static::never())
            ->method('load');

        static::expectException(AccessDeniedException::class);

        $this->subject->run($this->request, $this->gatekeeper);
    }

    // an album is named as one, so the reader knows the link holds more than a song
    public function testAnAlbumSaysItIsOne(): void
    {
        $this->publicInstance();
        $this->request('album', 4);

        $album          = $this->createMock(Album::class);
        $album->catalog = 3;
        $album->method('isVisible')
            ->willReturn(true);
        $album->method('get_fullname')
            ->willReturn('Curses');
        $album->method('get_parent_fullname')
            ->willReturn('ProleteR');
        $album->method('get_link')
            ->willReturn('https://music.example/albums.php?action=show&album=4');

        $this->libraryItemLoader->method('load')
            ->willReturn($album);

        $this->plugin->expects(static::once())
            ->method('external_share')
            ->with('https://music.example/albums.php?action=show&album=4', "I'm listening to the album Curses by ProleteR on Music")
            ->willReturn('https://social.example/share');
        $this->response->method('withHeader')
            ->willReturnSelf();

        $this->subject->run($this->request, $this->gatekeeper);
    }

    public function testAnOrphanedAlbumIsShownLikeItsPage(): void
    {
        $this->publicInstance();
        $this->request('album', 4);

        $album          = $this->createMock(Album::class);
        $album->catalog = 0;
        $album->method('isVisible')
            ->willReturn(true);
        $album->method('get_link')
            ->willReturn('https://music.example/albums.php?action=show&album=4');
        $album->method('get_fullname')
            ->willReturn('Orphans');

        $this->libraryItemLoader->method('load')
            ->willReturn($album);

        $this->plugin->expects(static::once())
            ->method('external_share')
            ->with('https://music.example/albums.php?action=show&album=4', "I'm listening to Orphans on Music")
            ->willReturn('https://social.example/share');
        $this->response->method('withHeader')
            ->willReturnSelf();

        $this->subject->run($this->request, $this->gatekeeper);
    }

    public function testAnUnknownOrUnshareableObjectIsRefused(): void
    {
        $this->publicInstance();
        $this->request('podcast', 2);

        $this->libraryItemLoader->method('load')
            ->willReturn(null);

        $this->plugin->expects(static::never())
            ->method('external_share');

        static::expectException(AccessDeniedException::class);
        static::expectExceptionMessage('Access Denied - Unknown or hidden object');

        $this->subject->run($this->request, $this->gatekeeper);
    }

    public function testAnUnknownPluginIsRefusedBeforeAnythingIsLoaded(): void
    {
        $this->publicInstance();
        $_GET['plugin'] = 'nope';

        $this->libraryItemLoader->expects(static::never())
            ->method('load');

        static::expectException(AccessDeniedException::class);
        static::expectExceptionMessage('Access Denied - Unknown external share plugin');

        $this->subject->run($this->request, $this->gatekeeper);
    }

    public function testAPlaylistSaysItIsOne(): void
    {
        $this->publicInstance();
        $this->request('playlist', 1);

        $playlist = $this->createMock(Playlist::class);
        $playlist->method('isVisible')
            ->willReturn(true);
        $playlist->method('get_fullname')
            ->willReturn('Electro cool');
        $playlist->method('get_link')
            ->willReturn('https://music.example/playlist.php?action=show&playlist_id=1');

        $this->libraryItemLoader->method('load')
            ->willReturn($playlist);

        $this->plugin->expects(static::once())
            ->method('external_share')
            ->with('https://music.example/playlist.php?action=show&playlist_id=1', "I'm listening to the playlist Electro cool on Music")
            ->willReturn('https://social.example/share');
        $this->response->method('withHeader')
            ->willReturnSelf();

        $this->subject->run($this->request, $this->gatekeeper);
    }

    public function testAPrivateInstanceRefusesAVisitorWithoutAnAccount(): void
    {
        $this->configContainer->method('isFeatureEnabled')
            ->willReturnMap([
                [ConfigurationKeyEnum::SHARE, true],
                [ConfigurationKeyEnum::DEMO_MODE, false],
            ]);
        $this->configContainer->method('isAuthenticationEnabled')
            ->willReturn(true);
        $this->gatekeeper->method('getUser')
            ->willReturn(null);
        $this->request('album', 4);

        $this->shareCreator->expects(static::never())
            ->method('create');

        static::expectException(AccessDeniedException::class);

        $this->subject->run($this->request, $this->gatekeeper);
    }

    public function testAPrivateInstanceStillGoesThroughAShare(): void
    {
        $this->configContainer->method('isFeatureEnabled')
            ->willReturnMap([
                [ConfigurationKeyEnum::SHARE, true],
                [ConfigurationKeyEnum::DEMO_MODE, false],
            ]);
        $this->configContainer->method('isAuthenticationEnabled')
            ->willReturn(true);
        $this->gatekeeper->method('getUser')
            ->willReturn($this->user);
        $this->request('album', 4);

        $this->libraryItemLoader->expects(static::never())
            ->method('load');

        // a failed creation refuses, which is the one outcome reachable without a database behind the share
        $this->shareCreator->expects(static::once())
            ->method('create')
            ->with($this->user, LibraryItemEnum::ALBUM, 4)
            ->willReturn(null);

        static::expectException(AccessDeniedException::class);
        static::expectExceptionMessage('Access Denied - Unable to create share');

        $this->subject->run($this->request, $this->gatekeeper);
    }

    public function testAPrivateListIsRefused(): void
    {
        $this->publicInstance();
        $this->request('playlist', 12);

        $playlist = $this->createMock(Playlist::class);
        $playlist->method('isVisible')
            ->with($this->user)
            ->willReturn(false);

        $this->libraryItemLoader->method('load')
            ->willReturn($playlist);

        $this->expectRefusal($playlist);
    }

    public function testAPublicInstanceHandsThePageItselfToTheNetwork(): void
    {
        $this->publicInstance();
        $this->request('song', 7);

        $song          = $this->createMock(Song::class);
        $song->catalog = 3;
        $song->method('isVisible')
            ->with($this->user)
            ->willReturn(true);
        $song->method('get_link')
            ->willReturn('https://music.example/song.php?action=show_song&song_id=7');
        $song->method('get_fullname')
            ->willReturn('Some song');
        $song->method('get_parent_fullname')
            ->willReturn('Some artist');

        $this->libraryItemLoader->expects(static::once())
            ->method('load')
            ->with(LibraryItemEnum::SONG, 7, [Song::class, Album::class, AlbumDisk::class, Artist::class, Playlist::class, Video::class])
            ->willReturn($song);

        // the tell: no share row is ever written for a visitor
        $this->shareCreator->expects(static::never())
            ->method('create');

        $this->plugin->expects(static::once())
            ->method('load')
            ->with($this->user);
        $this->plugin->expects(static::once())
            ->method('external_share')
            ->with('https://music.example/song.php?action=show_song&song_id=7', "I'm listening to Some song by Some artist on Music")
            ->willReturn('https://social.example/share?u=...');

        $this->response->expects(static::once())
            ->method('withHeader')
            ->with('Location', 'https://social.example/share?u=...')
            ->willReturnSelf();

        self::assertSame($this->response, $this->subject->run($this->request, $this->gatekeeper));
    }

    public function testASongOutsideTheVisitorsCatalogsIsRefused(): void
    {
        $this->publicInstance();
        $this->request('song', 7);

        $song          = $this->createMock(Song::class);
        $song->catalog = 9;
        $song->method('isVisible')
            ->willReturn(true);

        $this->libraryItemLoader->method('load')
            ->willReturn($song);

        $this->expectRefusal($song);
    }

    // its page shows any video that exists, so the permalink does the same and no more
    public function testAVideoIsSharedLikeItsPage(): void
    {
        $this->publicInstance();
        $this->request('video', 8);

        $video = $this->createMock(Video::class);
        $video->method('get_link')
            ->willReturn('https://music.example/video.php?action=show_video&video_id=8');
        $video->method('get_fullname')
            ->willReturn('Clip');

        $this->libraryItemLoader->method('load')
            ->willReturn($video);

        $this->plugin->expects(static::once())
            ->method('external_share')
            ->with('https://music.example/video.php?action=show_video&video_id=8', "I'm watching Clip on Music")
            ->willReturn('https://social.example/share');
        $this->response->method('withHeader')
            ->willReturnSelf();

        $this->subject->run($this->request, $this->gatekeeper);
    }

    public function testAWithdrawnAlbumIsRefused(): void
    {
        $this->publicInstance();
        $this->request('album', 4);

        $album          = $this->createMock(Album::class);
        $album->catalog = 3;
        $album->method('isVisible')
            ->with($this->user)
            ->willReturn(false);

        $this->libraryItemLoader->method('load')
            ->willReturn($album);

        $this->expectRefusal($album);
    }

    public function testAWithdrawnArtistIsRefused(): void
    {
        $this->publicInstance();
        $this->request('artist', 6);

        $artist = $this->createMock(Artist::class);
        $artist->method('isVisible')
            ->with($this->user)
            ->willReturn(false);

        $this->libraryItemLoader->method('load')
            ->willReturn($artist);

        $this->expectRefusal($artist);
    }

    // an instance with no site title still posts a whole sentence, never a dangling "on" with nothing after it
    protected function setUp(): void
    {
        $this->requestParser     = $this->createMock(RequestParserInterface::class);
        $this->configContainer   = $this->createMock(ConfigContainerInterface::class);
        $this->shareCreator      = $this->createMock(ShareCreatorInterface::class);
        $this->libraryItemLoader = $this->createMock(LibraryItemLoaderInterface::class);
        $this->modelFactory      = $this->createMock(ModelFactoryInterface::class);
        $this->response          = $this->createMock(ResponseInterface::class);
        $this->plugin            = $this->createMock(AmpacheMastodon::class);
        $this->user              = $this->createMock(User::class);
        $this->request           = $this->createMock(ServerRequestInterface::class);
        $this->gatekeeper        = $this->createMock(GuiGatekeeperInterface::class);

        $responseFactory = $this->createMock(ResponseFactoryInterface::class);
        $responseFactory->method('createResponse')
            ->with(302)
            ->willReturn($this->response);

        $this->subject = new ExternalShareAction(
            $this->requestParser,
            $this->configContainer,
            $this->createMock(PasswordGeneratorInterface::class),
            $responseFactory,
            $this->createMock(FunctionCheckerInterface::class),
            $this->shareCreator,
            $this->libraryItemLoader,
            $this->modelFactory
        );

        // `new Plugin()` builds its controller from the global container, so the mastodon plugin is this mock
        $dic = $this->createMockForIntersectionOfInterfaces([ContainerInterface::class, FactoryInterface::class]);
        $dic->method('make')
            ->with(AmpacheMastodon::class)
            ->willReturn($this->plugin);
        $dic->method('get')
            ->with(LoggerInterface::class)
            ->willReturn($this->createMock(LoggerInterface::class));
        $GLOBALS['dic'] = $dic;
        $_GET['plugin'] = 'mastodon';
    }

    /**
     * A refused item is neither named nor linked, and the network never hears of it
     */
    private function expectRefusal(MockObject $item): void
    {
        $item->expects(static::never())
            ->method('get_link');
        $item->expects(static::never())
            ->method('get_fullname');
        $this->plugin->expects(static::never())
            ->method('external_share');

        static::expectException(AccessDeniedException::class);
        static::expectExceptionMessage('Access Denied - Unknown or hidden object');

        $this->subject->run($this->request, $this->gatekeeper);
    }

    /**
     * No logins, nobody signed in, and the visitor may see catalog 3 only
     */
    private function publicInstance(): void
    {
        $this->configContainer->method('isFeatureEnabled')
            ->willReturnMap([
                [ConfigurationKeyEnum::SHARE, true],
                [ConfigurationKeyEnum::DEMO_MODE, false],
            ]);
        $this->configContainer->method('isAuthenticationEnabled')
            ->willReturn(false);
        $this->configContainer->method('get')
            ->with(ConfigurationKeyEnum::SITE_TITLE)
            ->willReturn('Music');
        $this->gatekeeper->method('getUser')
            ->willReturn(null);
        $this->modelFactory->method('createUser')
            ->with(-1)
            ->willReturn($this->user);
        $this->user->catalogs = ['music' => [3]];
    }

    private function request(string $type, int $id): void
    {
        $this->requestParser->method('getFromRequest')
            ->willReturnMap([
                ['type', $type],
                ['id', (string) $id],
            ]);
    }
}
