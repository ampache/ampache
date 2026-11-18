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
use Ampache\MockeryTestCase;

class ArtCheckDimensionsTest extends MockeryTestCase
{
    public function testCheckDimensionsFallsBackToAlbumBoundsWhenTypeHasNoOverride(): void
    {
        AmpConfig::set('album_art_max_width', 100, true);
        AmpConfig::set('artist_art_max_width', null, true);

        self::assertFalse(Art::check_dimensions(['width' => 200, 'height' => 50], 'artist'));
    }

    public function testCheckDimensionsFallsBackToLegacyAlbumBoundsWhenGenericIsUnset(): void
    {
        AmpConfig::set('album_art_max_width', 100, true);
        AmpConfig::set('art_max_width', null, true);

        self::assertFalse(Art::check_dimensions(['width' => 200, 'height' => 50], 'artist'));
    }

    public function testCheckDimensionsPrefersGenericArtBoundsOverLegacyAlbumBounds(): void
    {
        AmpConfig::set('album_art_max_width', 1000, true);
        AmpConfig::set('art_max_width', 100, true);

        self::assertFalse(Art::check_dimensions(['width' => 200, 'height' => 50], 'artist'));
    }

    public function testCheckDimensionsStillAppliesAlbumBoundsForAlbumType(): void
    {
        AmpConfig::set('album_art_max_width', 100, true);
        AmpConfig::set('artist_art_max_width', 300, true);

        self::assertFalse(Art::check_dimensions(['width' => 200, 'height' => 50], 'album'));
    }

    public function testCheckDimensionsUsesPerTypeOverrideWhenSet(): void
    {
        AmpConfig::set('album_art_max_width', 100, true);
        AmpConfig::set('artist_art_max_width', 300, true);

        self::assertTrue(Art::check_dimensions(['width' => 200, 'height' => 50], 'artist'));
    }

    protected function tearDown(): void
    {
        AmpConfig::set('album_art_max_width', null, true);
        AmpConfig::set('artist_art_max_width', null, true);
        AmpConfig::set('art_max_width', null, true);

        parent::tearDown();
    }
}
