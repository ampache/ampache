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

namespace Ampache\Module\Art\Mosaic;

interface PlaylistArtBuilderInterface
{
    // Largest number of tiles a mosaic lays out (3x3). Callers use this to stop collecting covers.
    public const int MAX_TILES = 9;

    // Fewest tiles a mosaic needs (2x2). Below this the caller keeps its single cover.
    public const int MIN_TILES = 4;

    /**
     * Composite cover images into a square grid mosaic (2x2 or 3x3).
     *
     * @param list<string> $images raw image byte-strings, ordered by preference
     * @return string|null raw PNG bytes, or null when a mosaic can't be built
     */
    public function build(array $images): ?string;
}
