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
use Ampache\Module\Playback\WebPlayer;
use Ampache\Module\Util\AjaxUriRetrieverInterface;
use Ampache\Module\Util\Ui;
use Override;

/**
 * The whole document the embedded player iframe loads, wrapping `WebPlayerView` in a head and body.
 */
final class WebPlayerPageView extends AbstractView
{
    private ?Stream_Playlist $playlist = null;

    public function __construct(
        private readonly string $webPath,
        private readonly AjaxUriRetrieverInterface $ajaxUriRetriever,
        private readonly bool $mayUse,
        private readonly bool $iframed = true,
        private readonly bool $isShare = false,
        private readonly bool $embed = false,
    ) {}

    public function getLogoUrl(): string
    {
        return Ui::get_logo_url();
    }

    public function getSiteTitle(): string
    {
        return (string) AmpConfig::get('site_title');
    }

    /**
     * The player itself, told what kind of stream it is holding.
     */
    public function renderPlayer(): string
    {
        $playlist = $this->getPlaylist();

        return (new WebPlayerView(
            $this->webPath,
            $this->ajaxUriRetriever,
            $playlist,
            WebPlayer::is_playlist_video($playlist),
            WebPlayer::is_playlist_democratic($playlist),
            WebPlayer::is_playlist_random($playlist),
            $this->isShare,
            $this->iframed,
            $this->mayUse,
            $this->embed
        ))->render();
    }

    #[Override]
    protected function templateFile(): string
    {
        return $this->findTemplate('playback/web_player_page.phtml');
    }

    /**
     * A share brings its own playlist; everything else names one in the request, and `-1` is the
     * empty playlist the player needs when neither applies.
     */
    private function getPlaylist(): Stream_Playlist
    {
        if ($this->playlist instanceof Stream_Playlist) {
            return $this->playlist;
        }

        $streamId = ($this->isShare) ? null : ($_REQUEST['playlist_id'] ?? null);

        return $this->playlist = (is_string($streamId) || is_int($streamId))
            ? new Stream_Playlist($streamId)
            : new Stream_Playlist(-1);
    }
}
