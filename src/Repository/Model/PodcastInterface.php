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

interface PodcastInterface extends library_item
{
    public function getEpisodeCount(): int;

    public function getFeed(): string;

    public function getTitle(): string;

    public function getWebsite(): string;

    public function getDescription(): string;

    public function getLanguage(): string;

    public function getGenerator(): string;

    public function getCopyright(): string;

    public function getLink(): string;

    public function getLinkFormatted(): string;

    public function getCatalog(): int;

    public function getTitleFormatted(): string;

    public function getDescriptionFormatted(): string;

    public function getLanguageFormatted(): string;

    public function getCopyrightFormatted(): string;

    public function getGeneratorFormatted(): string;

    public function getWebsiteFormatted(): string;

    public function getLastSync(): int;

    public function getLastSyncFormatted(): string;

    public function getLastBuildDate(): int;

    public function getLastBuildDateFormatted(): string;
}
