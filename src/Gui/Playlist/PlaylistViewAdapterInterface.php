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

namespace Ampache\Gui\Playlist;

interface PlaylistViewAdapterInterface
{
    public function canAppendNext(): bool;

    public function canAutoplayNext(): bool;

    public function canBatchDownload(): bool;

    public function canBeDeleted(): bool;

    public function canBeRefreshed(): bool;

    public function canShare(): bool;

    public function getAddToPlaylistIcon(): string;

    public function getAddToTemporaryPlaylistButton(): string;

    public function getAppendNextButton(): string;

    public function getArt(): void;

    public function getAutoplayNextButton(): string;

    public function getAverageRating(): string;

    public function getBatchDownloadIcon(): string;

    public function getBatchDownloadUrl(): string;

    public function getDeletionButton(): string;

    public function getDirectplayButton(): string;

    public function getEditButtonTitle(): string;

    public function getEditIcon(): string;

    public function getFullname(): string;

    public function getId(): int;

    public function getLastUpdate(): string;

    public function getMediaCount(): int;

    public function getPlaylistLink(): string;

    public function getPlaylistUrl(): string;

    public function getRandomPlayPlaylistButton(): string;

    public function getRandomToTemporaryPlaylistButton(): string;

    public function getRating(): string;

    public function getRefreshIcon(): string;

    public function getRefreshUrl(): string;

    public function getShareUi(): string;

    public function getUserFlags(): string;

    public function getUsername(): string;

    public function isEditable(): bool;

    public function isPrivate(): bool;
}
