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

/**
 * Shared data for the category link-bars above the browse and stats pages.
 *
 * They differ only in the action prefix their links use, so the flags that decide which categories appear
 * are worked out once and handed to whichever bar is being drawn.
 */
abstract class AbstractStatsFormView extends AbstractFormView
{
    public function __construct(
        string $webPath,
        private readonly string $filter,
        private readonly bool $byUser,
        private readonly bool $showArtist,
        private readonly bool $showAlbumArtist,
        private readonly bool $albumGroup,
        private readonly bool $podcastEnabled,
        private readonly bool $videoEnabled,
    ) {
        parent::__construct($webPath);
    }

    /**
     * `album` when discs are grouped, `album_disk` when they are browsed separately.
     */
    final public function getAlbumString(): string
    {
        return ($this->albumGroup) ? 'album' : 'album_disk';
    }

    /**
     * The `action` the page was reached with, used to mark the current category.
     */
    final public function getFilter(): string
    {
        return $this->filter;
    }

    final public function isByUser(): bool
    {
        return $this->byUser;
    }

    final public function isPodcastEnabled(): bool
    {
        return $this->podcastEnabled;
    }

    final public function isShowAlbumArtist(): bool
    {
        return $this->showAlbumArtist;
    }

    final public function isShowArtist(): bool
    {
        return $this->showArtist;
    }

    final public function isVideoEnabled(): bool
    {
        return $this->videoEnabled;
    }
}
