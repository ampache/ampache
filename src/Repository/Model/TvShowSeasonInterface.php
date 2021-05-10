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

interface TvShowSeasonInterface extends library_item
{
    public function getTvShowId(): int;

    public function getSeasonNumber(): int;

    /**
     * gets all episodes for this tv show season
     * @return int[]
     */
    public function getEpisodeIds(): array;

    /**
     * @return array{episode_count?: int, catalog_id?: int}
     */
    public function getExtraInfo(): array;

    public function getEpisodeCount(): int;

    public function getCatalogId(): int;

    public function getLink(): string;

    public function getLinkFormatted(): string;

    public function getNameFormatted(): string;

    public function getTvShow(): TvShow;

    public function remove(): bool;
}
