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
 *
 */

namespace Ampache\Gui\Album;

interface AlbumViewAdapterInterface
{
    public function getId(): int;

    public function getAlbumSuiteIds(): string;

    public function getRating(): string;

    public function getAverageRating(): string;

    public function getUserFlags(): string;

    public function getArt(): string;

    public function canAutoplayNext(): bool;

    public function canAppendNext(): bool;

    public function getDirectplayButton(): string;

    public function getAutoplayNextButton(): string;

    public function getAppendNextButton(): string;

    public function getAddToTemporaryPlaylistButton(): string;

    public function getRandomToTemporaryPlaylistButton(): string;

    public function canPostShout(): bool;

    public function getPostShoutUrl(): string;

    public function getPostShoutIcon(): string;

    public function canShare(): bool;

    public function getShareUi(): string;

    public function canBatchDownload(): bool;

    public function getBatchDownloadUrl(): string;

    public function getBatchDownloadIcon(): string;

    public function isEditable(): bool;

    public function getEditButtonTitle(): string;

    public function getEditIcon(): string;

    public function getDeletionUrl(): string;

    public function getDeletionIcon(): string;

    public function canBeDeleted(): bool;

    public function getAddToPlaylistIcon(): string;

    public function getPlayedTimes(): int;

    public function getAlbumUrl(): string;

    public function getAlbumLink(): string;

    public function getArtistLink(): string;

    public function canShowYear(): bool;

    public function getDisplayYear(): int;

    public function getGenre(): string;

    public function getSongCount(): int;
}
