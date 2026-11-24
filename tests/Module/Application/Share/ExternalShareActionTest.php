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

use Ampache\Config\AmpConfig;
use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Module\Application\Exception\AccessDeniedException;
use Ampache\Module\Authorization\AccessLevelEnum;
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
use Ampache\Repository\Model\License;
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
    /** @var array<string, mixed> */
    private array $config = [];

    private ConfigContainerInterface&MockObject $configContainer;
    private GuiGatekeeperInterface&MockObject $gatekeeper;
    private LibraryItemLoaderInterface&MockObject $libraryItemLoader;
    private AmpacheMastodon&MockObject $plugin;
    private ServerRequestInterface&MockObject $request;
    private RequestParserInterface&MockObject $requestParser;
    private ResponseInterface&MockObject $response;
    private ShareCreatorInterface&MockObject $shareCreator;

    /** the admin's `share_text`, empty for the built-in sentences */
    private string $shareText = '';

    private ExternalShareAction $subject;
    private User&MockObject $user;

    public function testADiskItsPageHidesIsRefused(): void
    {
        $this->publicInstance();
        $this->request('album_disk', 5);

        $disk = $this->createMock(AlbumDisk::class);
        $disk->method('isShownTo')
            ->with($this->user)
            ->willReturn(false);

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

    public function testAnAlbumItsPageHidesIsRefused(): void
    {
        $this->publicInstance();
        $this->request('album', 4);

        $album = $this->createMock(Album::class);
        $album->method('isShownTo')
            ->with($this->user)
            ->willReturn(false);

        $this->libraryItemLoader->method('load')
            ->willReturn($album);

        $this->expectRefusal($album);
    }

    // an album is named as one, so the reader knows the link holds more than a song
    public function testAnAlbumSaysItIsOne(): void
    {
        $this->publicInstance();
        $this->request('album', 4);

        $album = $this->createMock(Album::class);
        $album->method('isShownTo')
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

        $song = $this->createMock(Song::class);
        $song->method('isShownTo')
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

    public function testASongItsPageHidesIsRefused(): void
    {
        $this->publicInstance();
        $this->request('song', 7);

        $song = $this->createMock(Song::class);
        $song->method('isShownTo')
            ->with($this->user)
            ->willReturn(false);

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

    // the level is checked before the plugin is even built, so a refused visitor costs nothing
    public function testAVisitorBelowTheShareLevelIsRefused(): void
    {
        $this->publicInstance();
        AmpConfig::set('share_line_access_level', AccessLevelEnum::USER->value, true);
        $this->request('song', 7);

        $this->plugin->expects(static::never())
            ->method('load');
        $this->libraryItemLoader->expects(static::never())
            ->method('load');

        static::expectException(AccessDeniedException::class);

        $this->subject->run($this->request, $this->gatekeeper);
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

    // a song without an artist drops the bracketed part, so no dangling "by" reaches the network
    public function testTheAdminsTextDropsAPartWhoseVariableIsEmpty(): void
    {
        $this->shareText = '{site}: {title}[ by {artist}] ({album})';

        self::assertSame('Music: Some song (Some album)', $this->postedText($this->song('', 'Some song')));
    }

    // a name that happens to read like a variable is posted as typed, never filled in again
    public function testTheAdminsTextFillsInOnePass(): void
    {
        $this->shareText = '{title} on {site}';

        self::assertSame('Live at {site} on Music', $this->postedText($this->song('Some artist', 'Live at {site}')));
    }

    // the admin picks the variables and their order, and leaves out the ones they do not want
    public function testTheAdminsTextIsPostedWithTheVariablesItUses(): void
    {
        $this->shareText = '{site}: {title}[ by {artist}] ({album})';

        self::assertSame('Music: Some song by Some artist (Some album)', $this->postedText($this->song('Some artist', 'Some song')));
    }

    // an optional part at the end of the text leaves no trailing space behind
    public function testTheAdminsTextIsTrimmed(): void
    {
        $this->shareText = '{title} [{tags}]';

        self::assertSame('Some song', $this->postedText($this->song('Some artist', 'Some song')));
    }

    // a visitor without an account is the guest user, whose placeholder name would be posted otherwise
    public function testTheAdminsTextLeavesTheGuestUnnamed(): void
    {
        $this->shareText = '[{user} recommends ]{title}';

        $this->user->id = -1;
        $this->user->method('get_fullname')
            ->willReturn('Guest');

        self::assertSame('Some song', $this->postedText($this->song('Some artist', 'Some song')));
    }

    // on an album page the album and its artist fill the same variables a song page does
    public function testTheAdminsTextNamesAnAlbumLikeASong(): void
    {
        $this->shareText = '{album} by {artist}';

        $album = $this->createMock(Album::class);
        $album->method('isShownTo')
            ->willReturn(true);
        $album->method('get_fullname')
            ->willReturn('Curses');
        $album->method('get_parent_fullname')
            ->willReturn('ProleteR');

        self::assertSame('Curses by ProleteR', $this->postedText($album));
    }

    // on an artist page the artist is the page itself
    public function testTheAdminsTextNamesTheArtistOfAnArtistPage(): void
    {
        $this->shareText = 'Discover {artist}';

        $artist = $this->createMock(Artist::class);
        $artist->method('isVisible')
            ->willReturn(true);
        $artist->method('get_fullname')
            ->willReturn('ProleteR');

        self::assertSame('Discover ProleteR', $this->postedText($artist));
    }

    public function testTheAdminsTextNamesTheLicense(): void
    {
        $this->shareText = '{title}[ ({license})]';

        $license = $this->createMock(License::class);
        $license->method('getName')
            ->willReturn('CC BY-SA');
        $song = $this->song('Some artist', 'Some song');
        $song->method('getLicense')
            ->willReturn($license);

        self::assertSame('Some song (CC BY-SA)', $this->postedText($song));
    }

    // someone signed in is named where the admin put {user}
    public function testTheAdminsTextNamesWhoShares(): void
    {
        $this->shareText = '[{user} recommends ]{title}';

        $this->user->id = 12;
        $this->user->method('get_fullname')
            ->willReturn('Alan');

        self::assertSame('Alan recommends Some song', $this->postedText($this->song('Some artist', 'Some song')));
    }

    // a network cuts a hashtag at a space or a sign, so each tag is one word, and a tag of signs alone is skipped
    public function testTheAdminsTextPostsTheTagsAsHashtags(): void
    {
        $this->shareText = '{title} {tags}';

        $song = $this->song('Some artist', 'Some song');
        $song->method('get_tags')
            ->willReturn([['name' => 'Drum & Bass'], ['name' => '&'], ['name' => 'électro'], ['name' => 'dub'], ['name' => 'jazz']]);

        self::assertSame('Some song #DrumBass #électro #dub', $this->postedText($song));
    }

    protected function setUp(): void
    {
        $this->requestParser     = $this->createMock(RequestParserInterface::class);
        $this->configContainer   = $this->createMock(ConfigContainerInterface::class);
        $this->shareCreator      = $this->createMock(ShareCreatorInterface::class);
        $this->libraryItemLoader = $this->createMock(LibraryItemLoaderInterface::class);
        $this->response          = $this->createMock(ResponseInterface::class);
        $this->plugin            = $this->createMock(AmpacheMastodon::class);
        $this->user              = $this->createMock(User::class);
        $this->request           = $this->createMock(ServerRequestInterface::class);
        $this->gatekeeper        = $this->createMock(GuiGatekeeperInterface::class);
        $this->user->access      = AccessLevelEnum::USER->value;

        foreach (['share', 'share_line_access_level'] as $key) {
            $this->config[$key] = AmpConfig::get($key);
        }

        AmpConfig::set('share', true, true);
        AmpConfig::set('share_line_access_level', AccessLevelEnum::USER->value, true);

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
            $this->libraryItemLoader
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

    protected function tearDown(): void
    {
        foreach ($this->config as $key => $value) {
            AmpConfig::set($key, $value, true);
        }
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
     * Shares the item from a public instance and returns the sentence the network was handed
     */
    private function postedText(MockObject $item): string
    {
        $this->publicInstance();
        $this->request('song', 7);

        $item->method('get_link')
            ->willReturn('https://music.example/');
        $this->libraryItemLoader->method('load')
            ->willReturn($item);

        $posted = '';
        $this->plugin->method('external_share')
            ->willReturnCallback(function (string $url, string $text) use (&$posted): string {
                $posted = $text;

                return 'https://social.example/share';
            });
        $this->response->method('withHeader')
            ->willReturnSelf();

        $this->subject->run($this->request, $this->gatekeeper);

        return $posted;
    }

    /**
     * No logins, the visitor is the guest user, and guests may share
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
            ->willReturnCallback(fn(string $key): string => match ($key) {
                ConfigurationKeyEnum::SITE_TITLE => 'Music',
                ConfigurationKeyEnum::SHARE_TEXT => $this->shareText,
            });
        $this->gatekeeper->method('getUser')
            ->willReturn($this->user);
        AmpConfig::set('share_line_access_level', AccessLevelEnum::GUEST->value, true);
        $this->user->access = AccessLevelEnum::GUEST->value;
    }

    private function request(string $type, int $id): void
    {
        $this->requestParser->method('getFromRequest')
            ->willReturnMap([
                ['type', $type],
                ['id', (string) $id],
            ]);
    }

    private function song(string $artist, string $title): Song&MockObject
    {
        $song = $this->createMock(Song::class);
        $song->method('isShownTo')
            ->willReturn(true);
        $song->method('get_fullname')
            ->willReturn($title);
        $song->method('get_parent_fullname')
            ->willReturn($artist);
        $song->method('get_album_fullname')
            ->willReturn('Some album');

        return $song;
    }
}
