<?php

declare(strict_types=1);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2023
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
use Ampache\Config\ConfigContainerInterface;
use Ampache\Module\Database\DatabaseConnectionInterface;
use Ampache\Module\Database\Exception\DatabaseException;
use Ampache\Module\System\LegacyLogger;
use Ampache\Module\System\Update\Exception\UpdateFailedException;
use Ampache\Module\System\Update\Migration\MigrationInterface;
use Ampache\Repository\Model\Preference;
use Ampache\Repository\Model\UpdateInfoEnum;
use Ampache\Repository\Model\User;
use Ampache\Repository\UpdateInfoRepositoryInterface;
use Generator;
use Psr\Log\LoggerInterface;
use Throwable;
use Traversable;

/**
 * Performs the actual update process
 */
final class UpdateRunner implements UpdateRunnerInterface
{
    private DatabaseConnectionInterface $connection;

    private LoggerInterface $logger;

    private UpdateInfoRepositoryInterface $updateInfoRepository;

    private ConfigContainerInterface $configContainer;

    public function __construct(
        DatabaseConnectionInterface $connection,
        LoggerInterface $logger,
        UpdateInfoRepositoryInterface $updateInfoRepository,
        ConfigContainerInterface $configContainer
    ) {
        $this->connection           = $connection;
        $this->logger               = $logger;
        $this->updateInfoRepository = $updateInfoRepository;
        $this->configContainer      = $configContainer;
    }

    /**
     * Runs the migrations with are determined by the given updates
     *
     * @param Traversable<array{
     *  versionFormatted: string,
     *  version: int,
     *  migration: MigrationInterface
     * }> $updates Updates to perform
     *
     * @throws UpdateFailedException
     */
    public function run(
        Traversable $updates,
        ?Interactor $interactor = null
    ): void {
        $this->logger->notice(
            'Migration starting',
            [LegacyLogger::CONTEXT_TYPE => self::class]
        );

        /* Nuke All Active session before we start the mojo */
        $this->connection->query('TRUNCATE session');

        // Prevent the script from timing out, which could be bad
        set_time_limit(0);

        foreach ($updates as $update) {
            $migration = $update['migration'];
            if ($interactor !== null) {
                $interactor->info(
                    get_class($migration),
                    true
                );
            }

            $migration->setInteractor($interactor);

            try {
                $migration->migrate();
            } catch (Throwable $e) {
                throw new UpdateFailedException();
            }

            $this->logger->notice(
                sprintf('Successfully applied update %s', $update['versionFormatted']),
                [LegacyLogger::CONTEXT_TYPE => self::class]
            );

            // set the new version
            $this->updateInfoRepository->setValue(
                UpdateInfoEnum::DB_VERSION,
                (string) $update['version']
            );
        }

        // Let's also clean up the preferences unconditionally
        $this->logger->notice(
            'Rebuild preferences',
            [LegacyLogger::CONTEXT_TYPE => self::class]
        );

        User::rebuild_all_preferences();

        // translate preferences on DB update
        Preference::translate_db();

        $this->logger->notice(
            'Migration complete',
            [LegacyLogger::CONTEXT_TYPE => self::class]
        );
    }

    /**
     * Checks the db for the existence of tables provided by the given updates
     *
     * @param Traversable<array{
     *  versionFormatted: string,
     *  version: int,
     *  migration: MigrationInterface
     * }> $updates Update to perform
     *
     * @return Generator<string>
     *
     * @throws UpdateFailedException
     */
    public function runTableCheck(
        Traversable $updates,
        bool $migrate = false
    ): Generator {
        $collation = $this->configContainer->get('database_collation') ?? 'utf8mb4_unicode_ci';
        $charset   = $this->configContainer->get('database_charset') ?? 'utf8mb4';
        $engine    = 'InnoDB';

        foreach ($updates as $update) {
            $tableMigrations = $update['migration']->getTableMigrations($collation, $charset, $engine);

            foreach ($tableMigrations as $tableName => $migrationSql) {
                try {
                    $this->connection->query(sprintf('DESCRIBE `%s`', $tableName));

                    continue;
                } catch (DatabaseException $e) {
                    $this->logger->warning(
                        'Missing table: ' . $tableName,
                        [
                            LegacyLogger::CONTEXT_TYPE => self::class
                        ]
                    );

                    if (!$migrate) {
                        yield $tableName;

                        continue;
                    }
                }

                try {
                    $this->connection->query($migrationSql);
                } catch (DatabaseException $e) {
                    $error = sprintf('Failed creating missing table: %s', $tableName);

                    $this->logger->critical(
                        $error,
                        [
                            LegacyLogger::CONTEXT_TYPE => self::class
                        ]
                    );

                    throw new UpdateFailedException($error);
                }

                $this->logger->critical(
                    sprintf('Created missing table: %s', $tableName),
                    [
                        LegacyLogger::CONTEXT_TYPE => self::class
                    ]
                );

                yield $tableName;
            }
        }
    }
}
