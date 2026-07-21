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

use GdImage;

/**
 * Stitches several cover images into a single square grid image for playlist art.
 */
final readonly class PlaylistArtBuilder implements PlaylistArtBuilderInterface
{
    // Edge length of the generated mosaic in pixels; each tile is a whole fraction of this.
    private const int CANVAS_SIZE = 600;
    // Largest number of tiles we lay out (3x3); at least MIN_TILES are needed for a mosaic (2x2).
    private const int MAX_TILES = 9;

    private const int MIN_TILES = 4;

    public function build(array $images): ?string
    {
        if (!function_exists('imagecreatetruecolor')) {
            return null;
        }

        // Decode as many usable sources as we're willing to place, keeping the caller's order.
        $sources = [];
        foreach ($images as $raw) {
            if (count($sources) >= self::MAX_TILES) {
                break;
            }

            $image = @imagecreatefromstring($raw);
            if ($image instanceof GdImage) {
                $sources[] = $image;
            }
        }

        // Pick the largest square grid we can completely fill.
        $grid = match (true) {
            count($sources) >= self::MAX_TILES => 3,
            count($sources) >= self::MIN_TILES => 2,
            default => 0,
        };
        if ($grid === 0) {
            return null;
        }

        // CANVAS_SIZE divides evenly by every supported grid, so tiles fill the canvas exactly.
        $tile   = intdiv(self::CANVAS_SIZE, $grid);
        $canvas = imagecreatetruecolor(self::CANVAS_SIZE, self::CANVAS_SIZE);
        if (!$canvas instanceof GdImage) {
            return null;
        }

        for ($index = 0; $index < $grid * $grid; $index++) {
            $source = $sources[$index];
            $width  = imagesx($source);
            $height = imagesy($source);
            $side   = min($width, $height);
            // Center-crop each source to a square so tiles keep their aspect ratio.
            imagecopyresampled(
                $canvas,
                $source,
                ($index % $grid) * $tile,
                intdiv($index, $grid) * $tile,
                intdiv($width - $side, 2),
                intdiv($height - $side, 2),
                $tile,
                $tile,
                $side,
                $side
            );
        }

        ob_start();
        imagepng($canvas);
        $result = (string) ob_get_clean();

        return ($result === '') ? null : $result;
    }
}
