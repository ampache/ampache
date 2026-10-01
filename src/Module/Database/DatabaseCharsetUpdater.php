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

namespace Ampache\Module\Database;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Module\System\Dba;
use PDOStatement;

final readonly class DatabaseCharsetUpdater implements DatabaseCharsetUpdaterInterface
{
    /**
     * Columns that must stay on the legacy 3-byte utf8 charset regardless of site_charset.
     * These only ever hold ASCII (ids/tokens, enum-style type flags, MusicBrainz uuids), so
     * they never need utf8mb4 and are deliberately excluded from the blanket conversion.
     *
     * @var array<string, list<string>>
     */
    private const array FIXED_UTF8_COLUMNS = [
        'album' => ['mbid', 'mbid_group'],
        'album_map' => ['object_type'],
        'artist' => ['mbid'],
        'artist_map' => ['object_type'],
        'bookmark' => ['object_type'],
        'cache_object_count' => ['object_type', 'count_type'],
        'cache_object_count_run' => ['object_type', 'count_type'],
        'image' => ['object_type', 'kind'],
        'live_stream' => ['codec'],
        'now_playing' => ['id', 'object_type'],
        'object_count' => ['object_type', 'agent', 'geo_name', 'count_type'],
        'player_control' => ['object_type'],
        'playlist' => ['type'],
        'playlist_data' => ['object_type'],
        'podcast_episode' => ['state'],
        'rating' => ['object_type'],
        'recommendation' => ['object_type'],
        'recommendation_item' => ['mbid'],
        'search' => ['type'],
        'session' => ['id'],
        'session_remember' => ['token'],
        'session_stream' => ['id'],
        'share' => ['object_type'],
        'song' => ['mode', 'mbid'],
        'song_preview' => ['artist_mbid', 'album_mbid', 'mbid'],
        'stream_playlist' => ['codec'],
        'tag_map' => ['object_type'],
        'tmp_playlist' => ['object_type'],
        'tmp_playlist_data' => ['object_type'],
        'user_activity' => ['object_type'],
        'user_flag' => ['object_type'],
        'user_shout' => ['object_type'],
        'video' => ['mode'],
        'wanted' => ['artist_mbid', 'mbid'],
    ];

    private const string FIXED_UTF8_CHARSET = 'utf8';

    private const string FIXED_UTF8_COLLATION = 'utf8_unicode_ci';

    public function __construct(private ConfigContainerInterface $configContainer) {}

    public function findMismatches(): array
    {
        return $this->diff();
    }

    public function update(): array
    {
        $applied = [];
        foreach ($this->diff() as $mismatch) {
            $result               = Dba::write($mismatch['sql']);
            $mismatch['success']  = $result instanceof PDOStatement;
            $applied[]            = $mismatch;
        }

        return $applied;
    }

    /**
     * @return list<array{scope: string, table: string, column: ?string, current: string, desired: string, sql: string}>
     */
    private function diff(): array
    {
        $database   = (string) $this->configContainer->get('database_name');
        $translated = Dba::translate_to_mysqlcharset((string) $this->configContainer->get('site_charset'));
        $targetCharset   = $translated['charset'];
        $targetCollation = $translated['collation'];
        $targetEngine    = (string) ($this->configContainer->get('database_engine') ?? 'InnoDB');

        $mismatches = [];

        $schema = Dba::fetch_assoc(Dba::read(
            'SELECT DEFAULT_CHARACTER_SET_NAME, DEFAULT_COLLATION_NAME FROM information_schema.SCHEMATA WHERE SCHEMA_NAME = ?',
            [$database]
        ));
        if (
            $schema !== []
            && ($schema['DEFAULT_CHARACTER_SET_NAME'] !== $targetCharset || $schema['DEFAULT_COLLATION_NAME'] !== $targetCollation)
        ) {
            $mismatches[] = [
                'scope' => 'database',
                'table' => $database,
                'column' => null,
                'current' => $schema['DEFAULT_CHARACTER_SET_NAME'] . '/' . $schema['DEFAULT_COLLATION_NAME'],
                'desired' => $targetCharset . '/' . $targetCollation,
                'sql' => sprintf(
                    'ALTER DATABASE `%s` DEFAULT CHARACTER SET %s COLLATE %s',
                    $database,
                    $targetCharset,
                    $targetCollation
                ),
            ];
        }

        $tables = Dba::read(
            'SELECT TABLE_NAME, ENGINE, TABLE_COLLATION FROM information_schema.TABLES WHERE TABLE_SCHEMA = ?',
            [$database]
        );
        while ($table = Dba::fetch_assoc($tables)) {
            $tableName = (string) $table['TABLE_NAME'];

            if ($table['ENGINE'] !== null && $table['ENGINE'] !== $targetEngine) {
                $mismatches[] = [
                    'scope' => 'engine',
                    'table' => $tableName,
                    'column' => null,
                    'current' => (string) $table['ENGINE'],
                    'desired' => $targetEngine,
                    'sql' => sprintf('ALTER TABLE `%s` ENGINE=%s', $tableName, $targetEngine),
                ];
            }

            if ($table['TABLE_COLLATION'] !== null && $table['TABLE_COLLATION'] !== $targetCollation) {
                $mismatches[] = [
                    'scope' => 'table',
                    'table' => $tableName,
                    'column' => null,
                    'current' => (string) $table['TABLE_COLLATION'],
                    'desired' => $targetCollation,
                    // `DEFAULT CHARACTER SET` (no CONVERT TO) only changes what new columns get;
                    // it never rewrites bytes in columns that already exist.
                    'sql' => sprintf(
                        'ALTER TABLE `%s` DEFAULT CHARACTER SET %s COLLATE %s',
                        $tableName,
                        $targetCharset,
                        $targetCollation
                    ),
                ];
            }
        }

        $columns = Dba::read(
            'SELECT TABLE_NAME, COLUMN_NAME, CHARACTER_SET_NAME, COLLATION_NAME, COLUMN_TYPE, IS_NULLABLE, COLUMN_DEFAULT, EXTRA
             FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = ? AND CHARACTER_SET_NAME IS NOT NULL',
            [$database]
        );
        while ($column = Dba::fetch_assoc($columns)) {
            $table = (string) $column['TABLE_NAME'];
            $field = (string) $column['COLUMN_NAME'];
            $pinned = in_array($field, self::FIXED_UTF8_COLUMNS[$table] ?? [], true);

            [$desiredCharset, $desiredCollation] = $pinned
                ? [self::FIXED_UTF8_CHARSET, self::FIXED_UTF8_COLLATION]
                : [$targetCharset, $targetCollation];

            if ($column['CHARACTER_SET_NAME'] === $desiredCharset && $column['COLLATION_NAME'] === $desiredCollation) {
                continue;
            }

            $null = ($column['IS_NULLABLE'] === 'NO') ? ' NOT NULL' : ' NULL';
            if ($column['COLUMN_DEFAULT'] !== null) {
                $default = " DEFAULT '" . Dba::escape($column['COLUMN_DEFAULT']) . "'";
            } elseif ($column['IS_NULLABLE'] === 'YES') {
                $default = ' DEFAULT NULL';
            } else {
                $default = '';
            }
            $extra = ($column['EXTRA'] !== '') ? ' ' . $column['EXTRA'] : '';

            $mismatches[] = [
                'scope' => 'column',
                'table' => $table,
                'column' => $field,
                'current' => $column['CHARACTER_SET_NAME'] . '/' . $column['COLLATION_NAME'],
                'desired' => $desiredCharset . '/' . $desiredCollation,
                'sql' => sprintf(
                    'ALTER TABLE `%s` MODIFY COLUMN `%s` %s CHARACTER SET %s COLLATE %s%s%s%s',
                    $table,
                    $field,
                    $column['COLUMN_TYPE'],
                    $desiredCharset,
                    $desiredCollation,
                    $null,
                    $default,
                    $extra
                ),
            ];
        }

        return $mismatches;
    }
}
