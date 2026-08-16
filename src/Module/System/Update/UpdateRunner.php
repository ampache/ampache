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
 */

namespace Ampache\Module\System\Update;

use Ahc\Cli\IO\Interactor;
use Ampache\Config\ConfigContainerInterface;
use Ampache\Module\Authorization\AccessLevelEnum;
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

        // Migration\V8\Migration801003 needs no rollback. It raises `headphones_api_url`/`headphones_api_key` to
        // Manager level; a downgrade should keep that restriction rather than reopen the access-level hole it fixed.

        // Migration\V8\Migration801002 needs no rollback. It collapses `stream_beautiful_url`'s per-admin
        // `user_preference` rows into the single `user = -1` row and reclassifies it under category `system`.

        // Migration\V8\Migration801001 needs no rollback. It drops 24 indexes that repeat the leading columns of a
        // wider key, so every column stays indexed and Ampache7 keeps the same access paths without them.

        // Migration\V8\Migration800050 needs no rollback. It drops two `user_preference` indexes that `unique_name`
        // already covers, so Ampache7 reaches those rows by the same access paths without them.

        // Migration\V8\Migration800049 needs no rollback. It widens the `tag_map` and `user_activity` `object_type`
        // enums, and Ampache7 writes neither the broadcast genre nor a folder activity that the extra values name.

        if ($currentVersion >= 800048) {
            // Migration\V8\Migration800048 (Ampache7 has no mood column in its browse rows)
            if (!Preference::delete('hide_moods')) {
                throw new UpdateFailedException();
            }
        }

        if ($currentVersion >= 800047) {
            // Migration\V8\Migration800047 (Ampache7 has no moods interface)
            if (!Preference::delete('show_mood')) {
                throw new UpdateFailedException();
            }
        }

        // Migration\V8\Migration800042 to Migration800046 need no rollback. They widen `song_preview`.`file`, add
        // `wanted` to the `image` `object_type` enum, add indexes, create the `playlist_folder` tables and add
        // `song_data`.`bpm`. Ampache7 reads none of them, and a wider column or an unused table costs it nothing.

        if ($currentVersion >= 800041) {
            // Migration\V8\Migration800041 (Ampache7 broadcasts always require a listener session)
            if (!Preference::delete('broadcast_private')) {
                throw new UpdateFailedException();
            }
        }

        // Migration\V8\Migration800040 needs no rollback. It removed the 7digital preferences along with the
        // plugin's `update_info` version row, so Ampache7 already shows the plugin as inactive; reactivating it
        // runs install() and writes the preferences back. The keys they held went with the `user_preference`
        // rows and cannot be recovered either way.

        // Migration\V8\Migration800035 to Migration800039 need no rollback. They drop a key on the Ampache8-only
        // `collection_map`, repair columns missing from a stale seed, index `addition_time`, backfill `podcast`
        // rows in `object_count` and delete `folder` rows holding a bare directory name. Ampache7 reads none of
        // the Ampache8-only tables, and the repairs and counts are correct for it too.

        if ($currentVersion >= 800034) {
            // Migration\V8\Migration800034 (Ampache7's `label_asso` links a label to an artist only)
            // Its `artist` column is NOT NULL here, so the album-only rows Ampache8 writes have to go before the
            // column can be narrowed again; the labels themselves are kept, only the album association is lost
            if (
                !Dba::write('DELETE FROM `label_asso` WHERE `artist` IS NULL;') ||
                !Dba::write('ALTER TABLE `label_asso` MODIFY COLUMN `artist` int(11) UNSIGNED NOT NULL;') ||
                !Dba::write('ALTER TABLE `label_asso` DROP COLUMN `album`;')
            ) {
                throw new UpdateFailedException();
            }
        }

        // Migration\V8\Migration800033 needs no rollback. It added `artist`.`lastfm_url`, which Ampache7 never
        // reads, so a downgraded database keeps an unused nullable column. Dropping it would discard cached
        // last.fm data that only another round of API calls could rebuild.

        // Migration\V8\Migration800032 needs no rollback. It added `position_ms`, `playback_rate` and `state` to
        // `now_playing`; Ampache7 inserts that row with an explicit column list, so the extra nullable columns are
        // ignored, and the table holds only ephemeral session state that expires on its own anyway.

        // Migration\V8\Migration800031 needs no rollback block of its own. It added `collection` to the
        // `object_type` enum on `rating` and `user_flag`, and the `>= 800004` block below already narrows both
        // back to the Ampache7 spelling and deletes the rows that no longer fit -- the same way that block
        // covers the enums Migration800028 widened.

        if ($currentVersion >= 800030) {
            // Migration\V8\Migration800030 (Ampache7 has no Collections sidebar link for the preference to gate)
            if (!Preference::delete('show_collection')) {
                throw new UpdateFailedException();
            }
        }

        // Migration\V8\Migration800029 needs no rollback. It added a maintained `last_played` column to the
        // tables carrying a play counter. Ampache7 never reads it, so a downgraded database keeps an unused
        // nullable column, and dropping it would only throw away a value the migration rebuilds from
        // `object_count` when the database is upgraded again.

        // Migration\V8\Migration800028 needs no rollback. It created the `collection` and `collection_map`
        // tables, which Ampache7 never reads; dropping them would destroy hand-curated lists to undo a change
        // that costs the older version nothing, and the Ampache8 migration re-creates them with IF NOT EXISTS
        // so the contents survive a downgrade/upgrade cycle. Its other half widened the `object_type` enums to
        // accept `collection`, and the `>= 800004` block below already narrows every one of those enums back
        // to the Ampache7 spelling and deletes the rows that no longer fit.

        if ($currentVersion >= 800027) {
            // Migration\V8\Migration800027 (Ampache7 has no per-player bitrate overrides)
            // `transcode_bitrate_formats`, which that migration deleted, was itself Ampache8-only, so there is
            // nothing to put back -- the `>= 800019` block below deletes it for databases that still carry it
            if (
                !Preference::delete('transcode_bitrate_webplayer') ||
                !Preference::delete('transcode_bitrate_api')
            ) {
                throw new UpdateFailedException();
            }
        }

        // Migration\V8\Migration800026 needs no rollback. It only relabelled the preference
        // `disabled_custom_metadata_fields_input`, and Preference::translate_db() -- which this method runs
        // unconditionally at the end -- puts Ampache7's own wording back without any help from here.

        // Migration\V8\Migration800025 needs no rollback. It added the `user`.`subsonic_secret` column, which
        // Ampache7 never reads; dropping it would discard every Subsonic password the user had set, and they
        // cannot be regenerated because the column holds a secret of the user's own choosing.

        if ($currentVersion >= 800024) {
            // Migration\V8\Migration800024 (Ampache7's cached play counts have no live-count option)
            if (!Preference::delete('cron_cache_live_count')) {
                throw new UpdateFailedException();
            }
        }

        // Migration\V8\Migration800023 needs no rollback. It corrected art mime types that had been
        // built from the uploaded filename (`image/jpg`, which is not a registered type, and
        // `image/JPG` for an upper case name) to the type read from the image data itself. Ampache7
        // reads the stored value and serves it as the Content-Type, and `image/jpeg` is exactly what
        // its own art gathering writes, so restoring the old values would only reintroduce the bug.

        if ($currentVersion >= 800022) {
            // Migration\V8\Migration800022 (restore the preference deleted by the migration)
            // Ampache7 still gates the embedded web player on this preference -- without it playback
            // falls back to the popup window -- along with autoplay next/append and the SSE catalog worker
            if (!Preference::insert('ajax_load', 'Ajax page load', '1', AccessLevelEnum::USER->value, 'boolean', 'interface')) {
                throw new UpdateFailedException();
            }
        }

        if ($currentVersion >= 800021) {
            // Migration\V8\Migration800021 (Ampache7 has no mini player interface)
            if (!Preference::delete('mini_player')) {
                throw new UpdateFailedException();
            }
        }

        if ($currentVersion >= 800020) {
            // Migration\V8\Migration800020 (restore the preference deleted by the migration)
            // Ampache7's web player is gated on this preference; without it jPlayer gets no solution at all
            if (!Preference::insert('webplayer_html5', 'Authorize HTML5 Web Player', '1', AccessLevelEnum::USER->value, 'boolean', 'streaming', 'player')) {
                throw new UpdateFailedException();
            }
        }

        if ($currentVersion >= 800018) {
            // Migration\V8\Migration800018 (Ampache7 stores `transcode_bitrate` in kilobits, not bits)
            if (
                !Dba::write("UPDATE `user_preference` SET `value` = GREATEST(CAST(`value` AS UNSIGNED) DIV 1000, 1) WHERE `name` = 'transcode_bitrate' AND CAST(`value` AS UNSIGNED) >= 1000;") ||
                !Dba::write("UPDATE `preference` SET `value` = '128', `type` = 'string', `description` = 'Transcode Bitrate' WHERE `name` = 'transcode_bitrate';")
            ) {
                throw new UpdateFailedException();
            }
        }

        if ($currentVersion >= 800017) {
            // Migration\V8\Migration800017 (Ampache7 reads these from ampache.cfg.php, not preferences)
            if (
                !Preference::delete('max_bit_rate') ||
                !Preference::delete('min_bit_rate')
            ) {
                throw new UpdateFailedException();
            }
        }

        if ($currentVersion >= 800016) {
            // Migration\V8\Migration800016 (Ampache7 reads these from ampache.cfg.php, not preferences)
            if (
                !Preference::delete('encode_target') ||
                !Preference::delete('encode_video_target') ||
                !Preference::delete('encode_player_webplayer_target') ||
                !Preference::delete('encode_player_api_target')
            ) {
                throw new UpdateFailedException();
            }
        }

        if ($currentVersion >= 800015) {
            // Migration\V8\Migration800015 (put the consolidated play history back before dropping the archive)
            // Ampache7 only reads `object_count`, so anything left in the archive would be invisible. The derived
            // album/artist rows consolidation removed are rebuilt by Catalog::update_counts() on the next run.
            if (
                !Dba::write("INSERT IGNORE INTO `object_count` (`object_type`, `object_id`, `count_type`, `date`, `user`, `agent`, `geo_latitude`, `geo_longitude`, `geo_name`) SELECT `object_type`, `object_id`, `count_type`, `date`, `user`, `agent`, `geo_latitude`, `geo_longitude`, `geo_name` FROM `object_count_archive`;") ||
                !Dba::write("DROP TABLE IF EXISTS `object_count_archive`;")
            ) {
                throw new UpdateFailedException();
            }
        }

        if ($currentVersion >= 800014) {
            // Migration\V8\Migration800014 (restore the indexes the migration dropped as redundant)
            Dba::write("ALTER TABLE `object_count` ADD KEY `object_count_full_index` (`object_type`, `object_id`, `date`, `user`, `agent`, `count_type`) USING BTREE;", [], true);
            Dba::write("ALTER TABLE `object_count` ADD KEY `object_type` (`object_type`);", [], true);
            Dba::write("ALTER TABLE `object_count` ADD KEY `object_count_type_IDX` (`object_type`, `object_id`) USING BTREE;", [], true);
            Dba::write("ALTER TABLE `object_count` ADD KEY `date` (`date`);", [], true);
        }

        if ($currentVersion >= 800013) {
            // Migration\V8\Migration800013 (the detail was restored by the 800015 block above)
            if (!Dba::write("DROP TABLE IF EXISTS `object_count_summary`;")) {
                throw new UpdateFailedException();
            }
        }

        if ($currentVersion >= 800012) {
            // Migration\V8\Migration800012 (Ampache7 has no system user, hand the share.php plays back to user 0).
            // The `user` columns are left signed: Ampache7's own schema already declares them int(11) signed
            if (!Dba::write("UPDATE IGNORE `object_count` SET `user` = 0 WHERE `user` = -1 AND `agent` = 'share.php';")) {
                throw new UpdateFailedException();
            }
        }

        if ($currentVersion >= 800011) {
            // Migration\V8\Migration800011 (restore the preferences deleted by the migration)
            if (
                !Preference::insert('webplayer_flash', 'Authorize Flash Web Player', '1', AccessLevelEnum::USER->value, 'boolean', 'streaming', 'player') ||
                !Preference::insert('webplayer_aurora', 'Authorize JavaScript decoder (Aurora.js) in Web Player', '1', AccessLevelEnum::USER->value, 'boolean', 'streaming', 'player') ||
                !Preference::insert('use_play2', 'Use an alternative playback action for streaming if you have issues with playing music', '0', AccessLevelEnum::USER->value, 'boolean', 'streaming', 'player')
            ) {
                throw new UpdateFailedException();
            }
        }

        if ($currentVersion >= 800010) {
            // Migration\V8\Migration800010 (drop the table that migration creates)
            if (
                !Dba::write("DROP TABLE IF EXISTS `folder_map`;")
            ) {
                throw new UpdateFailedException();
            }
        }

        // Migration\V8\Migration800009 needs no rollback of its own. It added `folder`.`weight`, and the
        // `>= 800007` block below drops the whole `folder` table, taking the column with it.

        if ($currentVersion >= 800007) {
            // Migration\V8\Migration800007
            if (
                !Dba::write("DROP TABLE IF EXISTS `folder`;")
            ) {
                throw new UpdateFailedException();
            }
        }

        if ($currentVersion >= 800006) {
            // Migration\V8\Migration800006
            if (!Preference::delete('show_folder')) {
                throw new UpdateFailedException();
            }
        }

        if ($currentVersion >= 800004) {
            // Migration\V8\Migration800004
            if (
                !Dba::write('DELETE FROM `cache_object_count` WHERE `object_type` IS NULL OR `object_type` NOT IN (\'album\', \'album_disk\', \'artist\', \'catalog\', \'tag\', \'label\', \'live_stream\', \'playlist\', \'podcast\', \'podcast_episode\', \'search\', \'song\', \'user\', \'video\');') ||
                !Dba::write('ALTER TABLE `cache_object_count` MODIFY COLUMN `object_type` enum(\'album\', \'album_disk\', \'artist\', \'catalog\', \'tag\', \'label\', \'live_stream\', \'playlist\', \'podcast\', \'podcast_episode\', \'search\', \'song\', \'tvshow\', \'tvshow_season\', \'user\', \'video\') CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL;') ||
                !Dba::write('DELETE FROM `cache_object_count_run` WHERE `object_type` IS NULL OR `object_type` NOT IN (\'album\', \'album_disk\', \'artist\', \'catalog\', \'tag\', \'label\', \'live_stream\', \'playlist\', \'podcast\', \'podcast_episode\', \'search\', \'song\', \'user\', \'video\');') ||
                !Dba::write('ALTER TABLE `cache_object_count_run` MODIFY COLUMN `object_type` enum(\'album\', \'album_disk\', \'artist\', \'catalog\', \'tag\', \'label\', \'live_stream\', \'playlist\', \'podcast\', \'podcast_episode\', \'search\', \'song\', \'tvshow\', \'tvshow_season\', \'user\', \'video\') CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL;') ||
                !Dba::write('DELETE FROM `image` WHERE `object_type` IS NULL OR `object_type` NOT IN (\'album\', \'album_disk\', \'artist\', \'catalog\', \'tag\', \'label\', \'live_stream\', \'playlist\', \'podcast\', \'podcast_episode\', \'search\', \'song\', \'user\', \'video\');') ||
                !Dba::write('ALTER TABLE `image` MODIFY COLUMN `object_type` enum(\'album\', \'album_disk\', \'artist\', \'catalog\', \'tag\', \'label\', \'live_stream\', \'playlist\', \'podcast\', \'podcast_episode\', \'search\', \'song\', \'tvshow\', \'tvshow_season\', \'user\', \'video\') CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL;') ||
                !Dba::write('DELETE FROM `object_count` WHERE `object_type` IS NULL OR `object_type` NOT IN (\'album\', \'album_disk\', \'artist\', \'catalog\', \'tag\', \'label\', \'live_stream\', \'playlist\', \'podcast\', \'podcast_episode\', \'search\', \'song\', \'user\', \'video\');') ||
                !Dba::write('ALTER TABLE `object_count` MODIFY COLUMN `object_type` enum(\'album\', \'album_disk\', \'artist\', \'catalog\', \'tag\', \'label\', \'live_stream\', \'playlist\', \'podcast\', \'podcast_episode\', \'search\', \'song\', \'tvshow\', \'tvshow_season\', \'user\', \'video\') CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL;') ||
                !Dba::write('DELETE FROM `rating` WHERE `object_type` IS NULL OR `object_type` NOT IN (\'album\', \'album_disk\', \'artist\', \'catalog\', \'tag\', \'label\', \'live_stream\', \'playlist\', \'podcast\', \'podcast_episode\', \'search\', \'song\', \'user\', \'video\');') ||
                !Dba::write('ALTER TABLE `rating` MODIFY COLUMN `object_type` enum(\'album\', \'album_disk\', \'artist\', \'catalog\', \'tag\', \'label\', \'live_stream\', \'playlist\', \'podcast\', \'podcast_episode\', \'search\', \'song\', \'tvshow\', \'tvshow_season\', \'user\', \'video\') CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL;') ||
                !Dba::write('DELETE FROM `tag_map` WHERE `object_type` IS NULL OR `object_type` NOT IN (\'album\', \'album_disk\', \'artist\', \'catalog\', \'tag\', \'label\', \'live_stream\', \'playlist\', \'podcast\', \'podcast_episode\', \'search\', \'song\', \'user\', \'video\');') ||
                !Dba::write('ALTER TABLE `tag_map` MODIFY COLUMN `object_type` enum(\'album\', \'album_disk\', \'artist\', \'catalog\', \'tag\', \'label\', \'live_stream\', \'playlist\', \'podcast\', \'podcast_episode\', \'search\', \'song\', \'tvshow\', \'tvshow_season\', \'user\', \'video\') CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL;') ||
                !Dba::write('DELETE FROM `user_activity` WHERE `object_type` IS NULL OR `object_type` NOT IN (\'album\', \'album_disk\', \'artist\', \'catalog\', \'tag\', \'label\', \'live_stream\', \'playlist\', \'podcast\', \'podcast_episode\', \'search\', \'song\', \'user\', \'video\');') ||
                !Dba::write('ALTER TABLE `user_activity` MODIFY COLUMN `object_type` enum(\'album\', \'album_disk\', \'artist\', \'catalog\', \'tag\', \'label\', \'live_stream\', \'playlist\', \'podcast\', \'podcast_episode\', \'search\', \'song\', \'tvshow\', \'tvshow_season\', \'user\', \'video\') CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL;') ||
                !Dba::write('DELETE FROM `user_flag` WHERE `object_type` IS NULL OR `object_type` NOT IN (\'album\', \'album_disk\', \'artist\', \'catalog\', \'tag\', \'label\', \'live_stream\', \'playlist\', \'podcast\', \'podcast_episode\', \'search\', \'song\', \'user\', \'video\');') ||
                !Dba::write('ALTER TABLE `user_flag` MODIFY COLUMN `object_type` enum(\'album\', \'album_disk\', \'artist\', \'catalog\', \'tag\', \'label\', \'live_stream\', \'playlist\', \'podcast\', \'podcast_episode\', \'search\', \'song\', \'tvshow\', \'tvshow_season\', \'user\', \'video\') CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL;')
            ) {
                throw new UpdateFailedException();
            }
        }

        // Migration\V8\Migration800005 needs no rollback. It replaced a `direct_play_limit` of 0 (unlimited)
        // with 500. Ampache7 reads the same preference and honours the same meaning, the per-user values it
        // overwrote are not recoverable, and restoring 0 would only reintroduce the multi-thousand-track
        // direct play the migration exists to stop.

        // Migration\V8\Migration800001 needs no rollback. It switched every user off `subsonic_legacy`, which
        // Ampache7 also serves both sides of, so the value stays meaningful there. The individual values it
        // overwrote are gone either way -- the migration recorded no before-state to restore.

        if ($currentVersion >= 800000) {
            // Migration\V8\Migration800000
            if (!Preference::delete('api_enable_8')) {
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
     *     versionFormatted: string,
     *     version: int,
     *     migration: MigrationInterface
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
            $interactor?->info(
                get_class($migration),
                true
            );

            $migration->setInteractor($interactor);

            try {
                $migration->migrate();
            } catch (Throwable) {
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
     *     versionFormatted: string,
     *     version: int,
     *     migration: MigrationInterface
     * }> $updates Update to perform
     *
     * @return Generator<string>
     *
     * @throws UpdateFailedException
     */
    public function runTableCheck(
        Traversable $updates,
        bool $migrate = false,
        int $build = 0
    ): Generator {
        $collation = $this->configContainer->get('database_collation') ?? 'utf8mb4_unicode_ci';
        $charset   = $this->configContainer->get('database_charset') ?? 'utf8mb4';
        $engine    = $this->configContainer->get('database_engine') ?? 'InnoDB';

        foreach ($updates as $update) {
            $tableMigrations = $update['migration']->getTableMigrations($collation, $charset, $engine, $build);

            foreach ($tableMigrations as $tableName => $migrationSql) {
                try {
                    $this->connection->query(sprintf('DESCRIBE `%s`', $tableName));

                    continue;
                } catch (DatabaseException) {
                    $this->logger->warning(
                        'Missing table: ' . $tableName,
                        [LegacyLogger::CONTEXT_TYPE => self::class]
                    );

                    if (!$migrate) {
                        yield $tableName;

                        continue;
                    }
                }

                try {
                    $this->connection->query($migrationSql);
                } catch (DatabaseException) {
                    $error = sprintf('Failed creating missing table: %s', $tableName);

                    $this->logger->critical(
                        $error,
                        [LegacyLogger::CONTEXT_TYPE => self::class]
                    );

                    throw new UpdateFailedException($error);
                }

                $this->logger->critical(
                    sprintf('Created missing table: %s', $tableName),
                    [LegacyLogger::CONTEXT_TYPE => self::class]
                );

                yield $tableName;
            }
        }
    }
}
