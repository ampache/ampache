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
 */

namespace Ampache\Module\Art\Mosaic;

use Generator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use PHPUnit\Framework\TestCase;

#[RequiresPhpExtension('gd')]
class PlaylistArtBuilderTest extends TestCase
{
    /** @var list<array{int, int, int}> nine visually distinct colours, one per tile */
    private const array COLORS = [
        [255, 0, 0],
        [0, 255, 0],
        [0, 0, 255],
        [255, 255, 0],
        [255, 0, 255],
        [0, 255, 255],
        [255, 128, 0],
        [128, 0, 255],
        [255, 255, 255],
    ];

    private PlaylistArtBuilder $subject;

    public static function partialGridProvider(): Generator
    {
        yield '4 covers fill 2x2' => [4, 2];
        yield '5 covers still 2x2' => [5, 2];
        yield '8 covers still 2x2' => [8, 2];
        yield '9 covers fill 3x3' => [9, 3];
        yield '12 covers still 3x3' => [12, 3];
    }

    public function testBuildCentreCropsToASquare(): void
    {
        // red banner with a green square dead centre; a centre crop keeps only the green
        $image = imagecreatetruecolor(600, 200);
        self::assertNotFalse($image);
        imagefill($image, 0, 0, (int) imagecolorallocate($image, 255, 0, 0));
        imagefilledrectangle($image, 200, 0, 399, 199, (int) imagecolorallocate($image, 0, 255, 0));
        ob_start();
        imagepng($image);
        $wide = (string) ob_get_clean();

        $result = $this->subject->build(array_fill(0, 4, $wide));

        self::assertNotNull($result);
        self::assertSame([0, 255, 0], $this->tileColors($result, 2)[0]);
    }

    public function testBuildCreatesSquareMosaicFromFourImages(): void
    {
        $colors = array_slice(self::COLORS, 0, 4);
        $images = array_map(fn(array $rgb): string => $this->createImage(500, 250, $rgb), $colors);

        $result = $this->subject->build($images);

        $this->assertMosaic($result);
        // each source has to land in its own cell, in the order it was handed over
        self::assertSame($colors, $this->tileColors((string) $result, 2));
    }

    public function testBuildCreatesSquareMosaicFromNineImages(): void
    {
        $images = array_map(fn(array $rgb): string => $this->createImage(120, 400, $rgb), self::COLORS);

        $result = $this->subject->build($images);

        $this->assertMosaic($result);
        self::assertSame(self::COLORS, $this->tileColors((string) $result, 3));
    }

    #[DataProvider('partialGridProvider')]
    public function testBuildFillsTheLargestCompleteGrid(int $count, int $grid): void
    {
        $images = array_map(
            fn(array $rgb): string => $this->createImage(300, 300, $rgb),
            array_slice(self::COLORS, 0, $count)
        );

        $result = $this->subject->build($images);

        $this->assertMosaic($result);
        // a count between two grid sizes fills the smaller one and drops the extra covers
        self::assertSame(array_slice(self::COLORS, 0, $grid * $grid), $this->tileColors((string) $result, $grid));
    }

    public function testBuildIgnoresUndecodableImagesWhenCountingTiles(): void
    {
        // Three good covers plus junk isn't enough for a mosaic.
        $images = [$this->createImage(300, 300), 'junk', $this->createImage(300, 300), 'junk', $this->createImage(300, 300)];

        self::assertNull($this->subject->build($images));
    }

    public function testBuildKeepsTransparency(): void
    {
        $images = array_fill(0, 4, $this->createTransparentImage(300, 300));

        $result = $this->subject->build($images);

        self::assertNotNull($result);
        $image = imagecreatefromstring($result);
        self::assertNotFalse($image);
        // a truecolor canvas starts opaque black, so a lost alpha channel shows up as a black tile
        self::assertSame(127, (imagecolorat($image, 150, 150) >> 24) & 0x7F);
    }

    public function testBuildReturnsNullWhenNoImageIsDecodable(): void
    {
        self::assertNull($this->subject->build(['not-an-image', '', 'still not']));
    }

    public function testBuildReturnsNullWithFewerThanFourImages(): void
    {
        $images = [$this->createImage(300, 300), $this->createImage(300, 300), $this->createImage(300, 300)];

        self::assertNull($this->subject->build($images));
    }

    protected function setUp(): void
    {
        $this->subject = new PlaylistArtBuilder();
    }

    private function assertMosaic(?string $result): void
    {
        self::assertNotNull($result);

        $info = getimagesizefromstring($result);
        self::assertNotFalse($info);
        self::assertSame(IMAGETYPE_PNG, $info[2]);
        self::assertSame(600, $info[0]);
        self::assertSame(600, $info[1]);
    }

    /**
     * @param array{int, int, int} $rgb
     */
    private function createImage(int $width, int $height, array $rgb = [0, 0, 0]): string
    {
        $image = imagecreatetruecolor($width, $height);
        self::assertNotFalse($image);
        imagefill($image, 0, 0, (int) imagecolorallocate($image, ...$rgb));

        ob_start();
        imagepng($image);

        return (string) ob_get_clean();
    }

    private function createTransparentImage(int $width, int $height): string
    {
        $image = imagecreatetruecolor($width, $height);
        self::assertNotFalse($image);
        imagealphablending($image, false);
        imagesavealpha($image, true);
        imagefill($image, 0, 0, (int) imagecolorallocatealpha($image, 0, 0, 0, 127));

        ob_start();
        imagepng($image);

        return (string) ob_get_clean();
    }

    /**
     * Colour at the centre of every cell, in layout order.
     *
     * @return list<array{int, int, int}>
     */
    private function tileColors(string $png, int $grid): array
    {
        $image = imagecreatefromstring($png);
        self::assertNotFalse($image);

        $size   = intdiv(600, $grid);
        $colors = [];
        for ($index = 0; $index < $grid * $grid; $index++) {
            $rgb      = imagecolorat(
                $image,
                ($index % $grid) * $size + intdiv($size, 2),
                intdiv($index, $grid) * $size + intdiv($size, 2)
            );
            $colors[] = [($rgb >> 16) & 0xFF, ($rgb >> 8) & 0xFF, $rgb & 0xFF];
        }

        return $colors;
    }
}
