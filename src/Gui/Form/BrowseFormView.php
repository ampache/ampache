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

namespace Ampache\Gui\Form;

use Override;

/**
 * The browse category bar; it lists a few types the stats bars do not.
 */
final class BrowseFormView extends AbstractStatsFormView
{
    public function __construct(
        string $webPath,
        string $filter,
        bool $showArtist,
        bool $showAlbumArtist,
        bool $albumGroup,
        bool $podcastEnabled,
        bool $videoEnabled,
        private readonly bool $folderEnabled,
        private readonly bool $labelEnabled,
        private readonly bool $broadcastEnabled,
        private readonly bool $liveStreamEnabled,
    ) {
        parent::__construct($webPath, $filter, false, $showArtist, $showAlbumArtist, $albumGroup, $podcastEnabled, $videoEnabled);
    }

    public function isBroadcastEnabled(): bool
    {
        return $this->broadcastEnabled;
    }

    public function isFolderEnabled(): bool
    {
        return $this->folderEnabled;
    }

    public function isLabelEnabled(): bool
    {
        return $this->labelEnabled;
    }

    public function isLiveStreamEnabled(): bool
    {
        return $this->liveStreamEnabled;
    }

    #[Override]
    protected function templateFile(): string
    {
        return $this->findTemplate('form/browse.phtml');
    }
}
