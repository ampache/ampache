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

interface PodcastEpisodeInterface extends
    Media,
    library_item,
    MediaFileInterface,
    PlayableMediaInterface
{
    public function getPodcast(): PodcastInterface;

    public function getLink(): string;

    public function getSource(): string;

    public function getLinkFormatted(): string;

    public function getStateFormatted(): string;

    public function getPublicationDate(): int;

    public function getPublicationDateFormatted(): string;

    public function getAuthor(): string;

    public function getAuthorFormatted(): string;

    public function getWebsite(): string;

    public function getWebsiteFormatted(): string;

    public function getGuid(): string;

    public function getState(): string;

    public function getSizeFormatted(): string;

    public function getCategory(): string;

    public function getCategoryFormatted(): string;

    public function getDescription(): string;

    public function getDescriptionFormatted(): string;

    public function getTitleFormatted(): string;

    public function getPlayed(): int;

    public function getObjectCount(): ?int;

    public function getTime(): int;

    public function hasFile(): bool;

    public function getFile(): string;

    public function getBitrate(): ?int;

    public function getRate(): ?int;

    public function getMode(): ?string;
}
