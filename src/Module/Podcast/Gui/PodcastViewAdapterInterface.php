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

namespace Ampache\Module\Podcast\Gui;

interface PodcastViewAdapterInterface
{
    public function getArt(): ?string;

    public function getDescription(): string;

    public function isRealUser(): bool;

    public function getUserFlags(): string;

    public function getRating(): string;

    public function getDirectplayButton(): string;

    public function canAppendNext(): bool;

    public function getAppendNextButton(): string;

    public function canBeManaged(): bool;

    public function getStatsIcon(): string;

    public function getRssLink(): string;

    public function getWebsite(): string;

    public function getWebsiteIcon(): string;

    public function getEditTitle(): string;

    public function getEditIcon(): string;

    public function getSyncButton(): string;

    public function canDelete(): bool;

    public function getDeleteIcon(): string;

    public function getEpisodeList(): string;

    public function getTitleFormatted(): string;
}
