<?php

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2024
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

use Ampache\Repository\Model\Art;

interface ArtCleanupInterface
{
    /**
     * look for art in the image table that doesn't fit min or max dimensions and delete it
     */
    public function cleanup(): void;

    /**
     * This cleans up art that no longer has a corresponding object
     */
    public function collectGarbageForObject(string $object_type, int $object_id): void;

    /**
     * This cleans up art that no longer has a corresponding object
     */
    public function collectGarbage(): void;

    /**
     * This resets the art in the database
     */
    public function deleteForArt(Art $art): void;
}
