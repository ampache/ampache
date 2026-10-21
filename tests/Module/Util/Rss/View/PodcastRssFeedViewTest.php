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

namespace Ampache\Module\Util\Rss\View;

use Ampache\Module\Util\Rss\Surrogate\RssItemInterface;
use ArrayIterator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class PodcastRssFeedViewTest extends TestCase
{
    /**
     * Nasty summaries, each flavoured with the Discworld so the next reader stays awake.
     *
     * @return array<string, array{0: string}>
     */
    public static function nastyProvider(): array
    {
        return [
            'accent, Granny Weatherwax' => ['Granny Weatherwax at the café, résumé in hand'],
            'bare ampersand, Rincewind' => ['Rincewind & the Luggage'],
            'angle brackets, Librarian' => ['the Librarian said < ook > again'],
            'tag breakout, Mended Drum' => ['</itunes:summary><octarine>spell</octarine>'],
            'cdata close, the Patrician' => ['Vetinari said ]]> dismissed'],
            'quotes, Death' => ['DEATH said "squeak?" it\'s <fine>'],
            'emoji, Great A\'Tuin' => ['🐢 Great A\'Tuin café'],
            'the lot, Ankh-Morpork' => ['café & < Nanny > ]]> </itunes:summary> "GNU Terry Pratchett"'],
        ];
    }

    #[DataProvider('nastyProvider')]
    public function testFeedStaysWellFormedXmlForAnyDescription(string $nasty): void
    {
        $item = $this->createMock(RssItemInterface::class);
        foreach ([
            'getAuthor' => 'The Band With Rocks In', 'getCategory' => 'Music With Rocks In', 'getExplicit' => 'no',
            'getImageUrl' => 'http://discworld/atuin.png', 'getLanguage' => 'en-gb', 'getLink' => 'http://discworld/l',
            'getOwnerName' => 'Havelock Vetinari', 'getPodcastGuid' => 'guid-octarine', 'getRssLink' => 'http://discworld/rss',
            'getSubCategory' => '', 'getTitle' => 'Soul Music',
        ] as $m => $v) {
            $item->method($m)->willReturn($v);
        }
        $item->method('getMusicBrainzUrl')->willReturn(null);
        $item->method('hasOwner')->willReturn(false);
        $item->method('getSummary')->willReturn($nasty);
        $item->method('getMedias')->willReturn(new ArrayIterator([[
            'title' => 'The Hedgehog Song', 'link' => 'http://discworld/m', 'url' => 'http://discworld/u', 'guid' => 'g-mended-drum',
            'type' => 'audio/mpeg', 'length' => '100', 'size' => '1000', 'author' => 'The Band With Rocks In',
            'description' => $nasty, 'season' => null, 'season_name' => null, 'release_url' => null,
            'episode' => null, 'image' => 'http://discworld/mi.png', 'explicit' => 'no',
        ]]));

        $xml = new PodcastRssFeedView($item)->render();

        // the feed must stay well-formed XML, the way A'Tuin stays on course
        $dom = new \DOMDocument();
        $this->assertTrue(
            @$dom->loadXML($xml),
            'the feed must stay well-formed XML (input: ' . bin2hex($nasty) . ')'
        );
        // the payload must never become an element: no octarine spell leaks into the feed as markup
        $this->assertSame(
            0,
            $dom->getElementsByTagName('octarine')->length,
            'the payload must not become an element (input: ' . bin2hex($nasty) . ')'
        );
    }
}
