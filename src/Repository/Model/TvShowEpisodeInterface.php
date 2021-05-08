<?php
/*
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright 2001 - 2020 Ampache.org
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
 */

namespace Ampache\Repository\Model;

interface TvShowEpisodeInterface extends
        Media,
        library_item,
        GarbageCollectibleInterface,
        MediaFileInterface,
        PlayableMediaInterface
{
    public function getSummary(): string;

    public function getEpisodeNumber(): int;

    public function getOriginalName(): string;

    public function getFullTitle(): string;

    public function getSeasonId(): int;

    public function getLinkFormatted(): string;

    public function getTVShowSeason(): TVShow_Season;

    /**
     * get_release_item_art
     * @return array
     */
    public function get_release_item_art();
}
