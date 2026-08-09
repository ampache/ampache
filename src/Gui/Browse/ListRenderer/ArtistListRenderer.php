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
use Ampache\Gui\Artist\ArtistRowView;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Module\Authorization\GatekeeperFactoryInterface;
use Ampache\Module\Catalog\Catalog;
use Ampache\Repository\Model\Artist;
use Ampache\Repository\Model\User;
use Override;

/**
 * The artist browse, which also serves the album-artist and song-artist variants.
 *
 * Its empty state started its column count at eight and never counted the played column.
 */
final class ArtistListRenderer extends AbstractBrowseListRenderer
{
    public function __construct(
        private readonly ConfigContainerInterface $configContainer,
        private readonly GatekeeperFactoryInterface $gatekeeperFactory,
    ) {}

    public function getArtistClass(): string
    {
        return $this->getCellClass('cel_artist', 'grid_artist');
    }

    /**
     * The album-artist and song-artist browses say so in their heading.
     */
    public function getArtistLabel(): string
    {
        if ($this->getBrowse()->is_album_artist()) {
            return T_('Album Artist');
        }

        return ($this->getBrowse()->is_song_artist()) ? T_('Song Artist') : T_('Artist');
    }

    /**
     * @return list<Artist>
     */
    public function getArtists(): array
    {
        $artists = [];
        foreach ($this->getObjectIds() as $objectId) {
            $artist = new Artist($objectId);
            if ($artist->isNew()) {
                continue;
            }

            $artists[] = $artist;
        }

        return $artists;
    }

    /**
     * @return list<array{class: string, label: string, sort: null|string, type: bool}>
     */
    public function getColumns(): array
    {
        $columns = [
            ['class' => 'cel_play essential', 'label' => '', 'sort' => null, 'type' => false],
            ['class' => $this->getCoverClass() . ' optional', 'label' => T_('Art'), 'sort' => null, 'type' => false],
            ['class' => $this->getArtistClass() . ' essential persist', 'label' => $this->getArtistLabel(), 'sort' => 'name', 'type' => true],
            ['class' => 'cel_add essential', 'label' => '', 'sort' => null, 'type' => false],
            ['class' => 'cel_songs optional', 'label' => T_('Songs'), 'sort' => 'song_count', 'type' => false],
            ['class' => 'cel_albums optional', 'label' => T_('Albums'), 'sort' => 'album_count', 'type' => false],
            ['class' => $this->getTimeClass() . ' optional', 'label' => T_('Time'), 'sort' => 'time', 'type' => false],
        ];

        if ($this->showPlayedTimes()) {
            $columns[] = ['class' => $this->getCounterClass() . ' optional', 'label' => T_('Played'), 'sort' => 'total_count', 'type' => false];
        }

        if (!$this->hideGenres()) {
            $columns[] = ['class' => $this->getTagsClass() . ' optional', 'label' => T_('Genres'), 'sort' => null, 'type' => false];
        }

        if ($this->showRatings()) {
            $columns[] = ['class' => 'cel_ratings optional', 'label' => T_('Rating'), 'sort' => 'rating', 'type' => true];
        }

        $columns[] = ['class' => 'cel_action essential', 'label' => T_('Action'), 'sort' => null, 'type' => false];

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

    public function getTagsClass(): string
    {
        return $this->getCellClass('cel_tags', 'grid_tags');
    }

    public function getTimeClass(): string
    {
        return $this->getCellClass('cel_time', 'grid_time');
    }

    public function hideGenres(): bool
    {
        return (bool) $this->configContainer->get('hide_genres');
    }

    public function renderRow(Artist $artist): string
    {
        $gatekeeper = $this->gatekeeperFactory->createGuiGatekeeper();
        $mayUse     = $gatekeeper->mayAccess(AccessTypeEnum::INTERFACE, AccessLevelEnum::USER);
        $anonymous  = !$this->configContainer->get('use_auth') || $mayUse;
        $limit      = (int) $this->configContainer->get('direct_play_limit');
        $mayAdd     = $mayUse;
        if ($limit > 0 && !$this->getBrowse()->is_grid_view()) {
            $mayAdd = $artist->song_count <= $limit;
        }

        return (new ArtistRowView(
            $artist,
            $this->configContainer->getWebPath('/client'),
            $this->getCoverClass(),
            $this->getArtistClass(),
            $this->getTimeClass(),
            $this->getCounterClass(),
            $this->getTagsClass(),
            $this->getBrowse()->getId(),
            $this->getBrowse()->is_grid_view(),
            $this->hideGenres(),
            $this->showRatings(),
            $this->showPlayedTimes(),
            (bool) $this->configContainer->get('directplay') && $mayAdd,
            $mayAdd,
            $anonymous && (bool) $this->configContainer->get('sociable'),
            $anonymous && canEditArtist($artist, $gatekeeper->getUserId()),
            $anonymous && Catalog::can_remove($artist)
        ))->render();
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
        return $this->findTemplate('browse/artists.phtml');
    }
}
