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

namespace Ampache\Gui\Song;

use Ampache\Gui\System\ConfigViewAdapterInterface;
use Ampache\Gui\View\TemplateInterface;

interface SongViewAdapterInterface extends TemplateInterface
{
    public function canAppendNext(): bool;

    public function canAutoplayNext(): bool;

    public function canBeDeleted(): bool;

    public function canBeReordered(): bool;

    public function canDisplayStats(): bool;

    public function canDownload(): bool;

    public function canEditPlaylist(): bool;

    public function canPostShout(): bool;

    public function canShare(): bool;

    public function canToggleState(): bool;

    public function getAddToPlaylistIcon(): string;

    public function getAlbumDiskLink(): string;

    public function getAlbumLink(): string;

    public function getAppendNextButton(): string;

    public function getAdditionTime(): string;

    public function getArtistLink(): string;

    public function getAutoplayNextButton(): string;

    public function getAverageRating(): string;

    /**
     * Composer is a plain `song`.`composer` text field.
     */
    public function getComposer(): string;

    public function getConfig(): ConfigViewAdapterInterface;

    public function getCustomPlayActions(): string;

    public function getDeletionIcon(): string;

    public function getDeletionUrl(): string;

    public function getDirectplayButton(): string;

    public function getDisplayStatsIcon(): string;

    public function getDisplayStatsUrl(): string;

    public function getDownloadIcon(): string;

    public function getDownloadUrl(): string;

    public function getEditButtonTitle(): string;

    public function getEditIcon(): string;

    public function getExternalPlayIcon(): string;

    public function getExternalPlayUrl(): string;

    public function getGenre(): string;

    public function getId(): int;

    public function getLicenseLink(): string;

    public function getMoods(): string;

    public function getNumberPlayed(): int;

    public function getNumberSkipped(): int;

    public function getPlayDuration(): string;

    public function getPostShoutIcon(): string;

    public function getPostShoutUrl(): string;

    /**
     * @return array<string, float|int|string|null>
     */
    public function getProperties(): array;

    public function getRating(): string;

    public function getRefreshIcon(): string;

    public function getReorderIcon(): string;

    public function getShareUi(): string;

    public function getSongLink(): string;

    public function getTemporaryPlaylistButton(): string;

    public function getToggleStateButton(): string;

    public function getTrackNumber(): string;

    public function getUpdateFromTagsUrl(): string;

    public function getUserFlags(): string;

    public function getWaveformUrl(): string;

    public function getYear(): int;

    /**
     * A waveform is rendered on demand, so this is false when there is nothing to render it from.
     */
    public function hasWaveform(): bool;

    public function isEditable(): bool;
}
