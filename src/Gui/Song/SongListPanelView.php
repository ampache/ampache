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

namespace Ampache\Gui\Song;

use Ampache\Gui\GuiFactoryInterface;
use Ampache\Gui\View\AbstractView;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Repository\Model\Song;
use Override;

/**
 * A read-only table of songs, used by the "top tracks" and "similar songs" panels.
 *
 * The two were byte-identical apart from the table id, so they share this rather than drifting apart.
 * Neither offers reordering, which is why there is no drag column: the templates asked for one from a
 * variable nothing ever set.
 */
final class SongListPanelView extends AbstractView
{
    /**
     * @param list<int> $songIds
     * @param list<string> $hiddenColumns
     */
    public function __construct(
        private readonly GuiFactoryInterface $guiFactory,
        private readonly GuiGatekeeperInterface $gatekeeper,
        private readonly string $tableId,
        private readonly array $songIds,
        private readonly array $hiddenColumns,
        private readonly bool $showRatings,
        private readonly bool $hideGenres,
        private readonly bool $albumGroup,
        private readonly bool $showLicense,
        private readonly bool $showPlayedTimes,
        private readonly bool $showSkippedTimes,
        private readonly bool $maySeeDisabled,
        private readonly ?string $onRenderScript = null,
    ) {}

    public function areRatingsShown(): bool
    {
        return $this->showRatings;
    }

    /**
     * The visible columns in order, so the empty-state cell can span exactly as many as the header has.
     *
     * @return list<array{class: string, label: string}>
     */
    public function getColumns(): array
    {
        $columns = [
            ['class' => 'cel_play essential', 'label' => ''],
            ['class' => 'cel_song essential persist', 'label' => T_('Song Title')],
            ['class' => 'cel_add essential', 'label' => ''],
        ];

        if (!$this->isColumnHidden('cel_artist')) {
            $columns[] = ['class' => 'cel_artist optional', 'label' => T_('Song Artist')];
        }

        if (!$this->isColumnHidden('cel_album')) {
            $columns[] = ['class' => 'cel_album essential', 'label' => T_('Album')];
        }

        if (!$this->isColumnHidden('cel_year')) {
            $columns[] = ['class' => 'cel_year', 'label' => T_('Year')];
        }

        if (!$this->hideGenres) {
            $columns[] = ['class' => 'cel_tags optional', 'label' => T_('Genres')];
        }

        $columns[] = ['class' => 'cel_time optional', 'label' => T_('Time')];

        if ($this->showPlayedTimes) {
            $columns[] = ['class' => 'cel_counter optional', 'label' => T_('Played')];
        }

        if ($this->showSkippedTimes) {
            $columns[] = ['class' => 'cel_counter optional', 'label' => T_('Skipped')];
        }

        if ($this->showRatings) {
            $columns[] = ['class' => 'cel_ratings optional', 'label' => T_('Rating')];
        }

        $columns[] = ['class' => 'cel_action essential', 'label' => T_('Actions')];

        return $columns;
    }

    public function getOnRenderScript(): ?string
    {
        return $this->onRenderScript;
    }

    /**
     * @return list<int>
     */
    public function getSongIds(): array
    {
        return $this->songIds;
    }

    /**
     * A disabled song is only listed for someone who can act on it.
     *
     * @return list<Song>
     */
    public function getSongs(): array
    {
        $songs = [];
        foreach ($this->songIds as $songId) {
            $song = new Song($songId);
            if ($song->isNew() || (!$song->enabled && !$this->maySeeDisabled)) {
                continue;
            }

            $songs[] = $song;
        }

        return $songs;
    }

    public function getTableId(): string
    {
        return $this->tableId;
    }

    public function renderRow(Song $song): string
    {
        return $this->guiFactory->createSongRowView(
            $this->gatekeeper,
            $song,
            '',
            $this->showRatings,
            true,
            $this->albumGroup,
            false,
            $this->showLicense,
            $this->hideGenres,
            $this->isColumnHidden('cel_artist'),
            $this->isColumnHidden('cel_album'),
            $this->isColumnHidden('cel_year'),
            true
        )->render();
    }

    #[Override]
    protected function templateFile(): string
    {
        return $this->findTemplate('song_list_panel.phtml');
    }

    private function isColumnHidden(string $column): bool
    {
        return in_array($column, $this->hiddenColumns, true);
    }
}
