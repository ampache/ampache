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

namespace Ampache\Gui\Album;

use Ampache\Config\AmpConfig;
use Ampache\Gui\Partial\ExternalLinksView;
use Ampache\Gui\View\AbstractView;
use Ampache\Module\Art\Art;
use Ampache\Module\Catalog\Catalog;
use Ampache\Module\Database\Query\Browse;
use Ampache\Module\Database\Query\BrowseFactoryInterface;
use Ampache\Module\Playback\Stream_Playlist;
use Ampache\Module\Util\Ui;
use Ampache\Module\Util\Upload;
use Ampache\Repository\Model\Album;
use Ampache\Repository\Model\AlbumDisk;
use Ampache\Repository\Model\User;
use Override;

/**
 * An album's own page, and the same page for one of its disks.
 *
 * The two differed only in the object type they name in their urls and in the disk page having no track
 * reorder, so they share one view rather than two near-identical templates.
 */
final class AlbumPageView extends AbstractView
{
    public function __construct(
        private readonly Album|AlbumDisk $album,
        private readonly BrowseFactoryInterface $browseFactory,
        private readonly ?User $currentUser,
        private readonly string $webPath,
        private readonly bool $isEditable,
        private readonly bool $mayZip,
        private readonly bool $mayUse,
        private readonly bool $mayManage,
        private readonly bool $grouped = false,
    ) {}

    public function createBrowse(): Browse
    {
        return $this->browseFactory->create();
    }

    public function getAddToListLabel(): string
    {
        return Ui::get_add_to_list_label();
    }

    public function getAlbum(): Album|AlbumDisk
    {
        return $this->album;
    }

    public function getAlbumId(): int
    {
        return $this->album->getId();
    }

    public function getArt(): string
    {
        $name = '[' . $this->getParentName() . '] ' . $this->getFullname();
        ob_start();
        // art is stored against the album; a disk has none of its own
        Art::display('album', $this->getUploadAlbumId(), $name, ['width' => 384, 'height' => 384], null, true, false);

        return (string) ob_get_clean();
    }

    public function getArtistId(): int
    {
        return (int) $this->album->album_artist;
    }

    public function getBrowseFilter(): string
    {
        return $this->getObjectType();
    }

    /**
     * A multi-disk album lists each disk with its own actions instead of one flat song list.
     *
     * @return list<AlbumDiskSectionView>
     */
    public function getDiskSections(): array
    {
        if (!$this->grouped || !$this->album instanceof Album) {
            return [];
        }

        $sections = [];
        foreach ($this->album->getDisks() as $disk) {
            $sections[] = new AlbumDiskSectionView(
                $disk,
                $this->browseFactory,
                $this->webPath,
                $this->getHiddenColumns(),
                $this->isEditable,
                $this->mayZip,
                $this->mayUse
            );
        }

        return $sections;
    }

    public function getExternalLinks(): ExternalLinksView
    {
        return new ExternalLinksView(
            $this->getParentName(),
            $this->album->get_fullname(true),
            $this->album->mbid,
            'release',
            'album',
            'a',
            'master',
            ($this->getParentName() === 'Various Artists') ? 'Various' : null
        );
    }

    public function getFullname(): string
    {
        return $this->album->get_fullname(false, true);
    }

    /**
     * A single-artist album hides the artist column too; every album hides the ones a browse repeats.
     *
     * @return list<string>
     */
    public function getHiddenColumns(): array
    {
        return ((bool) AmpConfig::get('hide_single_artist') && $this->album->get_artist_count() === 1)
            ? ['cel_artist', 'cel_album', 'cel_year', 'cel_drag']
            : ['cel_album', 'cel_year', 'cel_drag'];
    }

    public function getLink(): string
    {
        return $this->album->get_link();
    }

    /**
     * The disk page names `album_disk` everywhere the album page names `album`.
     */
    public function getObjectType(): string
    {
        return ($this->album instanceof AlbumDisk) ? 'album_disk' : 'album';
    }

    public function getOwner(): ?User
    {
        $ownerId = $this->album->get_user_owner();

        return ($ownerId > 0) ? new User($ownerId) : null;
    }

    public function getParentLink(): string
    {
        return (string) $this->album->get_f_parent_link();
    }

    public function getParentName(): string
    {
        return (string) $this->album->get_parent_fullname();
    }

    public function getPlayedTimes(): int
    {
        return $this->album->total_count;
    }

    public function getRetagUrl(): string
    {
        return ($this->album instanceof AlbumDisk)
            ? $this->webPath . '/albums.php?action=update_disk_from_tags&album_disk=' . $this->getAlbumId()
            : $this->webPath . '/albums.php?action=update_from_tags&album_id=' . $this->getAlbumId();
    }

    public function getTitle(): string
    {
        $hasArtist = ($this->album instanceof AlbumDisk)
            ? $this->album->album_artist !== null
            : $this->album->findAlbumArtist() !== null;

        return ($hasArtist)
            ? scrub_out($this->getFullname()) . '&nbsp;-&nbsp;' . $this->getParentLink()
            : scrub_out($this->getFullname());
    }

    /**
     * A disk's upload link points at the album it belongs to, not at the disk.
     */
    public function getUploadAlbumId(): int
    {
        return ($this->album instanceof AlbumDisk) ? $this->album->album_id : $this->getAlbumId();
    }

    public function getUser(): ?User
    {
        return $this->currentUser;
    }

    public function getWebPath(): string
    {
        return $this->webPath;
    }

    public function isAutoplayAppend(): bool
    {
        return Stream_Playlist::check_autoplay_append();
    }

    public function isAutoplayNext(): bool
    {
        return Stream_Playlist::check_autoplay_next();
    }

    public function isEditable(): bool
    {
        return $this->isEditable;
    }

    public function isGrouped(): bool
    {
        return $this->grouped;
    }

    public function mayDelete(): bool
    {
        // the delete action takes an album id; there is no action to delete a single disk
        return !($this->album instanceof AlbumDisk) && Catalog::can_remove($this->album);
    }

    /**
     * Reordering, retagging and graphs are for the uploader or a manager.
     */
    public function mayManage(): bool
    {
        $ownerId = $this->album->get_user_owner();

        return $this->mayManage || ($ownerId > 0 && $ownerId === $this->currentUser?->getId());
    }

    public function mayUpload(): bool
    {
        return Upload::can_upload($this->currentUser) && $this->getArtistId() > 0;
    }

    public function mayUse(): bool
    {
        return $this->mayUse;
    }

    public function mayZip(): bool
    {
        return $this->mayZip;
    }

    /**
     * A very large album is not offered as one click, so the queue is not filled by accident.
     */
    public function showAdd(): bool
    {
        $limit = AmpConfig::get_int('direct_play_limit');

        return $this->mayUse && ($limit <= 0 || $this->album->song_count <= $limit);
    }

    /**
     * The orphan bucket has no cover of its own, so the page does not ask for one.
     */
    public function showArt(): bool
    {
        return $this->album->name !== T_('Unknown (Orphaned)');
    }

    /**
     * Direct play streams without touching the temporary playlist, so it needs no access level.
     */
    public function showDirectPlay(): bool
    {
        $limit = AmpConfig::get_int('direct_play_limit');

        return (bool) AmpConfig::get('directplay')
            && ($limit <= 0 || $this->album->song_count <= $limit);
    }

    public function showGraphs(): bool
    {
        return (bool) AmpConfig::get('statistical_graphs');
    }

    public function showPlayedTimes(): bool
    {
        return (bool) AmpConfig::get('show_played_times');
    }

    public function showRatings(): bool
    {
        return User::is_registered() && (bool) AmpConfig::get('ratings');
    }

    /**
     * Only the whole album offers a track reorder; a single disk has no ordering of its own to save.
     */
    public function showReorder(): bool
    {
        return !$this->album instanceof AlbumDisk;
    }

    public function showRss(): bool
    {
        return (bool) AmpConfig::get('use_rss');
    }

    public function showShare(): bool
    {
        return $this->mayUse && (bool) AmpConfig::get('share');
    }

    public function showShout(): bool
    {
        return (!AmpConfig::get('use_auth') || $this->mayUse) && (bool) AmpConfig::get('sociable');
    }

    public function showUploader(): bool
    {
        return (bool) AmpConfig::get('sociable') && $this->album->get_user_owner() > 0;
    }

    #[Override]
    protected function templateFile(): string
    {
        return $this->findTemplate('album/album.phtml');
    }
}
