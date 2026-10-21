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

use Ampache\Repository\Model\Album;
use Ampache\Repository\Model\Artist;
use Ampache\Repository\Model\library_item;
use Ampache\Repository\Model\LibraryItemLoaderInterface;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\Model\Podcast_Episode;
use Ampache\Repository\Model\Song;
use Ampache\Repository\Model\User;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class PlayableItemRssItemAdapterTest extends TestCase
{
    private LibraryItemLoaderInterface&MockObject $libraryItemLoader;
    private ModelFactoryInterface&MockObject $modelFactory;
    private library_item&MockObject $playable;
    private PlayableItemRssItemAdapter $subject;
    private User&MockObject $user;

    /**
     * Source text, raw or already escaped and flavoured with the Discworld, to the normalised raw it must become.
     *
     * @return array<string, array{0: string, 1: string}>
     */
    public static function summaryProvider(): array
    {
        return [
            'Granny, raw accent' => ['Mémé Ciredutemps', 'Mémé Ciredutemps'],
            'Granny, escaped accent' => ['M&eacute;m&eacute; Ciredutemps', 'Mémé Ciredutemps'],
            'Rincewind, raw amp' => ['Rincewind & the Luggage', 'Rincewind & the Luggage'],
            'Rincewind, escaped amp' => ['Rincewind &amp; the Luggage', 'Rincewind & the Luggage'],
            'octarine, raw tag' => ['</itunes:summary><octarine>', '</itunes:summary><octarine>'],
            'octarine, escaped tag' => ['&lt;/itunes:summary&gt;', '</itunes:summary>'],
            'the Patrician, cdata' => ['Vetinari ]]> dismissed', 'Vetinari ]]> dismissed'],
            'Death, control stripped' => ["DEATH\x08 SPEAKS\x0B here", 'DEATH SPEAKS here'],
            "A'Tuin, tab and newline" => ["A'Tuin\tswims\non", "A'Tuin\tswims\non"],
            'the turtle, emoji' => ['🐢 café', '🐢 café'],
        ];
    }

    public function testAnAlbumAnswersWithItsReleaseAndFallsBackToTheGroup(): void
    {
        $album       = $this->createMock(Album::class);
        $album->mbid = 'aaaaaaaa-bbbb-cccc-dddd-eeeeeeeeeeee';
        $this->assertSame(
            'https://musicbrainz.org/release/aaaaaaaa-bbbb-cccc-dddd-eeeeeeeeeeee',
            $this->musicBrainzUrl($album)
        );

        $album->mbid       = null;
        $album->mbid_group = '11111111-2222-3333-4444-555555555555';
        $this->assertSame(
            'https://musicbrainz.org/release-group/11111111-2222-3333-4444-555555555555',
            $this->musicBrainzUrl($album)
        );
    }

    public function testAnArtistAnswersWithItsMusicBrainzUrl(): void
    {
        $artist       = $this->createMock(Artist::class);
        $artist->mbid = 'cf157aed-60a1-4442-bc6f-490f418f496b';

        $this->assertSame(
            'https://musicbrainz.org/artist/cf157aed-60a1-4442-bc6f-490f418f496b',
            $this->musicBrainzUrl($artist)
        );
    }

    public function testAnItemWithNoIdentifierPublishesNoUrlRatherThanAnEmptyOne(): void
    {
        $artist       = $this->createMock(Artist::class);
        $artist->mbid = null;
        $this->assertNull($this->musicBrainzUrl($artist));

        $album             = $this->createMock(Album::class);
        $album->mbid       = null;
        $album->mbid_group = null;
        $this->assertNull($this->musicBrainzUrl($album));

        $this->assertNull($this->musicBrainzUrl($this->createMock(library_item::class)));
    }

    public function testArtSourcesDropsAnAlbumlessSong(): void
    {
        $song = $this->createMock(Song::class);
        $song->method('getId')->willReturn(49388);

        self::assertSame(
            [['song', 49388]],
            PlayableItemRssItemAdapter::artSources($song)
        );
    }

    public function testArtSourcesDropsAnUnsetPodcast(): void
    {
        $episode = $this->createMock(Podcast_Episode::class);
        $episode->method('getId')->willReturn(666);

        self::assertSame(
            [['podcast_episode', 666]],
            PlayableItemRssItemAdapter::artSources($episode)
        );
    }

    public function testArtSourcesReadsTheAlbumAfterTheSong(): void
    {
        $song        = $this->createMock(Song::class);
        $song->album = 7953;
        $song->method('getId')->willReturn(49388);

        self::assertSame(
            [['song', 49388], ['album', 7953]],
            PlayableItemRssItemAdapter::artSources($song)
        );
    }

    public function testArtSourcesReadsThePodcastAfterTheEpisode(): void
    {
        $episode          = $this->createMock(Podcast_Episode::class);
        $episode->podcast = 12;
        $episode->method('getId')->willReturn(666);

        self::assertSame(
            [['podcast_episode', 666], ['podcast', 12]],
            PlayableItemRssItemAdapter::artSources($episode)
        );
    }

    /**
     * A podcast episode's own get_description() already scrub_out()'s its text; a song has no description of
     * its own, so one is built from plain, unescaped name getters and must be scrubbed the same way, or the
     * rss template, which now e()s both branches, so buildMediaDescription returns raw normalised text.
     */
    public function testBuildMediaDescriptionReturnsRawNormalisedTextForBothBranches(): void
    {
        $song        = $this->createMock(Song::class);
        $song->album = 0;
        $song->method('get_fullname')->willReturn('Good Times & Bad Times');
        $song->method('get_album_fullname')->willReturn('Soul Music');
        $song->method('get_parent_fullname')->willReturn('The Band With Rocks In');

        $episode = $this->createMock(Podcast_Episode::class);
        $episode->method('get_description')->willReturn('Music With Rocks In &amp; more');

        $method = new ReflectionMethod($this->subject, 'buildMediaDescription');

        self::assertSame(
            'Good Times & Bad Times - Soul Music - The Band With Rocks In',
            $method->invoke($this->subject, $song),
        );
        self::assertSame(
            'Music With Rocks In & more',
            $method->invoke($this->subject, $episode),
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

    #[DataProvider('summaryProvider')]
    public function testGetSummaryReturnsNormalisedRawText(string $source, string $expected): void
    {
        $this->playable->method('get_description')->willReturn($source);

        $summary = $this->subject->getSummary();
        $this->assertSame($expected, $summary);

        // that normalised raw, escaped by the template via e(), always yields well-formed XML
        $dom = new \DOMDocument();
        $this->assertTrue(@$dom->loadXML('<r>' . htmlspecialchars($summary, ENT_QUOTES, 'UTF-8') . '</r>'));
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

    public function testHasOwnerReturnsFalseIfNotAvailable(): void
    {
        $this->playable->expects(static::once())
            ->method('get_user_owner')
            ->willReturn(null);

        self::assertFalse(
            $this->subject->hasOwner()
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

    protected function setUp(): void
    {
        $this->libraryItemLoader = $this->createMock(LibraryItemLoaderInterface::class);
        $this->modelFactory      = $this->createMock(ModelFactoryInterface::class);
        $this->playable          = $this->createMock(library_item::class);
        $this->user              = $this->createMock(User::class);

        $this->subject = new PlayableItemRssItemAdapter(
            $this->libraryItemLoader,
            $this->modelFactory,
            $this->playable,
            $this->user,
        );
    }

    private function musicBrainzUrl(library_item $item): ?string
    {
        return new ReflectionMethod(PlayableItemRssItemAdapter::class, 'musicBrainzUrl')->invoke(null, $item);
    }
}
