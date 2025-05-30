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

namespace Ampache\Repository;

use Ampache\Repository\Model\Label;
use DateTimeInterface;

interface LabelRepositoryInterface
{
    public function findById(int $labelId): ?Label;

    /**
     * @return string[]
     */
    public function getByArtist(int $artistId): array;

    /**
     * Return the list of all available labels
     *
     * @return string[]
     */
    public function getAll(): array;

    public function lookup(string $labelName, int $labelId = 0): int;

    public function removeArtistAssoc(int $labelId, int $artistId): void;

    public function addArtistAssoc(int $labelId, int $artistId, DateTimeInterface $date): void;

    public function delete(int $labelId): void;

    /**
     * This cleans out unused labels
     */
    public function collectGarbage(): void;
}
