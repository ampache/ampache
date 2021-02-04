<?php
/*
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 *  LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
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
 *
 */

namespace Ampache\Gui\Stats;

interface CatalogStatsInterface
{
    public function getConnectedCount(): int;

    public function getUserCount(): int;

    public function getAlbumCount(): int;

    public function getArtistCount(): int;

    public function getSongCount(): int;

    public function getPodcastCount(): int;

    public function getPodcastEpisodeCount(): int;

    public function getGenreCount(): int;

    public function getCatalogSize(): string;

    public function getPlayTime(): string;

    public function getItemCount(): int;

    public function getVideoCount(): int;
}
