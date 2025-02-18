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
use Ampache\Config\ConfigContainerInterface;
use Ampache\Module\Database\DatabaseConnectionInterface;
use Ampache\Module\Database\Exception\DatabaseException;
use Ampache\Module\System\Dba;
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
     * Run the rollback queries on the database
     *
     * @throws UpdateFailedException
     */
    public function runRollback(
        int $currentVersion,
        ?Interactor $interactor = null
    ): void {
        $this->logger->notice(
            'Downgrade starting',
            [LegacyLogger::CONTEXT_TYPE => self::class]
        );

        /* Nuke All Active session before we start the mojo */
        $this->connection->query('TRUNCATE session');

        // Prevent the script from timing out, which could be bad
        set_time_limit(0);


        if ($currentVersion >= 721001) {
            // Migration\V7\Migration721001
            if (
                !Preference::delete('show_playlist_media_parent')
            ) {
                throw new UpdateFailedException();
            }
        }

        if ($currentVersion >= 720001) {
            // Migration\V7\Migration720001
            if (
                Dba::read('SELECT `artist` FROM `tag` LIMIT 1;') &&
                !Dba::write("ALTER TABLE `tag` DROP COLUMN `artist`;")
            ) {
                throw new UpdateFailedException();
            }
            if (
                Dba::read('SELECT `album` FROM `tag` LIMIT 1;') &&
                !Dba::write("ALTER TABLE `tag` DROP COLUMN `album`;")
            ) {
                throw new UpdateFailedException();
            }
            if (
                Dba::read('SELECT `song` FROM `tag` LIMIT 1;') &&
                !Dba::write("ALTER TABLE `tag` DROP COLUMN `song`;")
            ) {
                throw new UpdateFailedException();
            }
            if (
                Dba::read('SELECT `video` FROM `tag` LIMIT 1;') &&
                !Dba::write("ALTER TABLE `tag` DROP COLUMN `video`;")
            ) {
                throw new UpdateFailedException();
            }
        }

        if ($currentVersion >= 710006) {
            // Migration\V7\Migration710006
            if (
                !Preference::delete('external_links_discogs') ||
                !Preference::delete('browse_song_grid_view') ||
                !Preference::delete('browse_album_grid_view') ||
                !Preference::delete('browse_album_disk_grid_view') ||
                !Preference::delete('browse_artist_grid_view') ||
                !Preference::delete('browse_live_stream_grid_view') ||
                !Preference::delete('browse_playlist_grid_view') ||
                !Preference::delete('browse_video_grid_view') ||
                !Preference::delete('browse_podcast_grid_view') ||
                !Preference::delete('browse_podcast_episode_grid_view')
            ) {
                throw new UpdateFailedException();
            }
        }

        if ($currentVersion >= 710005) {
            // Migration\V7\Migration710005
            Dba::write("ALTER TABLE `song` DROP KEY `album_disk_IDX`;");
            if (
                Dba::read('SELECT `album_disk` FROM `song` LIMIT 1;') &&
                !Dba::write("ALTER TABLE `song` DROP COLUMN `album_disk`;")
            ) {
                throw new UpdateFailedException();
            }
        }

        if ($currentVersion >= 710004) {
            // Migration\V7\Migration710004
            if (
                Dba::read('SELECT `total_skip` FROM `album` LIMIT 1;') &&
                !Dba::write("ALTER TABLE `album` DROP COLUMN `total_skip`;")
            ) {
                throw new UpdateFailedException();
            }
            if (
                Dba::read('SELECT `total_skip` FROM `album_disk` LIMIT 1;') &&
                !Dba::write("ALTER TABLE `album_disk` DROP COLUMN `total_skip`;")
            ) {
                throw new UpdateFailedException();
            }
            if (
                Dba::read('SELECT `total_skip` FROM `artist` LIMIT 1;') &&
                !Dba::write("ALTER TABLE `artist` DROP COLUMN `total_skip`;")
            ) {
                throw new UpdateFailedException();
            }
        }

        if ($currentVersion >= 710001) {
            // Migration\V7\Migration710001
            if (
                Dba::read('SELECT `addition_time` FROM `artist` LIMIT 1;') &&
                !Dba::write("ALTER TABLE `artist` DROP COLUMN `addition_time`;")
            ) {
                throw new UpdateFailedException();
            }
        }

        if ($currentVersion >= 702002) {
            // Migration\V7\Migration702002
            if (!Preference::delete('external_links_discogs')) {
                throw new UpdateFailedException();
            }
        }

        if ($currentVersion >= 700029) {
            // Migration\V7\Migration700028
            if (!Dba::write("ALTER TABLE `user_activity` MODIFY COLUMN `object_type` enum('album','album_disk','artist','catalog','tag','label','live_stream','playlist','podcast','podcast_episode','search','song','tvshow','tvshow_season','user','video') CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL;")) {
                throw new UpdateFailedException();
            }
        }

        if ($currentVersion >= 700028) {
            // Migration\V7\Migration700028
            if (!Dba::write("ALTER TABLE `rating` MODIFY COLUMN `object_type` enum('album','album_disk','artist','catalog','tag','label','live_stream','playlist','podcast','podcast_episode','search','song','tvshow','tvshow_season','user','video') CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL;")) {
                throw new UpdateFailedException();
            }
        }

        if ($currentVersion >= 700027) {
            // Migration\V7\Migration700027
            if (!Dba::write("ALTER TABLE `now_playing` MODIFY COLUMN `object_type` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL;")) {
                throw new UpdateFailedException();
            }
        }

        if ($currentVersion >= 700026) {
            // Migration\V7\Migration700026
            if (!Dba::write("ALTER TABLE `image` MODIFY COLUMN `object_type` enum('album','album_disk','artist','catalog','tag','label','live_stream','playlist','podcast','podcast_episode','search','song','tvshow','tvshow_season','user','video') CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL;")) {
                throw new UpdateFailedException();
            }
        }

        if ($currentVersion >= 700025) {
            // Migration\V7\Migration700025
            if (!Dba::write("ALTER TABLE `user_flag` MODIFY COLUMN `object_type` enum('album','album_disk','artist','catalog','tag','label','live_stream','playlist','podcast','podcast_episode','search','song','tvshow','tvshow_season','user','video') CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL;")) {
                throw new UpdateFailedException();
            }
        }

        if ($currentVersion >= 700024) {
            // Migration\V7\Migration700024
            if (!Dba::write("ALTER TABLE `cache_object_count` MODIFY COLUMN `object_type` enum('album','album_disk','artist','catalog','tag','label','live_stream','playlist','podcast','podcast_episode','search','song','tvshow','tvshow_season','user','video') CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL;")) {
                throw new UpdateFailedException();
            }
            if (!Dba::write("ALTER TABLE `cache_object_count_run` MODIFY COLUMN `object_type` enum('album','album_disk','artist','catalog','tag','label','live_stream','playlist','podcast','podcast_episode','search','song','tvshow','tvshow_season','user','video') CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL;")) {
                throw new UpdateFailedException();
            }
        }

        if ($currentVersion >= 700023) {
            // Migration\V7\Migration700023
            if (!Preference::delete('extended_playlist_links')) {
                throw new UpdateFailedException();
            }
        }

        if ($currentVersion >= 700022) {
            // Migration\V7\Migration700022
            if (
                !Preference::delete('external_links_google') ||
                !Preference::delete('external_links_duckduckgo') ||
                !Preference::delete('external_links_wikipedia') ||
                !Preference::delete('external_links_lastfm') ||
                !Preference::delete('external_links_bandcamp') ||
                !Preference::delete('external_links_musicbrainz')
            ) {
                throw new UpdateFailedException();
            }
        }

        if ($currentVersion >= 700021) {
            // Migration\V7\Migration700021
            if (
                Dba::read('SELECT `order` FROM `license` LIMIT 1;') &&
                !Dba::write("ALTER TABLE `license` DROP COLUMN `order`;")
            ) {
                throw new UpdateFailedException();
            }
        }

        if ($currentVersion >= 700020) {
            // Migration\V7\Migration700020
            Dba::write("ALTER TABLE `user_preference` DROP KEY `unique_name`;");
        }

        if ($currentVersion >= 700019) {
            // Migration\V7\Migration700019
            if (
                !Preference::delete('api_always_download')
            ) {
                throw new UpdateFailedException();
            }
        }

        if ($currentVersion >= 700016) {
            // Migration\V7\Migration700016
            if (
                !Preference::delete('sidebar_order_browse') ||
                !Preference::delete('sidebar_order_dashboard') ||
                !Preference::delete('sidebar_order_video') ||
                !Preference::delete('sidebar_order_playlist') ||
                !Preference::delete('sidebar_order_search') ||
                !Preference::delete('sidebar_order_information')
            ) {
                throw new UpdateFailedException();
            }
        }

        if ($currentVersion >= 700015) {
            // Migration\V7\Migration700015
            if (
                !Preference::delete('index_dashboard_form')
            ) {
                throw new UpdateFailedException();
            }
        }

        if ($currentVersion >= 700014) {
            // Migration\V7\Migration700005
            if (
                Dba::read('SELECT `name` FROM `user_preference` LIMIT 1;') &&
                !Dba::write("ALTER TABLE `user_preference` DROP COLUMN `name`;")
            ) {
                throw new UpdateFailedException();
            }
        }

        if ($currentVersion >= 700012) {
            // Migration\V7\Migration700012
            if (
                !Preference::delete('custom_logo_user')
            ) {
                throw new UpdateFailedException();
            }
        }

        if ($currentVersion >= 700006) {
            // Migration\V7\Migration700006
            if (!Preference::insert('home_recently_played_all', 'Show all media types in Recently Played', '1', 25, 'bool', 'interface', 'home', true)) {
                throw new UpdateFailedException();
            }
        }

        if ($currentVersion >= 700005) {
            // Migration\V7\Migration700005
            if (
                Dba::read('SELECT `last_count` FROM `playlist` LIMIT 1;') &&
                !Dba::write("ALTER TABLE `playlist` DROP COLUMN `last_count`;")
            ) {
                throw new UpdateFailedException();
            }
        }

        if ($currentVersion >= 700001) {
            // Migration\V7\Migration700001
            if (
                !Preference::delete('sidebar_hide_switcher') ||
                !Preference::delete('sidebar_hide_browse') ||
                !Preference::delete('sidebar_hide_dashboard') ||
                !Preference::delete('sidebar_hide_video') ||
                !Preference::delete('sidebar_hide_search') ||
                !Preference::delete('sidebar_hide_playlist') ||
                !Preference::delete('sidebar_hide_information')
            ) {
                throw new UpdateFailedException();
            }
        }

        $this->logger->notice(
            sprintf('Successful rollback to update %s', (string)Versions::MAXIMUM_UPDATABLE_VERSION),
            [LegacyLogger::CONTEXT_TYPE => self::class]
        );

        // set the new version
        $this->updateInfoRepository->setValue(
            UpdateInfoEnum::DB_VERSION,
            (string)Versions::MAXIMUM_UPDATABLE_VERSION
        );

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
        $engine    = ($charset === 'utf8mb4') ? 'InnoDB' : 'MYISAM';

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
