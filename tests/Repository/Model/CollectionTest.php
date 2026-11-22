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
 */

namespace Ampache\Repository\Model;

use Generator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

class CollectionTest extends TestCase
{
    private mixed $previousDic = null;

    public static function validTypeDataProvider(): Generator
    {
        yield 'folder' => ['folder', true];
        yield 'genre in the spelling a collection stores' => ['genre', true];
        // `tag` is the table name; anything arriving from a template has to be denormalized before it is checked
        yield 'genre named after its table' => ['tag', false];
        // a smartlist is never stored as itself, it contributes the songs it resolves to, so it is not valid here
        yield 'search' => ['search', false];
        yield 'collection' => ['collection', false];
    }

    /**
     * A pinned collection takes only its own type, whatever the caller sends
     */
    public function testAcceptsTypeRefusesAnythingButThePinnedType(): void
    {
        $subject = new Collection();

        $subject->object_type = 'folder';

        self::assertTrue($subject->acceptsType('folder'));
        self::assertFalse($subject->acceptsType('song'));
    }

    public function testAcceptsTypeTakesAnythingValidWhenMixed(): void
    {
        $subject = new Collection();

        self::assertTrue($subject->acceptsType('folder'));
        self::assertTrue($subject->acceptsType('song'));
        self::assertFalse($subject->acceptsType('search'));
    }

    // anyone can add a playlist id to their own collection, so a private member must not expand through it
    public function testGetMediasSkipsAPrivateMemberTheOwnerCannotSee(): void
    {
        $hidden = $this->createMock(Playlist::class);
        $hidden->method('isPrivate')->willReturn(true);
        $hidden->method('isVisible')->willReturn(false);
        $hidden->expects(self::never())->method('get_medias');

        $shared = $this->createMock(Playlist::class);
        $shared->method('isPrivate')->willReturn(true);
        $shared->method('isVisible')->willReturn(true);
        $shared->method('get_medias')->willReturn([
            ['object_type' => LibraryItemEnum::SONG, 'object_id' => 5, 'track' => 1, 'track_id' => 9],
        ]);

        $medias = $this->collectionHolding([1 => $hidden, 2 => $shared])->get_medias();

        self::assertSame([5], array_column($medias, 'object_id'));
    }

    #[DataProvider('validTypeDataProvider')]
    public function testIsValidTypeAnswersForTheType(string $objectType, bool $expected): void
    {
        self::assertSame($expected, Collection::isValidType($objectType));
    }

    protected function tearDown(): void
    {
        $GLOBALS['dic'] = $this->previousDic;
    }

    /**
     * @param array<int, Playlist> $playlists
     */
    private function collectionHolding(array $playlists): Collection
    {
        $loader = $this->createMock(LibraryItemLoaderInterface::class);
        $loader->method('load')->willReturnCallback(
            static fn(LibraryItemEnum $type, int $id): ?Playlist => $playlists[$id] ?? null
        );
        $dic = $this->createMock(ContainerInterface::class);
        $dic->method('get')->willReturn($loader);
        $this->previousDic = $GLOBALS['dic'] ?? null;
        $GLOBALS['dic']    = $dic;

        $collection = $this->createPartialMock(Collection::class, ['get_items']);
        $collection->method('get_items')->willReturn(array_map(
            static fn(int $id): array => ['object_type' => LibraryItemEnum::PLAYLIST, 'object_id' => $id, 'track' => $id, 'track_id' => $id],
            array_keys($playlists)
        ));

        return $collection;
    }
}
