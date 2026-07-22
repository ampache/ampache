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

    // Biggest tile any supported grid asks for, which is the 2x2 one.
    private const int TILE_MAX = 300;

    public function build(array $images): ?string
    {
        if (!function_exists('imagecreatetruecolor')) {
            return null;
        }

        // Decode as many usable sources as we're willing to place, keeping the caller's order. Each is
        // reduced to its final tile immediately so only one full size cover is ever held: GD allocates
        // outside the php memory_limit, so nine untouched 4000px covers can walk the process past its
        // limit and get it killed without ever raising a php error.
        $tiles = [];
        foreach ($images as $raw) {
            if (count($tiles) >= self::MAX_TILES) {
                break;
            }

            $tile = $this->toTile($raw);
            if ($tile instanceof GdImage) {
                $tiles[] = $tile;
            }
        }

        // Pick the largest square grid we can completely fill.
        $grid = match (true) {
            count($tiles) >= self::MAX_TILES => 3,
            count($tiles) >= self::MIN_TILES => 2,
            default => 0,
        };
        if ($grid === 0) {
            return null;
        }

        // CANVAS_SIZE divides evenly by every supported grid, so tiles fill the canvas exactly.
        $size   = intdiv(self::CANVAS_SIZE, $grid);
        $canvas = imagecreatetruecolor(self::CANVAS_SIZE, self::CANVAS_SIZE);
        if (!$canvas instanceof GdImage) {
            return null;
        }

        // A truecolor canvas starts opaque black, which is what a transparent cover would be flattened
        // onto. Start transparent and copy alpha through instead.
        imagealphablending($canvas, false);
        imagesavealpha($canvas, true);
        $transparent = imagecolorallocatealpha($canvas, 0, 0, 0, 127);
        if ($transparent !== false) {
            imagefilledrectangle($canvas, 0, 0, self::CANVAS_SIZE - 1, self::CANVAS_SIZE - 1, $transparent);
        }

        for ($index = 0; $index < $grid * $grid; $index++) {
            $tile = $tiles[$index];
            imagecopyresampled(
                $canvas,
                $tile,
                ($index % $grid) * $size,
                intdiv($index, $grid) * $size,
                0,
                0,
                $size,
                $size,
                imagesx($tile),
                imagesy($tile)
            );
        }

        ob_start();
        imagepng($canvas);
        $result = (string) ob_get_clean();

        return ($result === '') ? null : $result;
    }

    /**
     * Decode one cover into a square tile, centre cropped and no bigger than a tile ever needs to be.
     */
    private function toTile(string $raw): ?GdImage
    {
        $source = @imagecreatefromstring($raw);
        if (!$source instanceof GdImage) {
            return null;
        }

        $width  = imagesx($source);
        $height = imagesy($source);
        // Center-crop to a square so tiles keep their aspect ratio.
        $side   = min($width, $height);
        $target = min($side, self::TILE_MAX);

        $tile = imagecreatetruecolor($target, $target);
        if (!$tile instanceof GdImage) {
            return null;
        }

        imagealphablending($tile, false);
        imagesavealpha($tile, true);
        imagecopyresampled(
            $tile,
            $source,
            0,
            0,
            intdiv($width - $side, 2),
            intdiv($height - $side, 2),
            $target,
            $target,
            $side,
            $side
        );

        // $source drops out of scope here, so its full size buffer is released before the next decode

        return $tile;
    }
}
