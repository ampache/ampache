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

use Ampache\Gui\View\AbstractView;
use Ampache\Module\Application\Song\ShowLyricsAction;
use Ampache\Module\System\Core;
use Ampache\Repository\Model\LyricsSourceEnum;
use Ampache\Repository\Model\Song;
use Override;

/**
 * A song's lyrics, with the album art, the links back to its album and artist, and the editor's controls.
 */
final class LyricsView extends AbstractView
{
    /**
     * @param array{text?: null|string, url?: null|string} $lyrics
     * @param array<string, array{text?: null|string, url?: null|string}> $candidates
     */
    public function __construct(
        private readonly string $webPath,
        private readonly Song $song,
        private readonly array $lyrics,
        private readonly bool $isStored = true,
        private readonly bool $mayEdit = false,
        private readonly array $candidates = [],
        private readonly bool $isRefreshed = false,
    ) {}

    public function getAlbumName(): string
    {
        return $this->song->get_album_fullname();
    }

    public function getAlbumUrl(): string
    {
        return $this->webPath . '/albums.php?action=show&album=' . $this->song->album;
    }

    public function getArtistName(): string
    {
        return $this->song->get_parent_fullname();
    }

    public function getArtistUrl(): string
    {
        return $this->webPath . '/artists.php?action=show&artist=' . $this->song->artist;
    }

    /**
     * Each plugin answer with text, in the order the plugins were asked.
     *
     * @return list<array{plugin: string, text: string, url: null|string}>
     */
    public function getCandidates(): array
    {
        $results = [];
        foreach ($this->candidates as $plugin => $candidate) {
            $text = LyricsLinesView::toPlainText((string) ($candidate['text'] ?? ''));
            if (trim($text) === '') {
                continue;
            }

            $url       = (string) ($candidate['url'] ?? '');
            $results[] = [
                'plugin' => (string) $plugin,
                'text' => $text,
                'url' => ($url === '') ? null : $url,
            ];
        }

        return $results;
    }

    /**
     * Clear removes lyrics whatever set them, so hand-set ones get a warning that names the loss.
     */
    public function getClearConfirmation(): string
    {
        return ($this->isUserSet())
            ? T_('These lyrics were set by hand and will be lost. Clear them?')
            : T_('Clear the stored lyrics?');
    }

    /**
     * A one-use token for one of the page's forms; every form needs its own.
     */
    public function getFormToken(): string
    {
        return Core::form_register(ShowLyricsAction::FORM_NAME);
    }

    public function getFormUrl(): string
    {
        return $this->webPath . '/song.php?action=show_lyrics&song_id=' . $this->song->id;
    }

    /**
     * The lyrics lines, rendered the same way here and in the web player's lyrics panel.
     */
    public function getLinesView(): LyricsLinesView
    {
        return new LyricsLinesView($this->song->getId(), (string) ($this->lyrics['text'] ?? ''));
    }

    public function getLyricsUrl(): ?string
    {
        $url = $this->lyrics['url'] ?? null;

        return ($url === '') ? null : $url;
    }

    public function getRefreshUrl(): string
    {
        return $this->getFormUrl() . '&refresh=1';
    }

    public function getSong(): Song
    {
        return $this->song;
    }

    public function getSongUrl(): string
    {
        return $this->webPath . '/song.php?action=show_song&song_id=' . $this->song->id;
    }

    /**
     * An orphaned song has no album to show art for.
     */
    public function hasAlbumArt(): bool
    {
        return $this->getAlbumName() !== T_('Unknown (Orphaned)');
    }

    /**
     * The editor asked the plugins again, so an empty answer is worth saying out loud.
     */
    public function isRefreshed(): bool
    {
        return $this->isRefreshed;
    }

    public function isStored(): bool
    {
        return $this->isStored;
    }

    public function isUserSet(): bool
    {
        return $this->isStored && $this->song->lyrics_source === LyricsSourceEnum::USER;
    }

    public function mayEdit(): bool
    {
        return $this->mayEdit;
    }

    #[Override]
    protected function templateFile(): string
    {
        return $this->findTemplate('lyrics.phtml');
    }
}
