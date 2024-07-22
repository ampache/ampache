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

namespace Ampache\Module\Util\Rss\Surrogate;

use Traversable;

interface RssItemInterface
{
    /**
     * Returns the item title
     */
    public function getTitle(): string;

    /**
     * Returns `true` if the item provides an image
     */
    public function hasImage(): bool;

    /**
     * Returns the items image-url
     */
    public function getImageUrl(): string;

    /**
     * Returns `true` if the item provides a summary/description text
     */
    public function hasSummary(): bool;

    /**
     * Returns the items summary/description text
     */
    public function getSummary(): string;

    /**
     * Returns `true` if an item-owner is set
     */
    public function hasOwner(): bool;

    /**
     * Returns the name of the owner
     */
    public function getOwnerName(): string;

    /**
     * Returns all media-items which are associated with the item
     *
     * @return Traversable<array{
     *   title: string,
     *   guid: string,
     *   length: string,
     *   author: null|string,
     *   pubDate: null|string,
     *   type: null|string,
     *   size: null|string,
     *   url: null|string
     * }>
     */
    public function getMedias(): Traversable;
}
