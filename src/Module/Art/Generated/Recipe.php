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
 * Everything a template needs to draw one tile, with no database or model behind it.
 *
 * Keeping this separate from the templates is what lets a second renderer (a raster one, later) reuse
 * the same decisions instead of repeating them.
 */
final readonly class Recipe
{
    /**
     * @param string $label the type shown above the name, already translated
     * @param string $motif which drawing to use: record, medallion, waveform, tracklist
     * @param string $seed drives every deterministic choice, so a tile never changes on its own
     * @param list<string> $voices names whose accents colour a tracklist, empty for the other motifs
     * @param array{ground: string, accent: string, face: string} $palette
     */
    public function __construct(
        public string $label,
        public string $name,
        public string $subtitle,
        public string $motif,
        public string $seed,
        public array $palette,
        public array $voices = [],
    ) {}

    /**
     * Identifies the drawing rather than the object: two runs that would draw the same tile share it,
     * which is exactly what an ETag needs.
     */
    public function fingerprint(): string
    {
        return md5(implode("\0", [
            $this->label,
            $this->name,
            $this->subtitle,
            $this->motif,
            $this->seed,
            $this->palette['accent'],
            implode('|', $this->voices),
        ]));
    }
}
