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

namespace Ampache\Gui\NowPlaying;

use Ampache\Config\AmpConfig;
use Ampache\Gui\View\AbstractView;
use Ampache\Module\Art\Art;
use Ampache\Module\Authorization\Access;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Repository\Model\User;
use Ampache\Repository\Model\Video;
use Override;

/**
 * One now-playing row for a video.
 */
final class NowPlayingVideoRowView extends AbstractView
{
    public function __construct(
        private readonly Video $media,
        private readonly User $client,
        private readonly string $agent,
        private readonly string $webPath,
    ) {}

    public function getAgent(): string
    {
        return $this->agent;
    }

    /**
     * A video with a preview frame shows that; anything else falls back to its cover.
     */
    public function getArt(): string
    {
        // An unauthenticated viewer gets the plain thumbnail; only an authenticated one gets the video's own
        // page as its link, matching getVideoLink().
        $link = ($this->showLinks()) ? $this->media->get_link() : null;

        ob_start();
        $shown = false;
        if ($this->media->get_default_art_kind() === 'preview') {
            $shown = Art::display('video', $this->media->getId(), $this->media->getFileName(), ['width' => 150, 'height' => 84], $link, false, true, 'preview');
        }

        if (!$shown) {
            Art::display('video', $this->media->getId(), $this->media->getFileName(), ['width' => 100, 'height' => 150], $link);
        }

        return (string) ob_get_clean();
    }

    public function getAvatar(): string
    {
        return $this->client->get_f_avatar('f_avatar_medium');
    }

    public function getClientName(): string
    {
        return ($this->client->fullname) ?: 'Ampache User';
    }

    public function getClientUrl(): string
    {
        return $this->webPath . '/stats.php?action=show_user&user_id=' . ($this->client->getId() ?: -1);
    }

    public function getVideoId(): int
    {
        return $this->media->getId();
    }

    public function getVideoLink(): string
    {
        if (!$this->showLinks()) {
            return scrub_out($this->media->get_fullname());
        }

        return $this->media->get_f_link();
    }

    /**
     * Only an authenticated viewer gets a clickable link to the video; a public, unauthenticated viewer
     * of this page (`use_now_playing_embedded`) sees the plain title and a non-linked thumbnail.
     */
    public function showLinks(): bool
    {
        return Access::check(AccessTypeEnum::INTERFACE, AccessLevelEnum::USER);
    }

    public function showRatings(): bool
    {
        return (bool) AmpConfig::get('ratings');
    }

    #[Override]
    protected function templateFile(): string
    {
        return $this->findTemplate('now_playing_video_row.phtml');
    }
}
