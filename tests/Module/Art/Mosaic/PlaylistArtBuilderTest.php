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

use PHPUnit\Framework\TestCase;

class PlaylistArtBuilderTest extends TestCase
{
    private PlaylistArtBuilder $subject;

    public function testBuildCreatesSquareMosaicFromFourImages(): void
    {
        $images = array_fill(0, 4, $this->createImage(500, 250));

        $this->assertMosaic($this->subject->build($images));
    }

    public function testBuildCreatesSquareMosaicFromNineImages(): void
    {
        $images = array_fill(0, 9, $this->createImage(120, 400));

        $this->assertMosaic($this->subject->build($images));
    }

    public function testBuildIgnoresUndecodableImagesWhenCountingTiles(): void
    {
        // Three good covers plus junk isn't enough for a mosaic.
        $images = [$this->createImage(300, 300), 'junk', $this->createImage(300, 300), 'junk', $this->createImage(300, 300)];

        static::assertNull($this->subject->build($images));
    }

    public function testBuildReturnsNullWhenNoImageIsDecodable(): void
    {
        static::assertNull($this->subject->build(['not-an-image', '', 'still not']));
    }

    public function testBuildReturnsNullWithFewerThanFourImages(): void
    {
        $images = [$this->createImage(300, 300), $this->createImage(300, 300), $this->createImage(300, 300)];

        static::assertNull($this->subject->build($images));
    }

    protected function setUp(): void
    {
        $this->subject = new PlaylistArtBuilder();
    }

    private function assertMosaic(?string $result): void
    {
        static::assertNotNull($result);

        $info = getimagesizefromstring($result);
        static::assertNotFalse($info);
        static::assertSame(IMAGETYPE_PNG, $info[2]);
        static::assertSame(600, $info[0]);
        static::assertSame(600, $info[1]);
    }

    private function createImage(int $width, int $height): string
    {
        $image = imagecreatetruecolor($width, $height);
        static::assertNotFalse($image);

        ob_start();
        imagejpeg($image);

        return (string) ob_get_clean();
    }
}
