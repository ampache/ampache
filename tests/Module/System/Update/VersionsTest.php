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

use Ampache\Module\System\Update\Migration\MigrationInterface;
use PHPUnit\Framework\TestCase;

/**
 * Registering a migration is two edits -- the `$versions` entry and `MAXIMUM_UPDATABLE_VERSION`. Forgetting the
 * constant makes the version ping-pong between update and rollback, which only shows up on a live update.
 */
class VersionsTest extends TestCase
{
    /**
     * A registration pointing at the wrong class would silently run the previous migration under a new version.
     */
    public function testEveryMigrationClassNameMatchesItsVersion(): void
    {
        foreach (iterator_to_array(Versions::getPendingMigrations(0), true) as $version => $migrationClass) {
            $shortName = substr((string) strrchr($migrationClass, '\\'), 1);

            self::assertSame(
                sprintf('Migration%d', $version),
                $shortName,
                sprintf('Migration %d is registered as %s; the class name must match the version', $version, $shortName)
            );
        }
    }

    public function testEveryRegisteredMigrationIsLoadableAndAMigration(): void
    {
        foreach (iterator_to_array(Versions::getPendingMigrations(0), true) as $version => $migrationClass) {
            self::assertTrue(
                class_exists($migrationClass),
                sprintf('Migration %d is registered as %s, which does not exist', $version, $migrationClass)
            );
            self::assertContains(
                MigrationInterface::class,
                class_implements($migrationClass) ?: [],
                sprintf('Migration %d (%s) does not implement MigrationInterface', $version, $migrationClass)
            );
        }
    }

    public function testMaximumUpdatableVersionMatchesTheNewestMigration(): void
    {
        $versions = $this->getRegisteredVersions();

        self::assertSame(
            max($versions),
            Versions::MAXIMUM_UPDATABLE_VERSION,
            sprintf(
                'MAXIMUM_UPDATABLE_VERSION is %d but the newest registered migration is %d. Bump the constant in '
                . 'the same commit that registers the migration, or the database version will ping-pong on update.',
                Versions::MAXIMUM_UPDATABLE_VERSION,
                max($versions)
            )
        );
    }

    /**
     * `UpdateRunner` applies them in array order, so an entry out of sequence runs before what it depends on.
     */
    public function testVersionsAreRegisteredInAscendingOrder(): void
    {
        $versions = $this->getRegisteredVersions();
        $sorted   = $versions;
        sort($sorted);

        self::assertSame($sorted, $versions, 'Migrations must be registered in ascending version order');
    }

    /**
     * @return list<int>
     */
    private function getRegisteredVersions(): array
    {
        return array_keys(iterator_to_array(Versions::getPendingMigrations(0), true));
    }
}
