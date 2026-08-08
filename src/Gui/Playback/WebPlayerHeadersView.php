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
use Ampache\Module\Broadcast\Broadcast_Server;
use Ampache\Module\Playback\Stream;
use Ampache\Module\Util\AjaxUriRetrieverInterface;
use Ampache\Module\Util\Ui;
use JsonException;
use Override;

/**
 * The jPlayer stylesheets and script the web player needs, emitted into a document head.
 *
 * Almost all of it is javascript, so every value the page interpolates goes out as a json literal --
 * `addslashes()` was used here before, which leaves `</script>` and newlines intact.
 */
final class WebPlayerHeadersView extends AbstractView
{
    public function __construct(
        private readonly string $webPath,
        private readonly AjaxUriRetrieverInterface $ajaxUriRetriever,
        private readonly bool $iframed = false,
        private readonly bool $isShare = false,
    ) {}

    /**
     * @throws JsonException
     */
    public function getAjaxServerUriJs(): string
    {
        return $this->toJs($this->ajaxUriRetriever->getAjaxServerUri());
    }

    /**
     * @throws JsonException
     */
    public function getAjaxUriJs(): string
    {
        return $this->toJs($this->ajaxUriRetriever->getAjaxUri());
    }

    /**
     * @throws JsonException
     */
    public function getBroadcastAddressJs(): string
    {
        return $this->toJs(Broadcast_Server::get_address());
    }

    /**
     * @throws JsonException
     */
    public function getBroadcastFailedMessageJs(): string
    {
        return $this->toJs(T_('Could not connect to the broadcast. Resuming normal playback.'));
    }

    /**
     * @throws JsonException
     */
    public function getConfirmCloseMessageJs(): string
    {
        return $this->toJs(T_('Media is currently playing, are you sure you want to close?') . ' ' . $this->getSiteTitle() . '?');
    }

    /**
     * The `Cookies.set` options every toggle in the player writes its state with.
     */
    public function getCookieOptions(): string
    {
        return (AmpConfig::get('cookie_secure'))
            ? "path: '/', secure: true, samesite: 'Strict'"
            : "path: '/', samesite: 'Strict'";
    }

    /**
     * A theme asset url with its cache-busting token already appended.
     */
    public function getCssUrl(string $file): string
    {
        return $this->webPath . Ui::find_template($file, true) . '?v=' . Ui::find_template_version($file);
    }

    /**
     * @throws JsonException
     */
    public function getFullscreenUnsupportedMessageJs(): string
    {
        return $this->toJs(T_('Full-Screen not supported by your browser'));
    }

    /**
     * The colour the player highlights an engaged toggle with, which the light theme flips.
     */
    public function getHighlightColour(): string
    {
        return (AmpConfig::get('theme_color', 'dark') === 'light') ? 'blue' : 'orange';
    }

    public function getNotifyTimeout(): int
    {
        return (int) AmpConfig::get('browser_notify_timeout');
    }

    /**
     * @throws JsonException
     */
    public function getRemoteStreamMessageJs(): string
    {
        return $this->toJs(T_('The visualizer and equalizer are not available for remote catalog streams.'));
    }

    /**
     * @throws JsonException
     */
    public function getSessionIdJs(): string
    {
        return $this->toJs(session_id());
    }

    public function getSiteTitle(): string
    {
        return (string) AmpConfig::get('site_title');
    }

    /**
     * @throws JsonException
     */
    public function getSiteTitleJs(): string
    {
        return $this->toJs($this->getSiteTitle());
    }

    /**
     * @throws JsonException
     */
    public function getStreamSessionJs(): string
    {
        return $this->toJs(Stream::get_session());
    }

    /**
     * @throws JsonException
     */
    public function getUnsupportedFeatureMessageJs(): string
    {
        return $this->toJs(T_("Your browser doesn't support this feature."));
    }

    public function getWebPath(): string
    {
        return $this->webPath;
    }

    /**
     * Embedded in the page's own document, rather than standing alone in an iframe or a share page.
     */
    public function isIframed(): bool
    {
        return $this->iframed;
    }

    public function showConfirmClose(): bool
    {
        return $this->iframed && (bool) AmpConfig::get('webplayer_confirmclose') && !$this->isShare;
    }

    public function showDebug(): bool
    {
        return (bool) AmpConfig::get('webplayer_debug');
    }

    public function showPauseTabs(): bool
    {
        return $this->iframed && (bool) AmpConfig::get('webplayer_pausetabs') && !$this->isShare;
    }

    public function showSongPageTitle(): bool
    {
        return (bool) AmpConfig::get('song_page_title');
    }

    #[Override]
    protected function templateFile(): string
    {
        return $this->findTemplate('playback/web_player_headers.phtml');
    }

    /**
     * @throws JsonException
     */
    private function toJs(mixed $value): string
    {
        return json_encode($value, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR);
    }
}
