<?php
/*
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 *  LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright 2001 - 2022 Ampache.org
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

declare(strict_types=0);

namespace Ampache\Gui\Stats;

final class CatalogStats implements CatalogStatsInterface
{
    private array $stats;

    public function __construct(
        array $stats
    ) {
        $this->stats = $stats;
    }

    public function getConnectedCount(): int
    {
        return $this->stats['connected'] ?? 0;
    }

    public function getUserCount(): int
    {
        return $this->stats['user'] ?? 0;
    }

    public function getAlbumCount(): int
    {
        return $this->stats['album'] ?? 0;
    }

    public function getAlbumDiskCount(): int
    {
        return $this->stats['album_disk'] ?? 0;
    }

    public function getArtistCount(): int
    {
        return $this->stats['artist'] ?? 0;
    }

    public function getSongCount(): int
    {
        return $this->stats['song'] ?? 0;
    }

    public function getPodcastCount(): int
    {
        return $this->stats['podcast'] ?? 0;
    }

    public function getPodcastEpisodeCount(): int
    {
        return $this->stats['podcast_episode'] ?? 0;
    }

    public function getGenreCount(): int
    {
        return $this->stats['tags'] ?? 0;
    }

    public function getCatalogSize(): string
    {
        return $this->stats['formatted_size'] ?? '';
    }

    public function getPlayTime(): string
    {
        return $this->stats['time_text'] ?? '';
    }

    public function getItemCount(): int
    {
        return $this->stats['items'] ?? 0;
    }

    public function getVideoCount(): int
    {
        return $this->stats['video'] ?? 0;
    }
}
