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

namespace Ampache\Repository\Model;

use Ampache\Module\Playback\Stream_Playlist;

interface ShareInterface
{
    public function getPublicUrl(): string;

    public function getAllowStream(): int;

    public function getAllowDownload(): int;

    public function getCreationDate(): int;

    public function getLastvisitDate(): int;

    public function getExpireDays(): int;

    public function getMaxCounter(): int;

    public function getCounter(): int;

    public function getSecret(): string;

    public function getDescription(): string;

    public function getObjectId(): int;

    public function getObjectType(): string;

    public function getUserId(): int;

    public function getObjectUrl(): string;

    public function getObject(): playable_item;

    public function getObjectName(): string;

    public function getUserName(): string;

    public function getLastVisitDateFormatted(): string;

    public function getCreationDateFormatted(): string;

    public function create_fake_playlist(): Stream_Playlist;
}
