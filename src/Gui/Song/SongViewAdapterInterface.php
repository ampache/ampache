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

namespace Ampache\Gui\Song;

interface SongViewAdapterInterface
{
    public function getId(): int;

    public function getRating(): string;

    public function getAverageRating(): string;

    public function getUserFlags(): string;

    public function getWaveformUrl(): string;

    public function canAutoplayNext(): bool;

    public function canAppendNext(): bool;

    public function getDirectplayButton(): string;

    public function getAutoplayNextButton(): string;

    public function getAppendNextButton(): string;

    public function getCustomPlayActions(): string;

    public function getTemporaryPlaylistButton(): string;

    public function canPostShout(): bool;

    public function getPostShoutUrl(): string;

    public function getPostShoutIcon(): string;

    public function canShare(): bool;

    public function getShareUi(): string;

    public function canDownload(): bool;

    public function getExternalPlayUrl(): string;

    public function getExternalPlayIcon(): string;

    public function getDownloadUrl(): string;

    public function getDownloadIcon(): string;

    public function canDisplayStats(): bool;

    public function getDisplayStatsUrl(): string;

    public function getUpdateFromTagsUrl(): string;

    public function getDisplayStatsIcon(): string;

    public function getRefreshIcon(): string;

    public function isEditable(): bool;

    public function getEditButtonTitle(): string;

    public function getEditIcon(): string;

    public function canToggleState(): bool;

    public function getToggleStateButton(): string;

    public function getDeletionUrl(): string;

    public function getDeletionIcon(): string;

    public function canBeDeleted(): bool;

    /**
     * @return array<string, float|int|string|null>
     */
    public function getProperties(): array;
}
