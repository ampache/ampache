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
use Ampache\Repository\Model\Album;
use Ampache\Repository\Model\AlbumDisk;
use Ampache\Repository\Model\Song;
use Ampache\Repository\Model\User;
use Override;

/**
 * One now-playing row for a song.
 */
final class NowPlayingSongRowView extends AbstractView
{
    public function __construct(
        private readonly Song $media,
        private readonly User $client,
        private readonly string $agent,
        private readonly string $webPath,
    ) {}

    public function getAgent(): string
    {
        return $this->agent;
    }

    public function getAlbumId(): int
    {
        return (int) $this->media->album;
    }

    public function getAlbumLink(): string
    {
        return ($this->isAlbumGrouped())
            ? $this->media->get_f_album_link()
            : $this->media->get_f_album_disk_link();
    }

    /**
     * The song's own art wins when it has some; otherwise the album it belongs to supplies it.
     */
    public function getArt(): string
    {
        $playing = (AmpConfig::get('show_song_art') && Art::has_db($this->media->getId(), 'song'))
            ? $this->media
            : (($this->isAlbumGrouped()) ? new Album($this->getAlbumId()) : new AlbumDisk((int) $this->media->album_disk));
        if ($playing->isNew()) {
            return '';
        }

        ob_start();
        $playing->display_art(['width' => 100, 'height' => 100]);

        return (string) ob_get_clean();
    }

    public function getArtistLink(): string
    {
        return (string) $this->media->get_f_parent_link();
    }

    public function getAvatar(): string
    {
        return $this->client->get_f_avatar('f_avatar_medium');
    }

    /**
     * A user with no full name set is still identified, without writing the placeholder back to the model.
     */
    public function getClientName(): string
    {
        return ($this->client->fullname) ?: 'Ampache User';
    }

    public function getClientUrl(): string
    {
        return $this->webPath . '/stats.php?action=show_user&user_id=' . ($this->client->getId() ?: -1);
    }

    public function getGenres(): string
    {
        return (string) $this->media->get_f_tags();
    }

    public function getSimilarAction(): string
    {
        return '?page=index&action=similar_now_playing&media_id=' . $this->getSongId() . '&media_artist=' . $this->media->artist;
    }

    public function getSongId(): int
    {
        return $this->media->getId();
    }

    public function getSongLink(): string
    {
        return $this->media->get_f_link();
    }

    public function getYear(): int
    {
        return (int) $this->media->year;
    }

    public function getYearUrl(): string
    {
        return $this->webPath . '/search.php?type=album&action=search&limit=0&rule_1=year&rule_1_operator=2&rule_1_input=' . $this->getYear();
    }

    public function hasGenres(): bool
    {
        return $this->media->get_tags() !== [];
    }

    public function isAlbumGrouped(): bool
    {
        return (bool) AmpConfig::get('album_group');
    }

    public function showRatings(): bool
    {
        return Access::check(AccessTypeEnum::INTERFACE, AccessLevelEnum::USER) && (bool) AmpConfig::get('ratings');
    }

    public function showSimilar(): bool
    {
        return (bool) AmpConfig::get('show_similar');
    }

    public function showUserBlock(): bool
    {
        return Access::check(AccessTypeEnum::INTERFACE, AccessLevelEnum::USER);
    }

    #[Override]
    protected function templateFile(): string
    {
        return $this->findTemplate('now_playing_song_row.phtml');
    }
}
