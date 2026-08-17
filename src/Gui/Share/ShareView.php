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

namespace Ampache\Gui\Share;

use Ampache\Config\AmpConfig;
use Ampache\Gui\Playback\WebPlayerHeadersView;
use Ampache\Gui\Playback\WebPlayerView;
use Ampache\Gui\View\AbstractView;
use Ampache\Module\Art\Art;
use Ampache\Module\Playback\Stream_Playlist;
use Ampache\Module\Playback\WebPlayer;
use Ampache\Module\Util\AjaxUriRetrieverInterface;
use Ampache\Module\Util\Ui;
use Ampache\Repository\Model\Share;
use Ampache\Repository\Model\Song;
use Override;

/**
 * A share's public landing page: the hero artwork, the compact player and a download link.
 */
final class ShareView extends AbstractView
{
    private ?Stream_Playlist $playlist = null;

    public function __construct(
        private readonly string $webPath,
        private readonly AjaxUriRetrieverInterface $ajaxUriRetriever,
        private readonly Share $share,
    ) {}

    public function allowsDownload(): bool
    {
        return $this->share->allow_download;
    }

    /**
     * The hero image, falling back to the site logo.
     *
     * A share is served without a session, so real artwork is only linked when `image.php` will
     * actually serve it; otherwise the hero and the og:image would both be a forbidden image.
     */
    public function getArtUrl(): string
    {
        $type = (string) $this->share->object_type;
        $id   = $this->share->object_id;
        if ($type === 'song') {
            $song = new Song($this->share->object_id);
            if (!$song->isNew() && $song->album) {
                $type = 'album';
                $id   = (int) $song->album;
            }
        }

        $url = (Art::isPublic() && Art::has_db($id, $type))
            ? (Art::url($id, $type) ?? '')
            : '';

        return ($url === '') ? Ui::get_logo_url() : $url;
    }

    public function getDownloadUrl(): string
    {
        return $this->webPath . '/share.php?action=download&id=' . $this->share->id . '&secret=' . $this->share->secret;
    }

    public function getObjectUrl(): string
    {
        return $this->share->getObjectUrl();
    }

    public function getPublicUrl(): string
    {
        return (string) $this->share->public_url;
    }

    public function getSharedByText(): string
    {
        return sprintf(T_('Shared by %s'), $this->share->getUserName());
    }

    public function getShareTitle(): string
    {
        return $this->share->getObjectName();
    }

    public function getSiteTitle(): string
    {
        return (string) AmpConfig::get('site_title', 'Ampache');
    }

    public function getWebPath(): string
    {
        return $this->webPath;
    }

    public function hasObjectUrl(): bool
    {
        return $this->share->getObjectUrl() !== '';
    }

    /**
     * Embedded in someone else's page, so the hero, the social tags and the footer are all dropped.
     */
    public function isEmbed(): bool
    {
        return !empty($_REQUEST['embed']);
    }

    /**
     * The player as a fragment: this page owns the head and body around it.
     */
    public function renderPlayer(): string
    {
        $playlist = $this->playlist ??= $this->share->create_fake_playlist();

        return new WebPlayerView(
            $this->webPath,
            $this->ajaxUriRetriever,
            $playlist,
            WebPlayer::is_playlist_video($playlist),
            false,
            false,
            true,
            false,
            false,
            false,
            true
        )->render();
    }

    public function renderPlayerHeaders(): string
    {
        return new WebPlayerHeadersView($this->webPath, $this->ajaxUriRetriever, false, true)->render();
    }

    #[Override]
    protected function templateFile(): string
    {
        return $this->findTemplate('share/share.phtml');
    }
}
