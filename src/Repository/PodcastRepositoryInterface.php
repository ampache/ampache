<?php

declare(strict_types=1);

/**
 * vim:set softtabstop=3 shiftwidth=4 expandtab:
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

use Ampache\Repository\Model\Podcast;
use Traversable;

interface PodcastRepositoryInterface
{
    /**
     * Retrieve all podcast objects and maintain db-order
     *
     * @return Traversable<Podcast>
     */
    public function findAll(): Traversable;

    /**
     * Searches for an existing podcast object by the feed url
     */
    public function findByFeedUrl(string $feedUrl): ?Podcast;

    /**
     * Deletes a podcast
     */
    public function delete(Podcast $podcast): void;

    /**
     * Returns a new podcast item
     */
    public function prototype(): Podcast;

    /**
     * Persists the podcast-item in the database
     *
     * If the item is new, it will be created. Otherwise, an update will happen
     *
     * @return null|non-negative-int
     */
    public function persist(Podcast $podcast): ?int;

    /**
     * Retrieve a single podcast-item by its id
     */
    public function findById(int $podcastId): ?Podcast;
}
