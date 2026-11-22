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

namespace Ampache\Module\Art;

use Ampache\Config\AmpConfig;
use Ampache\Config\ConfigContainerInterface;
use Ampache\Module\Art\Generated\GeneratedArtServiceInterface;
use Ampache\Repository\ImageRepositoryInterface;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

/**
 * display() must print exactly what render() builds, so the two can never drift on what "nothing" means.
 */
class ArtRenderTest extends TestCase
{
    public function testAnUnknownTypeRendersNothingAndDisplayAgrees(): void
    {
        $size = ['width' => 100, 'height' => 100];

        self::assertSame('', Art::render('not_a_type', 1, 'x', $size));

        ob_start();
        $shown   = Art::display('not_a_type', 1, 'x', $size);
        $printed = (string) ob_get_clean();

        self::assertSame('', $printed);
        self::assertFalse($shown);
    }

    public function testDisplayPrintsExactlyWhatRenderBuilds(): void
    {
        $size = ['width' => 100, 'height' => 100];

        $html = Art::render('album', 1, 'A & B', $size, '/link');

        ob_start();
        $shown   = Art::display('album', 1, 'A & B', $size, '/link');
        $printed = (string) ob_get_clean();

        self::assertNotSame('', $html);
        self::assertStringContainsString('<div class="item_art"><a href="/link" title="A &amp; B">', $html);
        self::assertSame($html, $printed);
        self::assertTrue($shown);
    }

    public function testNoArtAndNoDefaultRendersNothingAndDisplayAgrees(): void
    {
        $size = ['width' => 100, 'height' => 100];

        self::assertSame('', Art::render('album', 1, 'x', $size, '/link', false));

        ob_start();
        $shown   = Art::display('album', 1, 'x', $size, '/link', false);
        $printed = (string) ob_get_clean();

        self::assertSame('', $printed);
        self::assertFalse($shown);
    }

    protected function setUp(): void
    {
        // a mocked container lets render() run its full path without a database, the way GeneratedArtQueryTest does
        $image = $this->createMock(ImageRepositoryInterface::class);
        $image->method('getOriginalRowsByObjectIds')->willReturn([]);
        $config = $this->createMock(ConfigContainerInterface::class);
        $config->method('isFeatureEnabled')->willReturn(true);
        $generated = $this->createMock(GeneratedArtServiceInterface::class);
        $generated->method('isEnabled')->willReturn(false);
        $dic = $this->createMock(ContainerInterface::class);
        $dic->method('get')->willReturnMap([
            [ImageRepositoryInterface::class, $image],
            [ConfigContainerInterface::class, $config],
            [GeneratedArtServiceInterface::class, $generated],
        ]);
        $GLOBALS['dic'] = $dic;
        AmpConfig::set('web_path', '/music', true);
        AmpConfig::set('custom_blankalbum', '', true);
        AmpConfig::set('upscale_images', true, true);
    }
}
