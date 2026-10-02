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
use Ampache\Module\Art\Generated\GeneratedArtServiceInterface;
use Ampache\Module\Art\Generated\Template\TemplateInterface;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use ReflectionMethod;

/**
 * The suffix this builds is the only thing that makes a page ask for a drawn tile, so an install that never
 * gets it never sees the feature at all, however the preference is set.
 */
class GeneratedArtQueryTest extends TestCase
{
    /**
     * A custom placeholder used to turn the whole feature off here. The two settings answer different
     * questions: `nosvg=1` already serves the placeholder to anything that cannot read an svg.
     */
    public function testACustomPlaceholderNoLongerSuppressesTheDrawnTile(): void
    {
        $this->bootDic(true);
        AmpConfig::set('custom_blankalbum', 'https://example.org/blank.png', true);

        self::assertStringContainsString('generate=1', $this->query());
    }

    public function testNothingIsAskedForWhileTheFeatureIsOff(): void
    {
        $this->bootDic(false);
        AmpConfig::set('custom_blankalbum', '', true);

        self::assertSame('', $this->query());
    }

    public function testTheTemplateRidesAlongSoAChangeOfMotifBustsTheCache(): void
    {
        $this->bootDic(true);
        AmpConfig::set('custom_blankalbum', '', true);

        self::assertStringContainsString('template=dark', $this->query());
    }

    private function bootDic(bool $isEnabled): void
    {
        $template = $this->createMock(TemplateInterface::class);
        $template->method('getId')->willReturn('dark');

        $generatedArt = $this->createMock(GeneratedArtServiceInterface::class);
        $generatedArt->method('isEnabled')->willReturn($isEnabled);
        $generatedArt->method('resolveTemplate')->willReturn($template);

        $dic = $this->createMock(ContainerInterface::class);
        $dic->method('get')->willReturn($generatedArt);

        $GLOBALS['dic'] = $dic;
    }

    private function query(): string
    {
        /** @var string $result */
        $result = new ReflectionMethod(Art::class, 'generated_art_query')->invoke(null);

        return $result;
    }
}
