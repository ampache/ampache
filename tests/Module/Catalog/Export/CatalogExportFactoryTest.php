<?php

declare(strict_types=1);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2024
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

namespace Ampache\Module\Catalog\Export;

use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\SongRepositoryInterface;
use Generator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class CatalogExportFactoryTest extends TestCase
{
    private CatalogExportFactory $subject;

    protected function setUp(): void
    {
        $this->subject = new CatalogExportFactory(
            $this->createMock(SongRepositoryInterface::class),
            $this->createMock(ModelFactoryInterface::class)
        );
    }

    /**
     * @param class-string $exporterClass
     */
    #[DataProvider(methodName: 'exportTypeDataProvider')]
    public function testCreateFromExportTypeReturnsInstance(
        CatalogExportTypeEnum $exportType,
        string $exporterClass
    ): void {
        static::assertInstanceOf(
            $exporterClass,
            $this->subject->createFromExportType($exportType)
        );
    }

    public static function exportTypeDataProvider(): Generator
    {
        yield [CatalogExportTypeEnum::ITUNES, ItunesExporter::class];
        yield [CatalogExportTypeEnum::CSV, CsvExporter::class];
    }
}
