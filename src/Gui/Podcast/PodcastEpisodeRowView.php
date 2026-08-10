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

namespace Ampache\Gui\Podcast;

use Ampache\Gui\View\AbstractView;
use Ampache\Repository\Model\Podcast_Episode;
use Override;

/**
 * One row of a podcast-episode browse.
 *
 * The access checks and config reads the template used to make for itself are decided by the caller.
 */
final class PodcastEpisodeRowView extends AbstractView
{
    public function __construct(
        private readonly Podcast_Episode $episode,
        private readonly string $webPath,
        private readonly string $classCover,
        private readonly string $classTime,
        private readonly string $classCounter,
        private readonly int $browseId,
        private readonly bool $mashup,
        private readonly bool $tableView,
        private readonly bool $gridView,
        private readonly bool $showRatings,
        private readonly bool $showPlayedTimes,
        private readonly bool $directplayEnabled,
        private readonly bool $canAddToPlaylist,
        private readonly bool $canDownload,
        private readonly bool $canEdit,
        private readonly bool $canDelete,
    ) {}

    public function canAddToPlaylist(): bool
    {
        return $this->canAddToPlaylist;
    }

    public function canDelete(): bool
    {
        return $this->canDelete;
    }

    public function canDownload(): bool
    {
        return $this->canDownload && !empty($this->episode->file);
    }

    public function canEdit(): bool
    {
        return $this->canEdit;
    }

    /**
     * An episode with no downloaded file has nothing to stream, so the play buttons are withheld.
     */
    public function canPlay(): bool
    {
        return $this->directplayEnabled && !empty($this->episode->file);
    }

    /**
     * @return array{width: int, height: int}
     */
    public function getArtSize(): array
    {
        return ($this->gridView)
            ? ['width' => 150, 'height' => 150]
            : ['width' => 100, 'height' => 100];
    }

    public function getBrowseId(): int
    {
        return $this->browseId;
    }

    public function getClassCounter(): string
    {
        return $this->classCounter;
    }

    public function getClassCover(): string
    {
        return $this->classCover;
    }

    public function getClassTime(): string
    {
        return $this->classTime;
    }

    public function getEpisode(): Podcast_Episode
    {
        return $this->episode;
    }

    public function getWebPath(): string
    {
        return $this->webPath;
    }

    public function isMashup(): bool
    {
        return $this->mashup;
    }

    public function isShowPlayedTimes(): bool
    {
        return $this->showPlayedTimes;
    }

    public function isShowRatings(): bool
    {
        return $this->showRatings;
    }

    public function isTableView(): bool
    {
        return $this->tableView;
    }

    #[Override]
    protected function templateFile(): string
    {
        return $this->findTemplate('podcast_episode_row.phtml');
    }
}
