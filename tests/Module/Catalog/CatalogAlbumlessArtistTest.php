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

use Ampache\Module\Database\DatabaseLockInterface;
use Ampache\Repository\AlbumRepositoryInterface;
use Ampache\Repository\ArtistRepositoryInterface;
use Ampache\Repository\CatalogMapRepositoryInterface;
use Ampache\Repository\Model\Artist;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use ReflectionProperty;

/**
 * A file with no album tag still needs an album artist. Resolving one here would create the row before
 * the caller can say who uploaded the file, and an artist with no owner is one whose art its own
 * uploader cannot edit. The name is handed over instead, and `Song::insert()` creates the row.
 */
class CatalogAlbumlessArtistTest extends TestCase
{
    /** @var list<string> every artist name the run asked to create */
    private array $created = [];

    public function testAnAlbumlessFileCreatesNoArtistOfItsOwn(): void
    {
        Catalog::filter_tag_results($this->tags('Solo Artist'));

        self::assertNotContains('Solo Artist', $this->created);
    }

    public function testAnAlbumlessFileNamesItsAlbumArtistForTheCaller(): void
    {
        $results = Catalog::filter_tag_results($this->tags('Solo Artist'));

        self::assertSame('Solo Artist', $results['albumartist']);
        self::assertArrayNotHasKey('albumartist_id', $results, 'the caller resolves it, knowing the uploader');
    }

    protected function setUp(): void
    {
        $this->created = [];

        // an albumless file reaches `Album::check()` before the album artist, so the row it needs is mocked
        $artistRepository = $this->createMock(ArtistRepositoryInterface::class);
        $artistRepository->method('findIdByName')->willReturn(null);
        $artistRepository->method('create')
            ->willReturnCallback(function (string $name): int {
                $this->created[] = $name;

                return count($this->created);
            });

        $lock = $this->createMock(DatabaseLockInterface::class);
        $lock->method('acquire')->willReturn(false);

        $dic = $this->createMock(ContainerInterface::class);
        $dic->method('get')->willReturnCallback(fn(string $id): object => match ($id) {
            AlbumRepositoryInterface::class => $this->createMock(AlbumRepositoryInterface::class),
            ArtistRepositoryInterface::class => $artistRepository,
            CatalogMapRepositoryInterface::class => $this->createMock(CatalogMapRepositoryInterface::class),
            DatabaseLockInterface::class => $lock,
            default => $this->createMock(LoggerInterface::class),
        });

        $GLOBALS['dic'] = $dic;

        // `Artist::check()` answers a repeated name from a static map, which would hide a second create
        new ReflectionProperty(Artist::class, '_mapcache')->setValue(null, []);
    }

    /**
     * @return array<string, mixed>
     */
    private function tags(string $artist): array
    {
        return [
            'file' => '/music/orphan.mp3',
            'catalog' => 1,
            'title' => 'Orphan',
            'artist' => $artist,
            'album' => '',
            'albumartist' => '',
            'year' => 2026,
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
