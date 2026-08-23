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

namespace Ampache\Module\Art\Generated;

use Ampache\Module\Database\DatabaseConnectionInterface;
use Ampache\Repository\Model\Album;
use Ampache\Repository\Model\ModelFactoryInterface;

/**
 * Turns a library item into the handful of values a template needs.
 *
 * This is the only part that touches models, which is what keeps the templates testable: they take a
 * recipe and give back a string, with no database anywhere near them.
 */
final readonly class RecipeBuilder implements RecipeBuilderInterface
{
    /** How many artists colour a playlist tile. Five rows is what the drawing has room for. */
    private const int VOICES = 5;

    public function __construct(
        private ModelFactoryInterface $modelFactory,
        private DatabaseConnectionInterface $connection,
    ) {}

    public function build(string $objectType, int $objectId): ?Recipe
    {
        return match ($objectType) {
            'album', 'album_disk' => $this->fromAlbum($objectId),
            'artist' => $this->fromArtist($objectId),
            'song' => $this->fromSong($objectId),
            'playlist' => $this->fromPlaylist($objectId),
            default => null,
        };
    }

    private function artistName(Album $album): string
    {
        if ($album->album_artist !== null && $album->album_artist > 0) {
            $artist = $this->modelFactory->createArtist($album->album_artist);
            $name   = trim((string) $artist->get_fullname());
            if ($name !== '') {
                return $name;
            }
        }

        return trim($album->get_fullname(true));
    }

    private function fromAlbum(int $albumId): ?Recipe
    {
        $album = $this->modelFactory->createAlbum($albumId);
        if ($album->isNew()) {
            return null;
        }

        $name = trim($album->get_fullname(true));
        if ($name === '') {
            return null;
        }

        $artist = $this->artistName($album);

        return new Recipe(
            label: T_('ALBUM'),
            name: $name,
            subtitle: mb_strtoupper($artist),
            motif: 'record',
            // the artist seeds the colour, so every release of theirs shares one family
            seed: $artist,
            palette: Palette::forSeed($artist),
        );
    }

    private function fromArtist(int $artistId): ?Recipe
    {
        $artist = $this->modelFactory->createArtist($artistId);
        if ($artist->isNew()) {
            return null;
        }

        $name = trim((string) $artist->get_fullname());
        if ($name === '') {
            return null;
        }

        return new Recipe(
            label: T_('ARTIST'),
            name: $name,
            subtitle: '',
            motif: 'medallion',
            seed: $name,
            palette: Palette::forSeed($name),
        );
    }

    private function fromPlaylist(int $playlistId): ?Recipe
    {
        $playlist = $this->modelFactory->createPlaylist($playlistId);
        if ($playlist->isNew()) {
            return null;
        }

        $name = trim((string) $playlist->get_fullname());
        if ($name === '') {
            return null;
        }

        $voices = $this->voices($playlistId);
        $count  = $playlist->get_media_count('song');
        $owner  = (string) ($playlist->username ?? '');

        $subtitle = sprintf(nT_('%d track', '%d tracks', $count), $count);
        if ($owner !== '') {
            $subtitle .= ' · ' . $owner;
        }

        return new Recipe(
            label: T_('PLAYLIST'),
            name: $name,
            subtitle: $subtitle,
            motif: 'tracklist',
            seed: $name,
            palette: Palette::forSeed($name),
            voices: $voices,
        );
    }

    /**
     * Only songs with nowhere to inherit from. A song normally shows its album's cover, and giving every
     * track its own tile would break that link for no gain.
     */
    private function fromSong(int $songId): ?Recipe
    {
        $song = $this->modelFactory->createSong($songId);
        if ($song->isNew()) {
            return null;
        }

        $album = trim($song->get_album_fullname(0, true));
        if ($album !== '' && $album !== T_('Unknown (Orphaned)')) {
            return null;
        }

        $name = trim((string) $song->get_fullname());
        if ($name === '') {
            return null;
        }

        $artist = trim($song->get_parent_fullname());
        $seed   = ($artist !== '') ? $artist : $name;

        return new Recipe(
            label: T_('SONG'),
            name: $name,
            subtitle: $artist,
            motif: 'waveform',
            seed: $seed,
            palette: Palette::forSeed($seed),
        );
    }

    /**
     * The first few artists on the list. They colour one row each, so a playlist wears the colours of
     * what is actually inside it rather than a shade drawn from its own name.
     *
     * @return list<string>
     */
    private function voices(int $playlistId): array
    {
        $rows = $this->connection->query(
            'SELECT DISTINCT `artist`.`name` FROM `playlist_data` '
            . 'INNER JOIN `song` ON `song`.`id` = `playlist_data`.`object_id` '
            . 'INNER JOIN `artist` ON `artist`.`id` = `song`.`artist` '
            . "WHERE `playlist_data`.`playlist` = ? AND `playlist_data`.`object_type` = 'song' "
            . "AND `artist`.`name` <> '' ORDER BY `playlist_data`.`track` LIMIT " . self::VOICES,
            [$playlistId]
        );

        $out = [];
        while ($name = $rows->fetchColumn()) {
            $out[] = (string) $name;
        }

        return $out;
    }
}
