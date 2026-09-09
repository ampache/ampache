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

namespace Ampache\Repository\Model;

use PHPUnit\Framework\TestCase;

class PlaylistTest extends TestCase
{
    public function testSplitMixedIdsKeepsANumericStringOnThePlaylistSide(): void
    {
        // a browse hands its ids back as strings, and only the `smart_` prefix marks a smartlist
        self::assertSame(
            ['playlist' => [3], 'search' => []],
            Playlist::split_mixed_ids(['3'])
        );
    }

    public function testSplitMixedIdsReturnsBothHalvesEmptyForAnEmptyList(): void
    {
        self::assertSame(
            ['playlist' => [], 'search' => []],
            Playlist::split_mixed_ids([])
        );
    }

    public function testSplitMixedIdsSeparatesSmartlistsFromPlaylists(): void
    {
        self::assertSame(
            ['playlist' => [1, 2], 'search' => [6, 7]],
            Playlist::split_mixed_ids([1, 'smart_6', 2, 'smart_7'])
        );
    }
}
