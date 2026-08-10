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

namespace Ampache\Gui\Artist;

use Ampache\Config\AmpConfig;
use Ampache\Gui\Partial\ExternalLinksView;
use Ampache\Gui\View\AbstractView;
use Ampache\Module\Art\Art;
use Ampache\Module\Catalog\Catalog;
use Ampache\Module\Database\Query\Browse;
use Ampache\Module\Database\Query\BrowseFactoryInterface;
use Ampache\Module\Playback\Stream_Playlist;
use Ampache\Module\System\Preference;
use Ampache\Module\Util\Ui;
use Ampache\Module\Util\Upload;
use Ampache\Repository\Model\Artist;
use Ampache\Repository\Model\User;
use Override;

/**
 * An artist's own page: its actions and the tabbed album, track and similar-artist panes.
 */
final class ArtistPageView extends AbstractView
{
    /**
     * @param array<string, list<int>> $multiObjectIds the album lists, keyed by the heading each carries
     */
    public function __construct(
        private readonly Artist $artist,
        private readonly array $multiObjectIds,
        private readonly string $objectType,
        private readonly BrowseFactoryInterface $browseFactory,
        private readonly ?User $currentUser,
        private readonly string $webPath,
        private readonly bool $mayEdit,
        private readonly bool $mayZip,
        private readonly bool $mayUse,
        private readonly bool $mayManage,
    ) {}

    public function createBrowse(): Browse
    {
        return $this->browseFactory->create();
    }

    public function getAddToListLabel(): string
    {
        return Ui::get_add_to_list_label();
    }

    public function getArt(): string
    {
        ob_start();
        Art::display('artist', $this->getArtistId(), $this->getFullname(), ['width' => 384, 'height' => 384], null, true, false);

        return (string) ob_get_clean();
    }

    public function getArtistId(): int
    {
        return $this->artist->getId();
    }

    public function getBiographyAction(): string
    {
        return '?page=index&action=artist_info&artist=' . $this->getArtistId();
    }

    public function getExternalLinks(): ExternalLinksView
    {
        return new ExternalLinksView($this->getFullname(), '', $this->artist->mbid, 'artist', 'artist', 'b', 'artist');
    }

    public function getFullname(): string
    {
        return (string) $this->artist->get_fullname();
    }

    /**
     * @return array<string, list<int>>
     */
    public function getMultiObjectIds(): array
    {
        return ($this->multiObjectIds === []) ? ['' => []] : $this->multiObjectIds;
    }

    public function getObjectType(): string
    {
        return $this->objectType;
    }

    public function getOwner(): ?User
    {
        $ownerId = $this->artist->get_user_owner();

        return ($ownerId > 0) ? new User($ownerId) : null;
    }

    public function getPlayedTimes(): int
    {
        return $this->artist->total_count;
    }

    /**
     * The album list honours the user's album sort preference; anything else keeps its own order.
     *
     * @return array{0: string, 1: string}
     */
    public function getSort(): array
    {
        $year = (AmpConfig::get('use_original_year')) ? 'original_year' : 'year';

        return match ((string) AmpConfig::get('album_sort')) {
            'name_asc' => ['name', 'ASC'],
            'name_desc' => ['name', 'DESC'],
            'year_asc' => [$year, 'ASC'],
            'year_desc' => [$year, 'DESC'],
            default => ['name_' . $year, 'ASC'],
        };
    }

    public function getUser(): ?User
    {
        return $this->currentUser;
    }

    public function getWebPath(): string
    {
        return $this->webPath;
    }

    public function isAlbumType(): bool
    {
        return $this->objectType === 'album' || $this->objectType === 'album_disk';
    }

    public function isAutoplayAppend(): bool
    {
        return Stream_Playlist::check_autoplay_append();
    }

    public function isAutoplayNext(): bool
    {
        return Stream_Playlist::check_autoplay_next();
    }

    public function mayDelete(): bool
    {
        return Catalog::can_remove($this->artist);
    }

    public function mayEdit(): bool
    {
        return $this->mayEdit;
    }

    public function mayManage(): bool
    {
        return $this->mayManage;
    }

    /**
     * Graphs are for the uploader or a manager.
     */
    public function maySeeGraphs(): bool
    {
        $ownerId = $this->artist->get_user_owner();

        return $this->mayManage || ($ownerId > 0 && $ownerId === $this->currentUser?->getId());
    }

    public function mayUpload(): bool
    {
        return Upload::can_upload($this->currentUser);
    }

    public function mayZip(): bool
    {
        return $this->mayZip;
    }

    /**
     * A very large artist is not offered as one click, so the queue is not filled by accident.
     */
    public function showAdd(): bool
    {
        $limit = AmpConfig::get_int('direct_play_limit');

        return $this->mayUse && ($limit <= 0 || $this->artist->song_count <= $limit);
    }

    /**
     * The biography pane replaces the cover when last.fm can supply one.
     */
    public function showBiography(): bool
    {
        return (bool) AmpConfig::get('lastfm_api_key');
    }

    public function showDirectPlay(): bool
    {
        return (bool) AmpConfig::get('directplay') && $this->showAdd();
    }

    public function showGraphs(): bool
    {
        return (bool) AmpConfig::get('statistical_graphs');
    }

    public function showLabels(): bool
    {
        return (bool) AmpConfig::get('label');
    }

    /**
     * Overwriting the name from MusicBrainz needs an id to fetch and the user's opt-in.
     */
    public function showMusicbrainzUpdate(): bool
    {
        return !empty($this->artist->mbid)
            && $this->currentUser instanceof User
            && (bool) Preference::get_by_user($this->currentUser->getId(), 'mb_overwrite_name');
    }

    public function showPlayedTimes(): bool
    {
        return (bool) AmpConfig::get('show_played_times');
    }

    public function showRatings(): bool
    {
        return User::is_registered() && (bool) AmpConfig::get('ratings');
    }

    public function showRss(): bool
    {
        return (bool) AmpConfig::get('use_rss');
    }

    public function showShout(): bool
    {
        return (!AmpConfig::get('use_auth') || $this->mayUse) && (bool) AmpConfig::get('sociable');
    }

    public function showSimilar(): bool
    {
        return (bool) AmpConfig::get('show_similar');
    }

    public function showUploader(): bool
    {
        return (bool) AmpConfig::get('sociable') && $this->artist->get_user_owner() > 0;
    }

    public function showWanted(): bool
    {
        return (bool) AmpConfig::get('wanted');
    }

    #[Override]
    protected function templateFile(): string
    {
        return $this->findTemplate('artist/artist.phtml');
    }
}
