<?php

declare(strict_types=0);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2023
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
 * playable_item Interface
 *
 * This defines how the playable item file classes should
 * work, this lists all required functions and the expected
 * input
 */
interface playable_item
{
    /**
     * format
     *
     * Creates member variables for output
     * @param bool $details
     */
    public function format($details = true): void;

    /**
     * get_fullname
     *
     * Get the item full name.
     */
    public function get_fullname(): ?string;

    /**
     * get_link
     *
     * Get the item link.
     */
    public function get_link(): string;

    /**
     * Get item f_link.
     */
    public function get_f_link(): string;

    /**
     * get_parent
     * Return parent `object_type`, `object_id`; null otherwise.
     */
    public function get_parent(): ?array;

    /**
     * get_childrens
     *
     * Get direct childrens. Return an array of `object_type`, `object_id` childrens.
     * @return array
     */
    public function get_childrens(): array;

    /**
     * Search for direct children of an object
     * @param string $name
     * @return array
     */
    public function get_children($name): array;

    /**
     * get_medias
     *
     * Get all medias from all childrens. Return an array of `object_type`, `object_id` medias.
     * @param string $filter_type
     * @return mixed
     */
    public function get_medias($filter_type = null): array;

    public function getId(): int;

    public function has_art(): bool;

    public function get_description(): string;

    public function get_user_owner(): ?int;
}
