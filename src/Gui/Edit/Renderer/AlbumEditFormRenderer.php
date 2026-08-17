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

namespace Ampache\Gui\Edit\Renderer;

use Ampache\Gui\Edit\AbstractEditFormRenderer;
use Ampache\Module\Authorization\Access;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Module\System\Core;
use Ampache\Repository\Model\Album;
use Ampache\Repository\Model\AlbumDisk;
use Ampache\Repository\Model\Artist;
use Ampache\Repository\Model\Mood;
use Ampache\Repository\Model\Tag;
use Ampache\Repository\Model\User;
use Override;

/**
 * The album and album-disk edit dialogs.
 *
 * They carried the same fields and differed only in where the disk number came from. Eight of those
 * fields reached their `value=` attribute unescaped, all of them free text.
 */
final class AlbumEditFormRenderer extends AbstractEditFormRenderer
{
    /**
     * Every artist on the album except the one credited as its album artist.
     */
    public function getAdditionalArtists(): string
    {
        $artists = $this->getItem()->get_artists();

        return Artist::get_display(array_diff($artists, [$this->getItem()->album_artist]));
    }

    public function getAlbumArtistId(): int
    {
        return (int) $this->getItem()->album_artist;
    }

    public function getAlbumId(): int
    {
        return $this->getItem()->getId();
    }

    public function getBarcode(): string
    {
        return (string) $this->getItem()->barcode;
    }

    public function getCatalogId(): int
    {
        $item = $this->getItem();

        return ($item instanceof AlbumDisk) ? $item->catalog : 0;
    }

    public function getCatalogNumber(): string
    {
        return (string) $this->getItem()->catalog_number;
    }

    /**
     * An album with one disk edits that disk here; a multi-disk album sends you to the disk's own dialog.
     */
    public function getDisk(): int
    {
        $item = $this->getItem();
        if ($item instanceof AlbumDisk) {
            return $item->disk;
        }

        foreach ($item->getDisks() as $albumDisk) {
            return $albumDisk->disk;
        }

        return 0;
    }

    public function getDiskSubtitle(): string
    {
        $item = $this->getItem();
        if ($item instanceof AlbumDisk) {
            return (string) $item->disksubtitle;
        }

        foreach ($item->getDisks() as $albumDisk) {
            return (string) $albumDisk->disksubtitle;
        }

        return '';
    }

    public function getGenres(): string
    {
        return Tag::get_display(Tag::get_top_tags('album', $this->getAlbumId(), 0));
    }

    public function getMbid(): string
    {
        return (string) $this->getItem()->mbid;
    }

    public function getMbidGroup(): string
    {
        return (string) $this->getItem()->mbid_group;
    }

    public function getMoods(): string
    {
        return Mood::get_display(Mood::get_top_moods('album', $this->getAlbumId(), 0));
    }

    public function getName(): string
    {
        return $this->getItem()->get_fullname(true);
    }

    public function getOriginalYear(): string
    {
        return (string) $this->getItem()->original_year;
    }

    public function getParentName(): string
    {
        return $this->getItem()->get_parent_fullname();
    }

    public function getReleaseStatus(): string
    {
        return (string) $this->getItem()->release_status;
    }

    public function getReleaseType(): string
    {
        return (string) $this->getItem()->release_type;
    }

    public function getVersion(): string
    {
        return (string) $this->getItem()->version;
    }

    public function getYear(): string
    {
        return (string) $this->getItem()->year;
    }

    public function isAlbumDisk(): bool
    {
        return $this->getItem() instanceof AlbumDisk;
    }

    public function mayEditMbid(): bool
    {
        $user = Core::get_global('user');

        return $this->mayManage() || ($user instanceof User && $user->getId() === $this->getItem()->get_user_owner());
    }

    public function mayManage(): bool
    {
        $user = Core::get_global('user');

        return $user instanceof User && Access::check(AccessTypeEnum::INTERFACE, AccessLevelEnum::CONTENT_MANAGER, $user->getId());
    }

    public function showAdditionalArtists(): bool
    {
        return count($this->getItem()->get_artists()) > 1;
    }

    /**
     * A multi-disk album has no single disk to edit, so those two rows only appear when there is one.
     */
    public function showDisk(): bool
    {
        return $this->isAlbumDisk() || $this->getItem()->disk_count === 1;
    }

    #[Override]
    protected function templateFile(): string
    {
        return $this->findTemplate('edit/album.phtml');
    }

    private function getItem(): Album|AlbumDisk
    {
        /** @var Album|AlbumDisk $item */
        $item = $this->getContext()->item;

        return $item;
    }
}
