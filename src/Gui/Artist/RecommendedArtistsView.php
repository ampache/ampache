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

use Ampache\Gui\View\AbstractView;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\Catalog\Catalog;
use Ampache\Repository\Model\Artist;
use Override;

/**
 * The "Similar Artists" panel, listing artists in the catalog and the ones only last.fm knows about.
 *
 * Header, footer and the empty-state cell all read the same column list, which is what keeps them from
 * disagreeing: the template this replaced counted eight columns as seven and left `Played` out of its
 * footer entirely.
 */
final class RecommendedArtistsView extends AbstractView
{
    /**
     * @param list<int> $artistIds
     * @param array<int, array{mbid: string, name: string}> $missingArtists
     */
    public function __construct(
        private readonly GuiGatekeeperInterface $gatekeeper,
        private readonly string $webPath,
        private readonly array $artistIds,
        private readonly array $missingArtists,
        private readonly int $browseId,
        private readonly bool $gridView,
        private readonly bool $hideGenres,
        private readonly bool $hideMoods,
        private readonly bool $showRatings,
        private readonly bool $showPlayedTimes,
        private readonly bool $directPlay,
        private readonly int $directPlayLimit,
        private readonly bool $mayInteract,
        private readonly bool $sociable,
        private readonly bool $mayEdit,
    ) {}

    public function areRatingsShown(): bool
    {
        return $this->showRatings;
    }

    /**
     * @return list<int>
     */
    public function getArtistIds(): array
    {
        return $this->artistIds;
    }

    /**
     * @return list<Artist>
     */
    public function getArtists(): array
    {
        $artists = [];
        foreach ($this->artistIds as $artistId) {
            $artist = new Artist($artistId);
            if (!$artist->isNew()) {
                $artists[] = $artist;
            }
        }

        return $artists;
    }

    public function getBrowseId(): int
    {
        return $this->browseId;
    }

    /**
     * @return list<array{class: string, label: string}>
     */
    public function getColumns(): array
    {
        $columns = [
            ['class' => 'cel_play', 'label' => ''],
            ['class' => 'cel_cover optional', 'label' => T_('Art')],
            ['class' => 'cel_artist', 'label' => T_('Artist')],
            ['class' => 'cel_add', 'label' => ''],
            ['class' => 'cel_songs', 'label' => T_('Songs')],
            ['class' => 'cel_albums', 'label' => T_('Albums')],
            ['class' => 'cel_time', 'label' => T_('Time')],
        ];

        if ($this->showPlayedTimes) {
            $columns[] = ['class' => 'cel_counter optional', 'label' => T_('Played')];
        }

        if (!$this->hideGenres) {
            $columns[] = ['class' => 'cel_tags', 'label' => T_('Genres')];
        }

        if (!$this->hideMoods) {
            $columns[] = ['class' => 'cel_moods', 'label' => T_('Moods')];
        }

        if ($this->showRatings) {
            $columns[] = ['class' => 'cel_ratings optional', 'label' => T_('Rating')];
        }

        $columns[] = ['class' => 'cel_action', 'label' => T_('Action')];

        return $columns;
    }

    /**
     * @return array<int, array{mbid: string, name: string}>
     */
    public function getMissingArtists(): array
    {
        return $this->missingArtists;
    }

    public function getMissingArtistUrl(string $musicBrainzId): string
    {
        return $this->webPath . '/artists.php?action=show_missing&mbid=' . rawurlencode($musicBrainzId);
    }

    public function isEmpty(): bool
    {
        return $this->artistIds === [] && $this->missingArtists === [];
    }

    /**
     * Queueing a very large artist is refused, so the same limit decides both controls.
     */
    public function renderRow(Artist $artist): string
    {
        $mayQueue = ($this->directPlayLimit <= 0)
            ? $this->mayInteract
            : $artist->song_count <= $this->directPlayLimit;

        return (new ArtistRowView(
            $artist,
            $this->webPath,
            'cel_cover',
            'cel_artist',
            'cel_time',
            'cel_counter',
            'cel_tags',
            'cel_moods',
            $this->browseId,
            $this->gridView,
            $this->hideGenres,
            $this->hideMoods,
            $this->showRatings,
            $this->showPlayedTimes,
            $this->directPlay && $mayQueue,
            $mayQueue,
            $this->mayInteract && $this->sociable,
            $this->mayInteract && $this->mayEdit && canEditArtist($artist, $this->gatekeeper->getUserId()),
            $this->mayInteract && Catalog::can_remove($artist)
        ))->render();
    }

    #[Override]
    protected function templateFile(): string
    {
        return $this->findTemplate('recommended_artists.phtml');
    }
}
