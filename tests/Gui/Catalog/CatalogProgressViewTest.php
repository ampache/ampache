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

namespace Ampache\Gui\Catalog;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class CatalogProgressViewTest extends TestCase
{
    /**
     * The ajax updates for a running catalog process write into these ids, so a rename here silently
     * stops the page updating.
     *
     * @return list<array{CatalogProgressTypeEnum, string, string}>
     */
    public static function elementIdDataProvider(): array
    {
        return [
            [CatalogProgressTypeEnum::ADD, 'add_count_666', 'add_dir_666'],
            [CatalogProgressTypeEnum::ART, 'count_art_666', 'read_art_666'],
            [CatalogProgressTypeEnum::CLEAN, 'clean_count_666', 'clean_dir_666'],
            [CatalogProgressTypeEnum::VERIFY, 'verify_count_666', 'verify_dir_666'],
        ];
    }

    public function testGetCounterInitialValueIsOnlySetWhereNothingIsAValidResult(): void
    {
        static::assertNotSame('', (new CatalogProgressView(CatalogProgressTypeEnum::ADD, 1))->getCounterInitialValue());
        static::assertNotSame('', (new CatalogProgressView(CatalogProgressTypeEnum::ART, 1))->getCounterInitialValue());
        static::assertSame('', (new CatalogProgressView(CatalogProgressTypeEnum::CLEAN, 1))->getCounterInitialValue());
        static::assertSame('', (new CatalogProgressView(CatalogProgressTypeEnum::VERIFY, 1))->getCounterInitialValue());
    }

    #[DataProvider('elementIdDataProvider')]
    public function testGetElementIdsSuffixTheCatalogId(
        CatalogProgressTypeEnum $type,
        string $counterId,
        string $readerId,
    ): void {
        $subject = new CatalogProgressView($type, 666, 'some-catalog');

        static::assertSame($counterId, $subject->getCounterElementId());
        static::assertSame($readerId, $subject->getReaderElementId());
    }

    public function testGetHeadingEscapesTheCatalogName(): void
    {
        $subject = new CatalogProgressView(CatalogProgressTypeEnum::CLEAN, 666, 'Rock & Roll');

        static::assertStringContainsString('Rock &amp; Roll', $subject->getHeading());
        static::assertStringNotContainsString('Rock & Roll', $subject->getHeading());
    }

    /**
     * The art search is not catalog-scoped in its wording, so it is the one heading with no name slot.
     */
    public function testGetHeadingOmitsTheNameForTheArtSearch(): void
    {
        $subject = new CatalogProgressView(CatalogProgressTypeEnum::ART, 666, 'some-catalog');

        static::assertStringNotContainsString('some-catalog', $subject->getHeading());
    }

    /**
     * A catalog with no name still has to render, so the heading falls back to an empty name.
     */
    public function testGetHeadingRendersWithoutACatalogName(): void
    {
        $subject = new CatalogProgressView(CatalogProgressTypeEnum::VERIFY, 666);

        static::assertStringContainsString('<strong>[  ]</strong>', $subject->getHeading());
    }
}
