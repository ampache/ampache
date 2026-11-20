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

namespace Ampache\Module\Database\Query;

use Ampache\Config\AmpConfig;
use Ampache\MockeryTestCase;
use PDO;

class CatalogQueryTest extends MockeryTestCase
{
    private ?string $originalDatabaseName = null;

    /**
     * Every other string filter in CatalogQuery escapes its value, but the `gather_types` IN-list imploded the
     * raw filter array straight into the SQL, so a quote in a list element broke out of the literal and injected
     * arbitrary SQL (reachable through the browse ajax `tag[]` parameter).
     */
    public function testGatherTypesEscapesEachListElement(): void
    {
        $this->stubDbaEscape();
        $query = new Query(0, false);

        $sql = (new CatalogQuery())->get_sql_filter($query, 'gather_types', ['music', "x') OR SLEEP(1)-- -"]);

        self::assertSame(
            " `catalog`.`gather_types` IN ('music', 'x\\') OR SLEEP(1)-- -') AND ",
            $sql
        );
    }

    protected function tearDown(): void
    {
        AmpConfig::set('database_name', $this->originalDatabaseName, true);
        AmpConfig::set('dbh_test', null, true);

        parent::tearDown();
    }

    private function stubDbaEscape(): void
    {
        $this->originalDatabaseName = AmpConfig::get('database_name');

        $pdo = $this->createMock(PDO::class);
        $pdo->method('quote')->willReturnCallback(static fn(string $value): string => "'" . addslashes($value) . "'");

        AmpConfig::set('database_name', 'test', true);
        AmpConfig::set('dbh_test', $pdo, true);
    }
}
