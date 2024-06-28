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
use Ampache\Module\System\Update\Exception\UpdateException;
use Ampache\Module\System\Update\Exception\UpdateFailedException;
use Ampache\Module\System\Update\Exception\VersionNotUpdatableException;
use Ampache\Module\System\Update\Migration\MigrationInterface;
use Ampache\Repository\Model\UpdateInfoEnum;
use Ampache\Repository\UpdateInfoRepositoryInterface;
use Generator;
use Psr\Container\ContainerInterface;

/**
 * Provides several utility methods to perform updates
 */
final class Updater implements UpdaterInterface
{
    private const MINIMUM_UPDATABLE_VERSION = 350008;

    private UpdateHelperInterface $updateHelper;

    private UpdateInfoRepositoryInterface $updateInfoRepository;

    private ContainerInterface $dic;

    private UpdateRunnerInterface $updateRunner;

    public function __construct(
        UpdateHelperInterface $updateHelper,
        UpdateInfoRepositoryInterface $updateInfoRepository,
        ContainerInterface $dic,
        UpdateRunnerInterface $updateRunner
    ) {
        $this->updateHelper         = $updateHelper;
        $this->updateInfoRepository = $updateInfoRepository;
        $this->dic                  = $dic;
        $this->updateRunner         = $updateRunner;
    }

    /**
     * This yields a list of the needed updates to the database
     *
     * @return Generator<array{
     *  versionFormatted: string,
     *  version: int,
     *  migration: MigrationInterface
     * }>
     */
    public function getPendingUpdates(): Generator
    {
        $availableMigrations = Versions::getPendingMigrations(
            (int) $this->updateInfoRepository->getValueByKey(UpdateInfoEnum::DB_VERSION)
        );

        foreach ($availableMigrations as $version => $migrationClass) {
            /** @var MigrationInterface $migration */
            $migration = $this->dic->get($migrationClass);

            yield [
                'versionFormatted' => $this->updateHelper->formatVersion((string) $version),
                'version' => $version,
                'migration' => $migration
            ];
        }
    }

    /**
     * Checks to see if we need to update Ampache at all
     */
    public function hasPendingUpdates(): bool
    {
        return Versions::getPendingMigrations(
            (int) $this->updateInfoRepository->getValueByKey(UpdateInfoEnum::DB_VERSION)
        )->valid();
    }

    /**
     * Performs update migrations
     *
     * @throws UpdateException
     */
    public function update(
        ?Interactor $interactor = null
    ): void {
        $currentVersion = (int) $this->updateInfoRepository->getValueByKey(UpdateInfoEnum::DB_VERSION);

        // Run a check to make sure that they don't try to upgrade from a version that won't work.
        if ($currentVersion < self::MINIMUM_UPDATABLE_VERSION) {
            throw new VersionNotUpdatableException();
        }

        $this->updateRunner->run(
            $this->getPendingUpdates(),
            $interactor
        );
    }

    /**
     * Checks for missing database tables
     *
     * @param bool $migrate Set to `true` if the system should try to create the missing tables
     *
     * @return Generator<string> The names of the missing database tables
     *
     * @throws UpdateFailedException
     */
    public function checkTables(bool $migrate = false): Generator
    {
        yield from $this->updateRunner->runTableCheck(
            $this->getPendingUpdates(),
            $migrate
        );
    }
}
