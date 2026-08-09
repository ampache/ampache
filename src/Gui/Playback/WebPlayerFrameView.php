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

namespace Ampache\Gui\Playback;

use Ampache\Config\AmpConfig;
use Ampache\Gui\View\AbstractView;
use Ampache\Module\Playback\Stream_Playlist;
use Override;

/**
 * The standalone document `Stream_Playlist::create_web_player()` returns, which drives the parent frame.
 */
final class WebPlayerFrameView extends AbstractView
{
    public function __construct(
        private readonly string $webPath,
        private readonly Stream_Playlist $playlist,
    ) {}

    public function appendsMedia(): bool
    {
        return array_key_exists('append', $_REQUEST);
    }

    public function clearsBeforeUnload(): bool
    {
        return (bool) AmpConfig::get('webplayer_confirmclose');
    }

    public function getEmbedUrl(): string
    {
        return $this->webPath . '/web_player_embedded.php?playlist_id=' . $this->playlist->id;
    }

    public function getPlaylist(): Stream_Playlist
    {
        return $this->playlist;
    }

    public function getSiteTitle(): string
    {
        return (string) AmpConfig::get('site_title');
    }

    public function playsNext(): bool
    {
        return !$this->appendsMedia() && array_key_exists('playnext', $_REQUEST);
    }

    #[Override]
    protected function templateFile(): string
    {
        return $this->findTemplate('playback/web_player_frame.phtml');
    }
}
