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

namespace Ampache\Repository\Model;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class PlaylistFolderTest extends TestCase
{
    /**
     * @return list<array{0: string}>
     */
    public static function acceptedNameDataProvider(): array
    {
        return [
            ['Rock'],
            ['Live at the BBC'],
            ['  padded  '],
            [str_repeat('a', 255)],
        ];
    }

    /**
     * @return list<array{0: string}>
     */
    public static function refusedNameDataProvider(): array
    {
        return [
            [''],
            ['   '],
            ['Rock/Live'],
            ['/'],
            [str_repeat('a', 256)],
        ];
    }

    public function testFromRowBuildsAFolderWithoutTouchingTheDatabase(): void
    {
        $folder = PlaylistFolder::fromRow([
            'id' => '7',
            'user' => '42',
            'parent' => '3',
            'name' => 'Live',
            'sort_order' => '5',
            'date' => '1700000000',
            'last_update' => '1700000001',
        ]);

        self::assertSame(7, $folder->getId());
        self::assertSame(42, $folder->getUserId());
        self::assertSame(3, $folder->getParentId());
        self::assertSame('Live', $folder->getName());
        self::assertSame(5, $folder->getSortOrder());
        self::assertFalse($folder->isNew());
    }

    public function testIsValidTypeAcceptsOnlyTheStoredSpellings(): void
    {
        self::assertTrue(PlaylistFolder::isValidType('playlist'));
        self::assertTrue(PlaylistFolder::isValidType('search'));
        self::assertTrue(PlaylistFolder::isValidType('collection'));

        // the API spelling has to be normalised first, so it is not valid on its own
        self::assertFalse(PlaylistFolder::isValidType('smartlist'));
        self::assertFalse(PlaylistFolder::isValidType('song'));
    }

    public function testIsVisibleOnlyToTheOwner(): void
    {
        $owner = $this->createMock(User::class);
        $owner->method('getId')
            ->willReturn(42);

        $other = $this->createMock(User::class);
        $other->method('getId')
            ->willReturn(43);

        $folder = PlaylistFolder::fromRow(['id' => '7', 'user' => '42', 'name' => 'Rock']);

        self::assertTrue($folder->isVisible($owner));
        self::assertFalse($folder->isVisible($other));
        self::assertFalse($folder->isVisible(null));
        self::assertFalse(new PlaylistFolder()->isVisible($owner));
    }

    public function testNewFolderIsNewAndSitsAtTheRoot(): void
    {
        $folder = new PlaylistFolder();

        self::assertTrue($folder->isNew());
        self::assertSame(0, $folder->getId());
        self::assertSame(PlaylistFolder::ROOT, $folder->getParentId());
    }

    /**
     * A refused name must leave the previous one alone rather than blanking it
     */
    #[DataProvider('refusedNameDataProvider')]
    public function testSetNameKeepsThePreviousNameWhenRefused(string $name): void
    {
        $folder = new PlaylistFolder();
        $folder->setName('Rock');

        $folder->setName($name);

        self::assertSame('Rock', $folder->getName());
        self::assertFalse(PlaylistFolder::isValidName($name));
    }

    #[DataProvider('acceptedNameDataProvider')]
    public function testSetNameStoresAnAcceptedNameTrimmed(string $name): void
    {
        $folder = new PlaylistFolder();

        $folder->setName($name);

        self::assertSame(trim($name), $folder->getName());
    }

    public function testSetParentIdClampsANegativeParentToTheRoot(): void
    {
        $folder = new PlaylistFolder();

        $folder->setParentId(-5);

        self::assertSame(PlaylistFolder::ROOT, $folder->getParentId());
    }

    public function testTypeSpellingsRoundTripBetweenTableAndApi(): void
    {
        self::assertSame('search', PlaylistFolder::normalizeType('smartlist'));
        self::assertSame('smartlist', PlaylistFolder::denormalizeType('search'));
        self::assertSame('playlist', PlaylistFolder::normalizeType('playlist'));
        self::assertSame('collection', PlaylistFolder::denormalizeType('collection'));
    }
}
