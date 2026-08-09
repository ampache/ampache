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
use Ampache\Module\Api\Ajax;
use Ampache\Module\Authorization\Access;
use Ampache\Module\Authorization\AccessFunctionEnum;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Module\Authorization\GatekeeperFactoryInterface;
use Ampache\Module\Playback\Stream_Playlist;
use Ampache\Module\Util\Ui;
use Ampache\Module\Util\ZipHandlerInterface;
use Ampache\Repository\Model\Song;
use Ampache\Repository\Model\User;
use Override;

/**
 * The song browse.
 *
 * Its empty state counted from seven where the row starts at five, and a disabled song a normal user may
 * not see opened a row and emitted no cells at all. The footer also sorted the album column by `album`
 * where the header used the grouping-aware name.
 */
final class SongListRenderer extends AbstractBrowseListRenderer
{
    /**
     * @var list<Song>|null
     */
    private ?array $songs = null;

    public function __construct(
        private readonly ConfigContainerInterface $configContainer,
        private readonly GatekeeperFactoryInterface $gatekeeperFactory,
        private readonly GuiFactoryInterface $guiFactory,
        private readonly ZipHandlerInterface $zipHandler,
    ) {}

    /**
     * Grid view has no room for a checkbox column and an empty browse has nothing to act on.
     */
    public function canMultiselect(): bool
    {
        return !$this->getBrowse()->is_grid_view()
            && $this->getSongs() !== []
            && ($this->isDirectPlay() || $this->mayAdd() || $this->mayBatchDownload());
    }

    /**
     * @return list<array{class: string, label: string, sort: null|string, id: null|string, footer: bool}>
     */
    public function getColumns(): array
    {
        $browseId = $this->getBrowse()->id;
        $columns  = [];
        if ($this->canMultiselect()) {
            $columns[] = ['class' => 'cel_select essential persist', 'label' => '', 'sort' => null, 'id' => null, 'footer' => false];
        }

        $columns[] = ['class' => 'cel_play essential', 'label' => '#', 'sort' => 'track', 'id' => 'song_sort_track' . $browseId, 'footer' => true];
        $columns[] = ['class' => $this->getCellClass('cel_song', 'grid_song') . ' essential persist', 'label' => T_('Song Title'), 'sort' => 'title', 'id' => 'song_sort_title' . $browseId, 'footer' => true];
        $columns[] = ['class' => 'cel_add essential', 'label' => '', 'sort' => null, 'id' => null, 'footer' => false];

        if (!$this->isHidden('cel_artist')) {
            $columns[] = ['class' => $this->getCellClass('cel_artist', 'grid_artist') . ' optional', 'label' => T_('Song Artist'), 'sort' => 'artist', 'id' => 'song_sort_artist' . $browseId, 'footer' => true];
        }

        if (!$this->isHidden('cel_album')) {
            $columns[] = ['class' => $this->getCellClass('cel_album', 'grid_album') . ' essential', 'label' => T_('Album'), 'sort' => $this->getAlbumSort(), 'id' => 'song_sort_' . $this->getAlbumSort() . $browseId, 'footer' => true];
        }

        if (!$this->isHidden('cel_year')) {
            $columns[] = ['class' => 'cel_year', 'label' => T_('Year'), 'sort' => 'year', 'id' => 'song_sort_year', 'footer' => true];
        }

        if (!$this->hideGenres()) {
            $columns[] = ['class' => $this->getCellClass('cel_tags', 'grid_tags') . ' optional', 'label' => T_('Genres'), 'sort' => null, 'id' => null, 'footer' => true];
        }

        $columns[] = ['class' => $this->getCellClass('cel_time', 'grid_time') . ' optional', 'label' => T_('Time'), 'sort' => 'time', 'id' => 'song_sort_time' . $browseId, 'footer' => true];

        if ($this->showLicense()) {
            $columns[] = ['class' => $this->getCellClass('cel_license', 'grid_license') . ' optional', 'label' => T_('License'), 'sort' => null, 'id' => null, 'footer' => true];
        }

        if ($this->showPlayedTimes()) {
            $columns[] = ['class' => $this->getCellClass('cel_counter', 'grid_counter') . ' optional', 'label' => T_('Played'), 'sort' => 'total_count', 'id' => 'song_sort_total_count' . $browseId, 'footer' => true];
        }

        if ($this->showSkippedTimes()) {
            $columns[] = ['class' => $this->getCellClass('cel_counter', 'grid_counter') . ' optional', 'label' => T_('Skipped'), 'sort' => 'total_skip', 'id' => 'song_sort_total_skip' . $browseId, 'footer' => true];
        }

        if ($this->showRatings()) {
            $columns[] = ['class' => 'cel_ratings optional', 'label' => T_('Rating'), 'sort' => 'rating', 'id' => 'song_sort_rating', 'footer' => true];
        }

        $columns[] = ['class' => 'cel_action essential', 'label' => T_('Action'), 'sort' => null, 'id' => null, 'footer' => false];

        if ($this->showDrag()) {
            $columns[] = ['class' => 'cel_drag essential', 'label' => '', 'sort' => null, 'id' => null, 'footer' => false];
        }

        return $columns;
    }

    /**
     * @return list<array{action: string, url: string, icon: string, text: string}>
     */
    public function getMultiselectActions(): array
    {
        $actions = [];
        if ($this->isDirectPlay()) {
            $actions[] = ['action' => 'ajax', 'url' => Ajax::url('?page=stream&action=directplay&object_type={type}&object_id={ids}'), 'icon' => 'play_circle', 'text' => T_('Play')];
            if (Stream_Playlist::check_autoplay_next()) {
                $actions[] = ['action' => 'ajax', 'url' => Ajax::url('?page=stream&action=directplay&object_type={type}&object_id={ids}&playnext=true'), 'icon' => 'menu_open', 'text' => T_('Play next')];
            }

            if (Stream_Playlist::check_autoplay_append()) {
                $actions[] = ['action' => 'ajax', 'url' => Ajax::url('?page=stream&action=directplay&object_type={type}&object_id={ids}&append=true'), 'icon' => 'low_priority', 'text' => T_('Play last')];
            }
        }

        $actions[] = ['action' => 'ajax', 'url' => Ajax::url('?action=basket&type={type}&id={ids}'), 'icon' => 'new_window', 'text' => T_('Add to Temporary Playlist')];
        if ($this->mayAdd()) {
            $actions[] = ['action' => 'playlist', 'url' => '', 'icon' => 'playlist_add', 'text' => Ui::get_add_to_list_label(true)];
        }

        // a selection cannot stream as one file, so the batch zip is the only sane download
        if ($this->mayBatchDownload()) {
            $actions[] = ['action' => 'link', 'url' => $this->configContainer->getWebPath('/client') . '/batch.php?action={type}&id={ids}', 'icon' => 'folder_zip', 'text' => T_('Download')];
        }

        return $actions;
    }

    /**
     * @return list<Song>
     */
    public function getSongs(): array
    {
        if ($this->songs !== null) {
            return $this->songs;
        }

        // repeating a browse's prefetch would also overwrite the threshold-adjusted play counts it cached
        if (!$this->getContext()->prefetched) {
            Song::build_cache($this->getObjectIds());
        }

        $mayManage = $this->gatekeeperFactory->createGuiGatekeeper()
            ->mayAccess(AccessTypeEnum::INTERFACE, AccessLevelEnum::CONTENT_MANAGER);
        $songs = [];
        foreach ($this->getObjectIds() as $objectId) {
            $song = new Song($objectId);
            if ($song->isNew() || (!$song->enabled && !$mayManage)) {
                continue;
            }

            $songs[] = $song;
        }

        return $this->songs = $songs;
    }

    /**
     * The album filter keys the reorder table so two song lists on one page do not share a sortable id.
     */
    public function getTableKey(): string
    {
        return (string) ($this->getBrowse()->get_filter('album') ?? $this->getBrowse()->id);
    }

    public function hideGenres(): bool
    {
        return (bool) $this->configContainer->get('hide_genres');
    }

    public function isDirectPlay(): bool
    {
        return (bool) $this->configContainer->get('directplay');
    }

    public function isGrouped(): bool
    {
        return (bool) $this->configContainer->get('album_group');
    }

    public function isHidden(string $column): bool
    {
        return in_array($column, $this->getContext()->hideColumns, true);
    }

    public function mayAdd(): bool
    {
        return $this->gatekeeperFactory->createGuiGatekeeper()
            ->mayAccess(AccessTypeEnum::INTERFACE, AccessLevelEnum::USER);
    }

    public function mayBatchDownload(): bool
    {
        return Access::check_function(AccessFunctionEnum::FUNCTION_BATCH_DOWNLOAD) && $this->zipHandler->isZipable('song');
    }

    public function renderRow(Song $song): string
    {
        return $this->guiFactory->createSongRowView(
            $this->gatekeeperFactory->createGuiGatekeeper(),
            $song,
            $this->getArgumentParam(),
            $this->showRatings(),
            !$this->getBrowse()->is_grid_view(),
            $this->isGrouped(),
            $this->showTrack(),
            $this->showLicense(),
            $this->hideGenres(),
            $this->isHidden('cel_artist'),
            $this->isHidden('cel_album'),
            $this->isHidden('cel_year'),
            !$this->showDrag()
        )->render();
    }

    /**
     * The drag handle needs a container to reorder within, and the view menu can still hide it.
     */
    public function showDrag(): bool
    {
        return $this->hasArgument() && !$this->isHidden('cel_drag');
    }

    public function showLicense(): bool
    {
        return (bool) $this->configContainer->get('licensing') && (bool) $this->configContainer->get('show_license');
    }

    public function showMultiselect(): bool
    {
        return $this->canMultiselect() && $this->getBrowse()->is_use_select();
    }

    public function showPlayedTimes(): bool
    {
        return (bool) $this->configContainer->get('show_played_times');
    }

    public function showRatings(): bool
    {
        return User::is_registered() && (bool) $this->configContainer->get('ratings');
    }

    public function showSkippedTimes(): bool
    {
        return (bool) $this->configContainer->get('show_skipped_times');
    }

    /**
     * Track numbers only mean something inside a container, and grid view has nowhere to put them.
     */
    public function showTrack(): bool
    {
        return $this->hasArgument() && !$this->getBrowse()->is_grid_view();
    }

    #[Override]
    protected function templateFile(): string
    {
        return $this->findTemplate('browse/songs.phtml');
    }

    private function getAlbumSort(): string
    {
        return ($this->isGrouped()) ? 'album' : 'album_disk';
    }
}
