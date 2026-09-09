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

namespace Ampache\Module\Catalog;

use PHPUnit\Framework\TestCase;

/**
 * album_artist is part of an album's identity, so an artist invented from a single track splits a
 * compilation into one album per artist, all sharing the same name. Deciding it needs the whole album,
 * which is why update_album_artist() does it after the scan and this stage must not guess.
 *
 * The branches that resolve an id are not covered here: they reach Artist::get_fullname_by_id(), which
 * needs a database.
 */
class CatalogAlbumArtistTest extends TestCase
{
    public function testATaggedAlbumArtistSkipsTheFallbackEntirely(): void
    {
        $results = Catalog::filter_tag_results($this->tags('Compilation', 'First Artist', 'Various Artists'));

        $this->assertSame('Various Artists', $results['albumartist']);
        $this->assertArrayNotHasKey('albumartist_id', $results, 'the tag is authoritative, nothing is resolved here');
    }

    public function testNamedAlbumWithoutAnAlbumArtistTagIsLeftUndecided(): void
    {
        $results = Catalog::filter_tag_results($this->tags('Compilation', 'First Artist'));

        $this->assertNull(
            $results['albumartist_id'],
            'one file cannot tell whether the album has a single artist or many'
        );
    }

    public function testTracksOfOneAlbumByDifferentArtistsStillAgree(): void
    {
        $first  = Catalog::filter_tag_results($this->tags('Compilation', 'First Artist'));
        $second = Catalog::filter_tag_results($this->tags('Compilation', 'Second Artist'));
        $third  = Catalog::filter_tag_results($this->tags('Compilation', 'Third Artist'));

        // taking the song artist here gave each of these its own album of the same name
        $this->assertSame($first['albumartist_id'], $second['albumartist_id']);
        $this->assertSame($second['albumartist_id'], $third['albumartist_id']);
    }

    /**
     * @return array<string, mixed>
     */
    private function tags(string $album, string $artist, string $albumartist = ''): array
    {
        return [
            'file' => '/music/track.mp3',
            'catalog' => 1,
            'title' => 'Track',
            'artist' => $artist,
            'album' => $album,
            'albumartist' => $albumartist,
            'year' => 1998,
            'disk' => 1,
            'disksubtitle' => '',
            'track' => 1,
            'mode' => 'cbr',
            'bitrate' => 128000,
            'rate' => 44100,
            'channels' => 2,
            'size' => 1024,
            'time' => 60,
            'mime' => 'audio/mpeg',
            'release_type' => '',
            'barcode' => '',
            'catalog_number' => '',
            'version' => '',
            'replaygain_track_gain' => null,
            'replaygain_track_peak' => null,
            'replaygain_album_gain' => null,
            'replaygain_album_peak' => null,
            'r128_track_gain' => null,
            'r128_album_gain' => null,
        ];
    }
}
