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

namespace Ampache\Module\Art\Generated;

/**
 * Colour pairs for generated art.
 *
 * The pairs are picked by hand rather than derived from the name: computing a hue straight from a hash
 * gives muddy ochres, because nothing keeps saturation and lightness in a range that reads well.
 */
final class Palette
{
    /** @var list<array{ground: string, accent: string, face: string}> */
    private const array PAIRS = [
        ['ground' => '#121826', 'accent' => '#567cff', 'face' => '#ffffff'],
        ['ground' => '#0e1c1a', 'accent' => '#00c49a', 'face' => '#f0fffa'],
        ['ground' => '#200e1c', 'accent' => '#ff5c8d', 'face' => '#fff0f6'],
        ['ground' => '#261606', 'accent' => '#ff9626', 'face' => '#fff6eb'],
        ['ground' => '#14141a', 'accent' => '#c484ff', 'face' => '#f8f4ff'],
        ['ground' => '#0a1a22', 'accent' => '#36c4de', 'face' => '#ecfcff'],
        ['ground' => '#221010', 'accent' => '#ec4848', 'face' => '#ffeeee'],
        ['ground' => '#181a0e', 'accent' => '#c6de3c', 'face' => '#faffe6'],
    ];

    /**
     * The accent alone, used when one tile shows colours borrowed from several artists.
     */
    public static function accentForSeed(string $seed): string
    {
        return self::forSeed($seed)['accent'];
    }

    /**
     * @return array{ground: string, accent: string, face: string}
     */
    public static function forSeed(string $seed): array
    {
        return self::PAIRS[crc32($seed) % count(self::PAIRS)];
    }
}
