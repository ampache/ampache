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

namespace Ampache\Module\System\Update;

use PHPUnit\Framework\TestCase;

/**
 * `resources/sql/ampache.sql` is a snapshot of an already-migrated schema and records the version it was taken at.
 * `InstallationHelper::install_insert_db()` reads that version and replays every later migration, so the dump is
 * allowed to lag the code - but the version it claims has to be real and must never run ahead of the schema in the
 * file, because a version above what the dump contains makes the updater see nothing pending and skip those
 * migrations on every fresh install.
 */
class SeedSchemaVersionTest extends TestCase
{
    private const string SEED_FILE = __DIR__ . '/../../../../resources/sql/ampache.sql';

    public function testSeedDeclaresADatabaseVersion(): void
    {
        self::assertIsInt(
            $this->getSeedVersion(),
            'resources/sql/ampache.sql must INSERT a `db_version` row into `update_info`'
        );
    }

    public function testSeedVersionIsAKnownMigration(): void
    {
        $version = $this->getSeedVersion();

        self::assertContains(
            $version,
            array_keys(iterator_to_array(Versions::getPendingMigrations(0), true)),
            sprintf('The seed declares db_version %d, which is not a registered migration', $version)
        );
    }

    public function testSeedVersionIsNotAheadOfTheCode(): void
    {
        self::assertLessThanOrEqual(
            Versions::MAXIMUM_UPDATABLE_VERSION,
            $this->getSeedVersion(),
            'The seed declares a db_version newer than MAXIMUM_UPDATABLE_VERSION'
        );
    }

    private function getSeedVersion(): int
    {
        $contents = (string) file_get_contents(self::SEED_FILE);

        self::assertMatchesRegularExpression(
            "/\('db_version', '(\d+)'\)/",
            $contents,
            'resources/sql/ampache.sql must INSERT a `db_version` row into `update_info`'
        );

        preg_match("/\('db_version', '(\d+)'\)/", $contents, $matches);

        return (int) $matches[1];
    }
}
