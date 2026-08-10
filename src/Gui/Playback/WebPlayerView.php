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
use Ampache\Module\System\Preference;
use Ampache\Module\Util\AjaxUriRetrieverInterface;
use JsonException;
use Override;

/**
 * The jPlayer markup and its init script.
 *
 * The control labels are plain strings the template escapes for html; they were `addslashes()`'d before,
 * which puts a literal backslash into a `title=` whenever a translation carries an apostrophe.
 */
final class WebPlayerView extends AbstractView
{
    public function __construct(
        private readonly string $webPath,
        private readonly AjaxUriRetrieverInterface $ajaxUriRetriever,
        private readonly Stream_Playlist $playlist,
        private readonly bool $isVideo,
        private readonly bool $isDemocratic,
        private readonly bool $isRandom,
        private readonly bool $isShare,
        private readonly bool $iframed,
        private readonly bool $mayUse,
        private readonly bool $embed = false,
        private readonly bool $playerFragment = false,
    ) {}

    /**
     * A share autoplays only when its link asked for it; everything else starts on its own.
     */
    public function autoplays(): bool
    {
        return (!$this->isShare)
            || (array_key_exists('autoplay', $_REQUEST) && make_bool($_REQUEST['autoplay']));
    }

    public function broadcastsByDefault(): bool
    {
        return (bool) AmpConfig::get('broadcast_by_default');
    }

    public function canSlideshow(): bool
    {
        return (bool) Preference::exists('flickr_api_key');
    }

    public function getAreaClass(): string
    {
        $class = ($this->embed) ? ' jp-area-embed' : ' jp-area-center';

        return ($this->isVideo) ? $class . ' jp-area-video' : $class;
    }

    public function getContainerClass(): string
    {
        return ($this->isVideo) ? 'jp-video jp-video-float jp-video-360p' : 'jp-audio';
    }

    /**
     * The `Cookies.set` options the player writes its own toggles with.
     */
    public function getCookieOptions(): string
    {
        return (AmpConfig::get('cookie_secure'))
            ? "expires: 7, path: '/', secure: true, samesite: 'Strict'"
            : "expires: 7, path: '/', samesite: 'Strict'";
    }

    public function getJpVolume(): float
    {
        return (float) AmpConfig::get('jp_volume', 0.80);
    }

    /**
     * @return array<string, string>
     */
    public function getLabels(): array
    {
        return [
            'prev' => T_('Previous'),
            'play' => T_('Play'),
            'pause' => T_('Pause'),
            'next' => T_('Next'),
            'stop' => T_('Stop'),
            'mute' => T_('Mute'),
            'unmute' => T_('Unmute'),
            'maxvol' => T_('Max Volume'),
            'fullscreen' => T_('Full Screen'),
            'restscreen' => T_('Restore Screen'),
            'shuffleon' => T_('Shuffle'),
            'shuffleoff' => T_('Shuffle Off'),
            'repeaton' => T_('Repeat'),
            'repeatoff' => T_('Repeat Off'),
            'showalbum' => T_('Show Album'),
        ];
    }

    public function getPlayerClass(): string
    {
        return ($this->isVideo) ? 'jp-jplayer-video' : 'jp-jplayer-audio';
    }

    public function getPlaylist(): Stream_Playlist
    {
        return $this->playlist;
    }

    /**
     * `999` is the "remove every played item" sentinel, which jPlayer expresses as 0.
     */
    public function getRemoveCount(): int
    {
        $count = AmpConfig::get_int('webplayer_removeplayed', 0);

        return ($count === 999) ? 0 : $count;
    }

    public function getReplaygainIcon(): string
    {
        return (AmpConfig::get('theme_color', 'dark') === 'light') ? 'replaygain_dark' : 'replaygain';
    }

    public function getShareStyle(): string
    {
        return ($this->isShare || $this->isRandom) ? 'display: none;' : '';
    }

    /**
     * @throws JsonException
     */
    public function getShowAlbumLabelJs(): string
    {
        return json_encode($this->getLabels()['showalbum'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR);
    }

    public function getSiteTitle(): string
    {
        return (string) AmpConfig::get('site_title', '');
    }

    public function getWebPath(): string
    {
        return $this->webPath;
    }

    public function isEmbed(): bool
    {
        return $this->embed;
    }

    public function isIframed(): bool
    {
        return $this->iframed;
    }

    public function isPlayerFragment(): bool
    {
        return $this->playerFragment;
    }

    /**
     * A random or democratic stream is generated, so its item list is not the user's to reorder.
     */
    public function isPlaylistEditable(): bool
    {
        return !$this->isRandom && !$this->isDemocratic;
    }

    public function isRandom(): bool
    {
        return $this->isRandom;
    }

    public function isShare(): bool
    {
        return $this->isShare;
    }

    public function isSociable(): bool
    {
        return (bool) AmpConfig::get('sociable');
    }

    public function isVideo(): bool
    {
        return $this->isVideo;
    }

    /**
     * Text that ends up inside html which is itself inside a single-quoted javascript string.
     *
     * Both escapes are needed and in this order; `addslashes()` alone left the markup unescaped.
     */
    public function jsText(string $text): string
    {
        return addslashes(scrub_out($text));
    }

    public function loops(): bool
    {
        return $this->isRandom || $this->isDemocratic;
    }

    public function mayPostShout(): bool
    {
        return $this->isSociable() && (!AmpConfig::get('use_auth') || $this->mayUse);
    }

    /**
     * An install with auth off lets anyone shout; otherwise it needs a real user.
     */
    public function mayUse(): bool
    {
        return $this->mayUse;
    }

    /**
     * The caller owns the document and has already emitted the headers.
     */
    public function needsHeaders(): bool
    {
        return !$this->iframed && !$this->playerFragment;
    }

    /**
     * jPlayer drops items before the current one once a threshold is set; `999` means "all of them".
     */
    public function removesPlayed(): bool
    {
        return AmpConfig::get_int('webplayer_removeplayed', 0) > 0;
    }

    public function renderHeaders(): string
    {
        return (new WebPlayerHeadersView($this->webPath, $this->ajaxUriRetriever, $this->iframed, $this->isShare))->render();
    }

    public function showBroadcast(): bool
    {
        return (bool) AmpConfig::get('broadcast') && $this->mayUse;
    }

    public function showBrowserNotify(): bool
    {
        return $this->iframed && (bool) AmpConfig::get('browser_notify');
    }

    public function showLyrics(): bool
    {
        return (bool) AmpConfig::get('show_lyrics');
    }

    public function showPauseTabs(): bool
    {
        return $this->iframed && (bool) AmpConfig::get('webplayer_pausetabs');
    }

    /**
     * A shared video needs the player pinned below the share's own header.
     */
    public function showShareVideoStyle(): bool
    {
        return $this->isShare && $this->isVideo;
    }

    /**
     * A share must not rewrite the hosting page's title.
     */
    public function showSongPageTitle(): bool
    {
        return (bool) AmpConfig::get('song_page_title') && !$this->isShare;
    }

    #[Override]
    protected function templateFile(): string
    {
        return $this->findTemplate('playback/web_player.phtml');
    }
}
