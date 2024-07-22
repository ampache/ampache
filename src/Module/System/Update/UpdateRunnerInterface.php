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

namespace Ampache\Module\System\Update;

use Ahc\Cli\IO\Interactor;
use Ampache\Module\System\Update\Exception\UpdateFailedException;
use Ampache\Module\System\Update\Migration\MigrationInterface;
use Iterator;
use Traversable;

interface UpdateRunnerInterface
{
    /**
     * Run the rollback queries on the database
     *
     * @throws UpdateFailedException
     */
    public function runRollback(int $currentVersion, ?Interactor $interactor = null): void;

    /**
     * Runs the migrations with are determined by the given updates
     *
     * @param Traversable<array{
     *   versionFormatted: string,
     *   version: int,
     *   migration: MigrationInterface
     * }> $updates Updates to perform
     *
     * @throws UpdateFailedException
     */
    public function run(Traversable $updates, ?Interactor $interactor = null): void;

    /**
     * Checks the db for the existence of tables provided by the given updates
     *
     * @param Traversable<array{
     *  versionFormatted: string,
     *  version: int,
     *  migration: MigrationInterface
     * }> $updates Updates to perform
     *
     * @return Iterator<string>
     *
     * @throws UpdateFailedException
     */
    public function runTableCheck(
        Traversable $updates,
        bool $migrate = false
    ): Iterator;
}
