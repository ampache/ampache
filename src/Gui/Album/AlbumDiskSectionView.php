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
use Ampache\Gui\View\AbstractView;
use Ampache\Module\Database\Query\Browse;
use Ampache\Module\Database\Query\BrowseFactoryInterface;
use Ampache\Module\Playback\Stream_Playlist;
use Ampache\Repository\Model\AlbumDisk;
use Override;

/**
 * One disk's heading, action row and song list on a grouped album page.
 *
 * Its "Link" fallback emitted a bare `<li>` inside a `<div>`, where every sibling is a plain anchor.
 */
final class AlbumDiskSectionView extends AbstractView
{
    /**
     * @param list<string> $hiddenColumns
     */
    public function __construct(
        private readonly AlbumDisk $disk,
        private readonly BrowseFactoryInterface $browseFactory,
        private readonly string $webPath,
        private readonly array $hiddenColumns,
        private readonly bool $isEditable,
        private readonly bool $mayZip,
        private readonly bool $mayUse,
    ) {}

    public function createBrowse(): Browse
    {
        return $this->browseFactory->create();
    }

    public function getDiskId(): int
    {
        return $this->disk->getId();
    }

    /**
     * @return list<string>
     */
    public function getHiddenColumns(): array
    {
        return $this->hiddenColumns;
    }

    public function getLink(): string
    {
        return $this->disk->get_link();
    }

    /**
     * A disk with its own subtitle shows it beside the disk number.
     */
    public function getSubtitle(): string
    {
        $link = $this->disk->get_f_link();
        if (empty($this->disk->disksubtitle)) {
            return $link;
        }

        return $link . '<span class="discnb disc' . $this->disk->disk . '">: ' . scrub_out($this->disk->disksubtitle) . '</span>';
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

    public function mayZip(): bool
    {
        return $this->mayZip;
    }

    /**
     * A very large disk is not offered as one click, so the queue is not filled by accident.
     */
    public function showAdd(): bool
    {
        $limit = AmpConfig::get_int('direct_play_limit');

        return $this->mayUse && ($limit <= 0 || $this->disk->song_count <= $limit);
    }

    /**
     * Direct play streams without touching the temporary playlist, so it needs no access level.
     */
    public function showDirectPlay(): bool
    {
        $limit = AmpConfig::get_int('direct_play_limit');

        return (bool) AmpConfig::get('directplay')
            && ($limit <= 0 || $this->disk->song_count <= $limit);
    }

    public function showShare(): bool
    {
        return $this->mayUse && (bool) AmpConfig::get('share');
    }

    public function showShout(): bool
    {
        return (!AmpConfig::get('use_auth') || $this->mayUse) && (bool) AmpConfig::get('sociable');
    }

    #[Override]
    protected function templateFile(): string
    {
        return $this->findTemplate('album/disk_section.phtml');
    }
}
