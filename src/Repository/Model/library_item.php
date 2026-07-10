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

namespace Ampache\Repository\Model;

/**
 * library_item Interface
 *
 * This defines how the media file classes should
 * work, this lists all required functions and the expected
 * input
 */
interface library_item
{
    public function get_default_art_kind(): string;

    /**
     * get_description
     */
    public function get_description(): string;

    /**
     * get_fullname
     *
     * Get the item full name.
     */
    public function get_fullname(): ?string;

    /**
     * Get item keywords for metadata searches.
     * @return array<string, array{important: bool, label: string, value: string}>
     */
    public function get_keywords(): array;

    /**
     * get_link
     *
     * Get the item link.
     */
    public function get_link(): string;

    public function get_user_owner(): ?int;

    public function getId(): int;

    /**
     * Returns the media-type of the library-item
     */
    public function getMediaType(): LibraryItemEnum;

    public function has_art(): bool;

    public function isNew(): bool;

    /**
     * update
     * @param array<string, mixed> $data
     */
    public function update(array $data): ?int;
}
