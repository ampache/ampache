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
 */

namespace Ampache\Module\System\Update\Migration;

use Ahc\Cli\IO\Interactor;
use Traversable;

interface MigrationInterface
{
    /**
     * Performs the actual migration steps
     */
    public function migrate(): void;

    /**
     * Sets the cli interactor instance
     */
    public function setInteractor(?Interactor $interactor): void;

    /**
     * Returns a list of changelog-strings
     *
     * @return list<non-empty-string>
     */
    public function getChangelog(): array;

    /**
     * Returns `true` if the migration should trigger a warning within the UI
     */
    public function hasWarning(): bool;

    /**
     * Returns the sql-statements used for create/migration the database tables
     *
     * @return Traversable<string, string> Dictionary with the key being the table name and value the sql-statements
     */
    public function getTableMigrations(
        string $collation,
        string $charset,
        string $engine
    ): Traversable;
}
