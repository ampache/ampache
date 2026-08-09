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

namespace Ampache\Module\Util\Rss\Surrogate;

use Traversable;

interface RssItemInterface
{
    /**
     * Returns the itunes category of the item
     */
    public function getCategory(): string;

    /**
     * Returns the items image-url
     */
    public function getImageUrl(): string;

    /**
     * RSS channel language (RFC 5646), from the installation locale
     */
    public function getLanguage(): string;

    /**
     * Returns a link to the item
     */
    public function getLink(): string;

    /**
     * Returns all media-items which are associated with the item
     *
     * @return Traversable<array{
     *   title: string,
     *   guid: string,
     *   link: string,
     *   description: string,
     *   length: string,
     *   author: ?string,
     *   pubDate: ?string,
     *   type: ?string,
     *   size: ?string,
     *   url: ?string,
     *   season: ?string,
     *   season_name: ?string,
     *   episode: ?string
     * }>
     */
    public function getMedias(): Traversable;

    /**
     * Returns the name of the owner
     */
    public function getOwnerName(): string;

    /**
     * podcast:guid of this feed (UUIDv5 of its canonical token-less url)
     */
    public function getPodcastGuid(): string;

    /**
     * Returns a link to the feed url
     */
    public function getRssLink(): string;

    /**
     * Returns the items summary/description text
     */
    public function getSummary(): string;

    /**
     * Returns the item title
     */
    public function getTitle(): string;

    /**
     * Returns `true` if the item provides an image
     */
    public function hasImage(): bool;

    /**
     * Returns `true` if an item-owner is set
     */
    public function hasOwner(): bool;

    /**
     * Returns `true` if the item provides a summary/description text
     */
    public function hasSummary(): bool;
}
