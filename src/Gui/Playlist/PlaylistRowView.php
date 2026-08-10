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

use Ampache\Gui\System\ConfigViewAdapterInterface;
use Ampache\Gui\View\AbstractView;
use Override;

/**
 * One row of a playlist browse.
 *
 * Built fresh per row rather than reused with reassigned context, so a value from the previous row cannot
 * survive into the next one.
 */
final class PlaylistRowView extends AbstractView
{
    public function __construct(
        private readonly PlaylistViewAdapterInterface $playlist,
        private readonly ConfigViewAdapterInterface $config,
        private readonly bool $usingRatings,
        private readonly bool $isShowArt,
        private readonly bool $isShowPlaylistAdd,
        private readonly string $classCover,
    ) {}

    public function getClassCover(): string
    {
        return $this->classCover;
    }

    public function getConfig(): ConfigViewAdapterInterface
    {
        return $this->config;
    }

    public function getPlaylist(): PlaylistViewAdapterInterface
    {
        return $this->playlist;
    }

    public function isShowArt(): bool
    {
        return $this->isShowArt;
    }

    public function isShowPlaylistAdd(): bool
    {
        return $this->isShowPlaylistAdd;
    }

    public function isUsingRatings(): bool
    {
        return $this->usingRatings;
    }

    #[Override]
    protected function templateFile(): string
    {
        return $this->findTemplate('playlist_row.phtml');
    }
}
