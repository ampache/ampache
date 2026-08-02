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

use PHPUnit\Framework\TestCase;

class SongPreviewResultTest extends TestCase
{
    private static function preview(string $artist, string $title): SongPreviewResult
    {
        return new SongPreviewResult('https://example.org/' . md5($artist . $title) . '.m4a', $title, $artist);
    }

    public function testAnEmptyRequestMatchesNothing(): void
    {
        static::assertSame([], SongPreviewResult::rank([self::preview('Daft Punk', 'Get Lucky')], '', ''));
    }

    public function testDropsACoverByADifferentArtist(): void
    {
        $cover = self::preview('The Wedding Band', 'Get Lucky');

        static::assertSame([], SongPreviewResult::rank([$cover], 'Daft Punk', 'Get Lucky'));
    }

    public function testDropsADifferentTrackByTheSameArtist(): void
    {
        $other = self::preview('Daft Punk', 'Around the World');

        static::assertSame([], SongPreviewResult::rank([$other], 'Daft Punk', 'Get Lucky'));
    }

    public function testKeepsAnExactMatch(): void
    {
        $wanted = self::preview('Daft Punk', 'Get Lucky');

        static::assertSame([$wanted], SongPreviewResult::rank([$wanted], 'Daft Punk', 'Get Lucky'));
    }

    public function testKeepsAResultCarryingExtraCreditsAndASuffix(): void
    {
        // what the providers actually return for this track
        $wanted = self::preview('Daft Punk, Pharrell Williams & Nile Rodgers', 'Get Lucky (Radio Edit)');

        static::assertSame([$wanted], SongPreviewResult::rank([$wanted], 'Daft Punk', 'Get Lucky'));
    }

    public function testPutsTheClosestMatchFirst(): void
    {
        $loose = self::preview('Daft Punk, Pharrell Williams & Nile Rodgers', 'Get Lucky (Radio Edit)');
        $exact = self::preview('Daft Punk', 'Get Lucky');

        static::assertSame([$exact, $loose], SongPreviewResult::rank([$loose, $exact], 'Daft Punk', 'Get Lucky'));
    }
}
