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
use Ampache\Repository\Model\Song;
use Override;

/**
 * A song's lyrics, with the album art and the links back to its album and artist.
 */
final class LyricsView extends AbstractView
{
    /**
     * @param array{text?: null|string, url?: null|string} $lyrics
     */
    public function __construct(
        private readonly string $webPath,
        private readonly Song $song,
        private readonly array $lyrics,
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
     * Lyrics text comes from an ID3 tag or a third-party provider, so it is never trusted markup; this builds the
     * only HTML it may contain itself (line breaks), which is why the template prints it with raw().
     */
    public function getLyrics(): string
    {
        $text = $this->lyrics['text'] ?? null;

        if ($text === null || $text === '') {
            return T_('No lyrics found.');
        }

        // normalize a previously-stored literal <br> back to a newline before escaping, so old provider fetches still line-break correctly
        $text = (string) preg_replace('/<br\s*\/?>/i', "\n", $text);

        return nl2br($this->e($text));
    }

    public function getLyricsUrl(): ?string
    {
        $url = $this->lyrics['url'] ?? null;

        return ($url === '') ? null : $url;
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

    #[Override]
    protected function templateFile(): string
    {
        return $this->findTemplate('lyrics.phtml');
    }
}
