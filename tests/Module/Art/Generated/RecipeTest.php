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

use PHPUnit\Framework\TestCase;

/**
 * The fingerprint is what an ETag is built from, so it has to follow the drawing: unchanged while the
 * item is unchanged, different as soon as anything the tile shows has moved.
 */
class RecipeTest extends TestCase
{
    public function testChangingTheVoicesChangesTheFingerprint(): void
    {
        $this->assertNotSame(
            $this->recipe('Late Shift', ['Vela'])->fingerprint(),
            $this->recipe('Late Shift', ['Vela', 'Aster'])->fingerprint(),
            'adding a track from another artist recolours a playlist tile'
        );
    }

    public function testRenamingTheItemChangesTheFingerprint(): void
    {
        $this->assertNotSame(
            $this->recipe()->fingerprint(),
            $this->recipe('Day Cartography')->fingerprint(),
            'a renamed album must not keep serving its old tile from cache'
        );
    }

    public function testTheFingerprintIsStableForTheSameDrawing(): void
    {
        $this->assertSame($this->recipe()->fingerprint(), $this->recipe()->fingerprint());
    }

    private function recipe(string $name = 'Night Cartography', array $voices = []): Recipe
    {
        return new Recipe(
            label: 'ALBUM',
            name: $name,
            subtitle: 'ORCHESTRE DU SOIR',
            motif: 'record',
            seed: 'Orchestre du Soir',
            palette: Palette::forSeed('Orchestre du Soir'),
            voices: $voices,
        );
    }
}
