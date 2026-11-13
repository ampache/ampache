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

namespace Ampache\Module\Api;

use Ampache\Config\AmpConfig;
use Ampache\MockeryTestCase;
use Ampache\Module\Database\database_object;
use Ampache\Repository\AlbumRepositoryInterface;
use Ampache\Repository\BookmarkRepositoryInterface;
use Ampache\Repository\FolderRepositoryInterface;
use Ampache\Repository\LabelRepositoryInterface;
use Ampache\Repository\Model\Artist;
use Ampache\Repository\Model\Song;
use Ampache\Repository\Model\User;
use Ampache\Repository\SongRepositoryInterface;
use Override;
use ReflectionMethod;
use ReflectionProperty;
use SimpleXMLElement;

/**
 * Every song of an album maps to the same album artists, and a various-artists album maps every track
 * artist. Building that list once per song is what makes a large folder cost N x A instead of A.
 */
class OpenSubsonicAlbumArtistsTest extends MockeryTestCase
{
    private const int ALBUM_ID = 4093;

    /** One over the limit, so the list collapses */
    private const int TOO_MANY = 11;

    public function testAnAlbumAtTheLimitKeepsEveryName(): void
    {
        $subject     = $this->subject();
        $song        = $this->mock(Song::class);
        $song->album = 8888;
        $song->shouldReceive('get_album_artists')->once()->andReturn(range(1, self::TOO_MANY - 1));

        $this->assertCount(self::TOO_MANY - 1, $subject->songAlbumArtists($song), 'a real collaboration must keep its artists');
    }

    public function testAnAlbumCreditingTooManyArtistsBecomesVarious(): void
    {
        $subject     = $this->subject();
        $song        = $this->mock(Song::class);
        $song->album = 9999;
        $song->shouldReceive('get_album_artists')->once()->andReturn(range(1, self::TOO_MANY));

        $artists = $subject->songAlbumArtists($song);

        $this->assertCount(1, $artists, 'a thousand names under every track is not a credit, it is a payload');
        $this->assertSame('ar-0', $artists[0]['id']);
        $this->assertSame(T_('Various'), $artists[0]['name']);
    }

    public function testArtistId3RatingsAreConsistentAcrossFormats(): void
    {
        AmpConfig::set('memory_cache', true, true);
        new ReflectionProperty(database_object::class, '_enabled')->setValue(null, null);
        $previousUser    = $GLOBALS['user'] ?? null;
        $user            = $this->mock(User::class);
        $user->id        = 427;
        $GLOBALS['user'] = $user;

        try {
            $albumRepository = $this->mock(AlbumRepositoryInterface::class);
            $albumRepository->shouldReceive('getAlbumByArtist')->with(428)->andReturn([]);
            $folderRepository    = $this->mock(FolderRepositoryInterface::class);
            $songRepository      = $this->mock(SongRepositoryInterface::class);
            $json                = new OpenSubsonic_Json_Data($albumRepository, $folderRepository, $this->subject(), $songRepository);
            $xml                 = new OpenSubsonic_Xml_Data($albumRepository, $folderRepository, $this->subject(), $songRepository);
            $artist              = $this->mock(Artist::class);
            $artist->id          = 428;
            $artist->name        = 'Example';
            $artist->mbid        = 'example-id';
            $artist->album_count = 0;
            $artist->song_count  = 1;
            $artist->shouldReceive('isNew')->andReturn(false);
            $artist->shouldReceive('has_art')->andReturn(false);
            $artist->shouldReceive('get_fullname')->andReturn('Example');

            database_object::add_to_cache('rating_artist_user427', 428, [4]);
            database_object::add_to_cache('userflag_artist_user427', 428, [false]);

            $details = $json->addArtistWithAlbumsID3(['subsonic-response' => []], $artist)['subsonic-response']['artist'];
            self::assertSame(4, $details['userRating']);
            self::assertSame('example-id', $details['musicBrainzId']);
            self::assertSame(['artist'], $details['roles']);
            self::assertSame([], $details['album']);
            self::assertSame(4, $json->addArtistID3(['subsonic-response' => []], $artist)['subsonic-response']['artist']['userRating']);

            $document = new SimpleXMLElement('<subsonic-response/>');
            $xml->addArtistID3($document, $artist, true);
            self::assertSame('4', (string) $document->artist['userRating']);

            $row     = ['id' => 428, 'f_name' => 'Example', 'name' => 'Example', 'album_count' => 0, 'song_count' => 1, 'catalog_id' => 1, 'has_art' => 0];
            $indexed = new ReflectionMethod($json, '_getArtistArray')->invoke($json, [], $row);
            self::assertSame(4, $indexed[0]['userRating']);
            $document = new SimpleXMLElement('<index/>');
            new ReflectionMethod($xml, '_addArtistArray')->invoke($xml, $document, $row);
            self::assertSame('4', (string) $document->artist['userRating']);

            database_object::add_to_cache('rating_artist_user427', 428, [0]);
            self::assertArrayNotHasKey('userRating', $json->addArtistWithAlbumsID3(['subsonic-response' => []], $artist)['subsonic-response']['artist']);
            $document = new SimpleXMLElement('<subsonic-response/>');
            $xml->addArtistID3($document, $artist, true);
            self::assertFalse(isset($document->artist['userRating']));
        } finally {
            if ($previousUser === null) {
                unset($GLOBALS['user']);
            } else {
                $GLOBALS['user'] = $previousUser;
            }
        }
    }

    public function testTheDisplayStringIsJoinedOncePerAlbum(): void
    {
        $subject = $this->subject();

        $this->assertSame(
            'Alpha, Beta',
            $subject->songDisplayAlbumArtist($this->song(callsExpected: 1))
        );
        $this->assertSame(
            'Alpha, Beta',
            $subject->songDisplayAlbumArtist($this->song(callsExpected: 0))
        );
    }

    public function testTheEntriesShareOneCopyOfTheList(): void
    {
        $subject = $this->subject();
        $entries = [];
        foreach ([$this->song(callsExpected: 1), $this->song(callsExpected: 0), $this->song(callsExpected: 0)] as $song) {
            $entries[] = ['albumArtists' => $subject->songAlbumArtists($song)];
        }

        // a copy would cost per entry; sharing is what keeps a folder of N songs at the size of one list
        $this->assertSame($entries[0]['albumArtists'], $entries[2]['albumArtists']);
    }

    public function testTheListIsBuiltOncePerAlbum(): void
    {
        $subject = $this->subject();
        $first   = $this->song(callsExpected: 1);
        $second  = $this->song(callsExpected: 0);

        $this->assertSame(
            $subject->songAlbumArtists($first),
            $subject->songAlbumArtists($second),
            'the second song of the same album must reuse the first list'
        );
    }

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        // `Artist::get_name_array_by_id()` reads this cache first, which keeps the lookup off the database
        $names = [11 => 'Alpha', 22 => 'Beta'];
        // the list tests walk ids 1..11, and `get_name_array_by_id()` would otherwise go to the database
        foreach (range(1, self::TOO_MANY) as $artistId) {
            $names[$artistId] ??= 'Artist ' . $artistId;
        }

        foreach ($names as $artistId => $name) {
            database_object::add_to_cache('artist_name_array', $artistId, [
                'id' => (string) $artistId,
                'name' => $name,
                'prefix' => '',
                'basename' => $name,
            ]);
        }
    }

    private function song(int $callsExpected): Song
    {
        $song        = $this->mock(Song::class);
        $song->album = self::ALBUM_ID;
        $song->shouldReceive('get_album_artists')->times($callsExpected)->andReturn([11, 22]);

        return $song;
    }

    private function subject(): OpenSubsonic_Fields
    {
        return new OpenSubsonic_Fields(
            $this->mock(BookmarkRepositoryInterface::class),
            $this->mock(LabelRepositoryInterface::class),
        );
    }
}
