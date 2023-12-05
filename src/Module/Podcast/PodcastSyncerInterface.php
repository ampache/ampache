<?php

/**
 * vim:set softtabstop=3 shiftwidth=4 expandtab:
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

namespace Ampache\Module\Podcast;

use Ampache\Repository\Model\Podcast;
use SimpleXMLElement;

interface PodcastSyncerInterface
{
    /**
     * Update the feed and sync all new episodes
     */
    public function sync(Podcast $podcast, bool $gather = false): bool;

    /**
     * Add podcast episodes
     */
    public function addEpisodes(
        Podcast $podcast,
        SimpleXMLElement $episodes,
        int $lastSync = 0,
        bool $gather = false
    ): void;
}