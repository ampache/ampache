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

namespace Ampache\Module\Art\Generated\Template;

use Ampache\Module\Art\Generated\Palette;
use Ampache\Module\Art\Generated\Recipe;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * The tiles carry names typed by users straight into an XML document, and they are served as images to
 * anyone who can see the library. These pin the properties that keeps that safe and legible.
 */
class SvgTemplateTest extends TestCase
{
    /**
     * @return list<array{0: string, 1: string}>
     */
    public static function breakProvider(): array
    {
        return [
            // real names lean on these separators, and a single unbroken word used to run off the tile
            ['Lounge_Soiree_Firefox2', 'Lounge_Soiree_'],
            ['RMLL2017-Classical/Baroque', 'RMLL2017-'],
            ['Soundtrack/Ambient Vol.1', 'Soundtrack/'],
        ];
    }

    /**
     * @return list<array{0: string}>
     */
    public static function motifProvider(): array
    {
        return [['record'], ['medallion'], ['waveform'], ['tracklist']];
    }

    public function testAHostileNameCannotEscapeTheDocument(): void
    {
        $svg = (new DarkTemplate())->render(
            $this->recipe('</text><script>alert(1)</script><text x="0">'),
            300
        );

        $this->assertStringNotContainsString('<script>', $svg);
        $this->assertStringContainsString('&lt;script&gt;', $svg);

        $previous = libxml_use_internal_errors(true);
        $parsed   = simplexml_load_string($svg);
        libxml_use_internal_errors($previous);

        $this->assertNotFalse($parsed, 'the name must stay text, whatever it contains');
    }

    public function testAnEmptyNameStillDraws(): void
    {
        $svg = (new DarkTemplate())->render($this->recipe(''), 300);

        $this->assertStringStartsWith('<svg', $svg);
    }

    public function testAWordWithNoSeparatorIsStillBroken(): void
    {
        $drawn = $this->drawnText((new DarkTemplate())->render($this->recipe(str_repeat('x', 120)), 300));

        foreach ($drawn as $line) {
            $this->assertLessThan(30, mb_strlen($line), 'an unbreakable word must not run off the tile');
        }
    }

    #[DataProvider('motifProvider')]
    public function testEveryMotifProducesWellFormedXml(string $motif): void
    {
        $svg = (new DarkTemplate())->render($this->recipe('Night Cartography', $motif, ['Vela', 'Aster']), 300);

        $previous = libxml_use_internal_errors(true);
        $parsed   = simplexml_load_string($svg);
        libxml_use_internal_errors($previous);

        $this->assertNotFalse($parsed, 'a malformed tile would render as a broken image');
    }

    #[DataProvider('breakProvider')]
    public function testLongNamesBreakOnTheirSeparators(string $name, string $expected): void
    {
        $svg = (new DarkTemplate())->render($this->recipe($name), 300);

        $this->assertStringContainsString('>' . $expected . '<', $svg);
    }

    public function testTheAccessibleNameSurvivesTheSmallestSize(): void
    {
        $svg = (new DarkTemplate())->render($this->recipe('Night Cartography'), 48);

        $this->assertStringContainsString('aria-label="Night Cartography"', $svg);
    }

    public function testTheNameIsDroppedWhenItWouldBeUnreadable(): void
    {
        $template = new DarkTemplate();
        $recipe   = $this->recipe('Night Cartography');

        // the name is wrapped over several lines, so it is the joined text that has to carry it
        $small = implode(' ', $this->drawnText($template->render($recipe, 48)));
        $full  = implode(' ', $this->drawnText($template->render($recipe, 300)));

        $this->assertStringNotContainsString('Night', $small);
        $this->assertStringContainsString('Night Cartography', $full);
    }

    public function testTheSameRecipeAlwaysDrawsTheSameTile(): void
    {
        $template = new DarkTemplate();
        $recipe   = $this->recipe('Night Cartography', 'medallion');

        $this->assertSame($template->render($recipe, 300), $template->render($recipe, 300));
    }

    public function testTheTwoTemplatesDrawDifferentGrounds(): void
    {
        $recipe = $this->recipe('Night Cartography');

        $this->assertNotSame(
            (new DarkTemplate())->render($recipe, 300),
            (new LightTemplate())->render($recipe, 300)
        );
    }

    /**
     * The visible words go, the accessible name stays: a reader still has to be told which album this is.
     *
     * @return list<string>
     */
    private function drawnText(string $svg): array
    {
        preg_match_all('#<text[^>]*>([^<]*)</text>#', $svg, $matches);

        return $matches[1];
    }

    private function recipe(string $name, string $motif = 'record', array $voices = []): Recipe
    {
        return new Recipe(
            label: 'ALBUM',
            name: $name,
            subtitle: 'A SUBTITLE',
            motif: $motif,
            seed: 'seed',
            palette: Palette::forSeed('seed'),
            voices: $voices,
        );
    }
}
