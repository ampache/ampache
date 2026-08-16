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

namespace Ampache\Plugin;

use Ampache\Module\Api\OpenSubsonic_Api;
use Ampache\Module\Util\UrlValidatorInterface;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

/**
 * Covers the id translation and score normalisation in the AudioMuse sonic-analysis plugin.
 *
 * AudioMuse has no Ampache connector — it is pointed at Ampache through its Navidrome connector, which is a plain
 * Subsonic API client. So it indexes tracks under the prefixed `so-<id>` form Ampache gives Subsonic clients, not
 * the bare row id. Getting that translation wrong silently returns nothing, which is why it is pinned here.
 */
class AmpacheAudioMuseTest extends TestCase
{
    /**
     * @param array<int, array<string, mixed>> $rows
     * @return list<array{'id': int, 'similarity': float}>
     */
    private static function toMatches(array $rows): array
    {
        $method = new ReflectionMethod(AmpacheAudioMuse::class, '_toMatches');

        $urlValidator = new class implements UrlValidatorInterface {
            public function isPublicHttpUrl(string $url): bool
            {
                return true;
            }
        };

        /** @var list<array{'id': int, 'similarity': float}> $result */
        $result = $method->invoke(new AmpacheAudioMuse($urlValidator), $rows);

        return $result;
    }

    /**
     * A bare id must not go through getAmpacheId(): the legacy old-style Subsonic ranges read a small integer as a
     * catalog id, which would drop every native-connector row.
     */
    public function testBareIdsAreNotMistakenForOtherObjectTypes(): void
    {
        self::assertSame('catalog', OpenSubsonic_Api::getAmpacheType('1'));
        self::assertSame([['id' => 1, 'similarity' => 1.0]], self::toMatches([['item_id' => '1', 'distance' => 0.0]]));
    }

    /**
     * AudioMuse reports whichever id the connector it scanned with used: its Ampache connector reports Ampache's own
     * row id, its Navidrome connector the Subsonic `so-` form. Both have to resolve, or half the setups return nothing.
     */
    public function testMatchesAcceptEitherConnectorsIdForm(): void
    {
        self::assertSame(
            [
                ['id' => 1, 'similarity' => 0.9],
                ['id' => 3001, 'similarity' => 0.9],
            ],
            self::toMatches([
                ['item_id' => '1', 'distance' => 0.1],
                ['item_id' => 'so-3001', 'distance' => 0.1],
            ])
        );
    }

    /**
     * AudioMuse can report an angular distance above 1, which must not become a negative similarity.
     */
    public function testMatchesClampSimilarityIntoRange(): void
    {
        $matches = self::toMatches([
            ['item_id' => 'so-2', 'distance' => 1.9],
            ['item_id' => 'so-4', 'distance' => -0.5],
        ]);

        self::assertSame(0.0, $matches[0]['similarity']);
        self::assertSame(1.0, $matches[1]['similarity']);
    }

    /**
     * A row that is not a song id belongs to some other server's index, so it is dropped rather than guessed at.
     */
    public function testMatchesDropRowsThatAreNotSongIds(): void
    {
        self::assertSame(
            [],
            self::toMatches([
                ['item_id' => 'al-7', 'distance' => 0.1],
                ['item_id' => 'ar-2', 'distance' => 0.1],
                ['item_id' => '', 'distance' => 0.1],
                ['distance' => 0.1],
            ])
        );
    }

    public function testMatchesTranslateSubsonicIdsBackToAmpacheIds(): void
    {
        self::assertSame(
            [
                ['id' => 1, 'similarity' => 1.0],
                ['id' => 3001, 'similarity' => 0.58],
            ],
            self::toMatches([
                ['item_id' => 'so-1', 'distance' => 0.0],
                ['item_id' => 'so-3001', 'distance' => 0.42],
            ])
        );
    }

    /**
     * The id sent out has to be the one AudioMuse indexed, which is the Subsonic form.
     */
    public function testOutgoingIdsUseTheSubsonicForm(): void
    {
        self::assertSame('so-1', OpenSubsonic_Api::getSongSubId(1));
        self::assertSame('so-3001', OpenSubsonic_Api::getSongSubId(3001));
    }

    /**
     * find_path scores the whole route, not each hop, so its rows arrive with no distance at all. Treating that as
     * distance 0 would report every song in the path as a perfect match; -1 is what the spec reserves for it.
     */
    public function testRowsWithNoDistanceReportSimilarityAsUnsupported(): void
    {
        self::assertSame(
            [
                ['id' => 1, 'similarity' => -1.0],
                ['id' => 3001, 'similarity' => -1.0],
            ],
            self::toMatches([
                ['item_id' => 'so-1', 'title' => 'Pasadinha'],
                ['item_id' => 'so-3001', 'title' => 'Empty Phases'],
            ])
        );
    }
}
