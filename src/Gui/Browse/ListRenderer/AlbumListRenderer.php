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

namespace Ampache\Gui\Browse\ListRenderer;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Gui\GuiFactoryInterface;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Module\Authorization\GatekeeperFactoryInterface;
use Ampache\Repository\Model\Album;
use Ampache\Repository\Model\AlbumDisk;
use Ampache\Repository\Model\User;
use Override;

/**
 * The album browse, and the album-disk browse it becomes when `album_group` is off.
 *
 * The two differed only in the model they loaded and the prefix on their sort links, and both started
 * their empty state at nine columns while never counting the played column.
 */
final class AlbumListRenderer extends AbstractBrowseListRenderer
{
    public function __construct(
        private readonly ConfigContainerInterface $configContainer,
        private readonly GatekeeperFactoryInterface $gatekeeperFactory,
        private readonly GuiFactoryInterface $guiFactory,
    ) {}

    public function getAlbumClass(): string
    {
        return $this->getCellClass('cel_album', 'grid_album');
    }

    /**
     * @return list<Album|AlbumDisk>
     */
    public function getAlbums(): array
    {
        $albums = [];
        foreach ($this->getObjectIds() as $objectId) {
            $album = ($this->isAlbumDisk()) ? new AlbumDisk($objectId) : new Album($objectId);
            if ($album->isNew()) {
                continue;
            }

            $albums[] = $album;
        }

        return $albums;
    }

    public function getArtistClass(): string
    {
        return $this->getCellClass('cel_artist', 'grid_artist');
    }

    /**
     * @return list<array{class: string, label: string, sort: null|string, key: string}>
     */
    public function getColumns(): array
    {
        $columns = [
            ['class' => 'cel_play essential', 'label' => '', 'sort' => null, 'key' => 'play'],
            ['class' => $this->getCoverClass() . ' optional', 'label' => T_('Art'), 'sort' => null, 'key' => 'cover'],
            ['class' => $this->getAlbumClass() . ' essential persist', 'label' => T_('Album'), 'sort' => 'name', 'key' => 'name'],
            ['class' => 'cel_add essential', 'label' => '', 'sort' => null, 'key' => 'add'],
            ['class' => $this->getArtistClass() . ' essential', 'label' => T_('Album Artist'), 'sort' => 'album_artist_album_sort', 'key' => 'artist'],
            ['class' => 'cel_songs optional', 'label' => T_('Songs'), 'sort' => 'song_count', 'key' => 'song_count'],
            ['class' => 'cel_year essential', 'label' => T_('Year'), 'sort' => $this->getYearSort(), 'key' => 'year'],
        ];

        if ($this->showPlayedTimes()) {
            $columns[] = ['class' => $this->getCounterClass() . ' optional', 'label' => T_('Played'), 'sort' => 'total_count', 'key' => 'total_count'];
        }

        if (!$this->hideGenres()) {
            $columns[] = ['class' => $this->getTagsClass() . ' optional', 'label' => T_('Genres'), 'sort' => null, 'key' => 'tags'];
        }

        if ($this->showRatings()) {
            $columns[] = ['class' => 'cel_ratings optional', 'label' => T_('Rating'), 'sort' => 'rating', 'key' => 'rating'];
        }

        $columns[] = ['class' => 'cel_action essential', 'label' => T_('Actions'), 'sort' => null, 'key' => 'action'];

        return $columns;
    }

    public function getCounterClass(): string
    {
        return $this->getCellClass('cel_counter', 'grid_counter');
    }

    public function getCoverClass(): string
    {
        return $this->getCellClass('cel_cover', 'grid_cover');
    }

    /**
     * The bottom list header is the grouping control on a release-grouped browse.
     */
    public function getGroupRelease(): bool
    {
        return $this->getContext()->groupRelease;
    }

    public function getObjectType(): string
    {
        return ($this->isAlbumDisk()) ? 'album_disk' : 'album';
    }

    /**
     * Both browses share one set of sort ids, so the prefix keeps their dom ids apart.
     */
    public function getSortPrefix(): string
    {
        return ($this->isAlbumDisk()) ? 'album_disk_sort_' : 'album_sort_';
    }

    public function getTagsClass(): string
    {
        return $this->getCellClass('cel_tags', 'grid_tags');
    }

    public function hideGenres(): bool
    {
        return (bool) $this->configContainer->get('hide_genres');
    }

    public function isAlbumDisk(): bool
    {
        return $this->getBrowse()->get_type() === 'album_disk';
    }

    public function renderRow(Album|AlbumDisk $album): string
    {
        $gatekeeper = $this->gatekeeperFactory->createGuiGatekeeper();
        $mayAdd     = $gatekeeper->mayAccess(AccessTypeEnum::INTERFACE, AccessLevelEnum::USER);
        $limit      = (int) $this->configContainer->get('direct_play_limit');
        if ($limit > 0 && !$this->getBrowse()->is_grid_view()) {
            $mayAdd = $mayAdd && $album->song_count <= $limit;
        }

        if ($album instanceof AlbumDisk) {
            return $this->guiFactory->createAlbumDiskRowView(
                $gatekeeper,
                $this->getBrowse(),
                $album,
                $this->showRatings(),
                $this->hideGenres(),
                $this->showPlayedTimes(),
                $mayAdd,
                $this->getCoverClass(),
                $this->getAlbumClass(),
                $this->getArtistClass(),
                $this->getTagsClass(),
                $this->getCounterClass()
            )->render();
        }

        return $this->guiFactory->createAlbumRowView(
            $gatekeeper,
            $this->getBrowse(),
            $album,
            $this->showRatings(),
            $this->hideGenres(),
            $this->showPlayedTimes(),
            $mayAdd,
            $this->getCoverClass(),
            $this->getAlbumClass(),
            $this->getArtistClass(),
            $this->getTagsClass(),
            $this->getCounterClass()
        )->render();
    }

    public function showPlayedTimes(): bool
    {
        return (bool) $this->configContainer->get('show_played_times');
    }

    public function showRatings(): bool
    {
        return User::is_registered() && (bool) $this->configContainer->get('ratings');
    }

    #[Override]
    protected function templateFile(): string
    {
        return $this->findTemplate('browse/albums.phtml');
    }

    private function getYearSort(): string
    {
        return ($this->configContainer->get('use_original_year')) ? 'original_year' : 'year';
    }
}
