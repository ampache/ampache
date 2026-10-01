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
    public function __construct(private ConfigContainerInterface $configContainer) {}

    public function findMismatches(): array
    {
        return $this->diff();
    }

    public function update(): array
    {
        $applied = [];
        foreach ($this->diff() as $mismatch) {
            // entries with no sql (e.g. a table missing from the schema reference) are informational only
            if ($mismatch['sql'] === '') {
                continue;
            }

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
        $database        = (string) $this->configContainer->get('database_name');
        $translated      = Dba::translate_to_mysqlcharset((string) $this->configContainer->get('site_charset'));
        $targetCharset   = $translated['charset'];
        $targetCollation = $translated['collation'];
        $targetEngine    = (string) ($this->configContainer->get('database_engine') ?? 'InnoDB');
        $schema          = $this->loadSchemaReference($targetCharset, $targetCollation);

        $mismatches = [];

        $schemaDefaults = Dba::fetch_assoc(Dba::read(
            'SELECT DEFAULT_CHARACTER_SET_NAME, DEFAULT_COLLATION_NAME FROM information_schema.SCHEMATA WHERE SCHEMA_NAME = ?',
            [$database]
        ));
        if (
            $schemaDefaults !== []
            && ($schemaDefaults['DEFAULT_CHARACTER_SET_NAME'] !== $targetCharset || $schemaDefaults['DEFAULT_COLLATION_NAME'] !== $targetCollation)
        ) {
            $mismatches[] = [
                'scope' => 'database',
                'table' => $database,
                'column' => null,
                'current' => $schemaDefaults['DEFAULT_CHARACTER_SET_NAME'] . '/' . $schemaDefaults['DEFAULT_COLLATION_NAME'],
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

            $reference = $schema['tables'][$tableName] ?? null;
            if ($reference === null) {
                // nothing in resources/sql/ampache.sql to compare against - a table this tool
                // has never heard of is more likely orphaned (left behind by a removed feature)
                // than something it should be guessing a charset for.
                $mismatches[] = [
                    'scope' => 'unknown',
                    'table' => $tableName,
                    'column' => null,
                    'current' => 'present in the database',
                    'desired' => 'not found in resources/sql/ampache.sql - confirm this table is still used, or regenerate the schema reference with admin:exportSchema',
                    'sql' => '',
                ];
                continue;
            }

            if ($table['TABLE_COLLATION'] !== null && $this->normalizeCollation((string) $table['TABLE_COLLATION']) !== $reference['collation']) {
                $mismatches[] = [
                    'scope' => 'table',
                    'table' => $tableName,
                    'column' => null,
                    'current' => (string) $table['TABLE_COLLATION'],
                    'desired' => $reference['collation'],
                    // `DEFAULT CHARACTER SET` (no CONVERT TO) only changes what new columns get;
                    // it never rewrites bytes in columns that already exist.
                    'sql' => sprintf(
                        'ALTER TABLE `%s` DEFAULT CHARACTER SET %s COLLATE %s',
                        $tableName,
                        $reference['charset'],
                        $reference['collation']
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

            // an unlisted column in a known table just inherits that table's own default -
            // only skip when the table itself has no ground truth to fall back to
            $reference = $schema['columns'][$table][$field] ?? $schema['tables'][$table] ?? null;
            if ($reference === null) {
                continue;
            }

            $currentCharset   = $this->normalizeCharset((string) $column['CHARACTER_SET_NAME']);
            $currentCollation = $this->normalizeCollation((string) $column['COLLATION_NAME']);
            if ($currentCharset === $reference['charset'] && $currentCollation === $reference['collation']) {
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
                'desired' => $reference['charset'] . '/' . $reference['collation'],
                'sql' => sprintf(
                    'ALTER TABLE `%s` MODIFY COLUMN `%s` %s CHARACTER SET %s COLLATE %s%s%s%s',
                    $table,
                    $field,
                    $column['COLUMN_TYPE'],
                    $reference['charset'],
                    $reference['collation'],
                    $null,
                    $default,
                    $extra
                ),
            ];
        }

        return $mismatches;
    }

    /**
     * Tables that localplay controllers create for themselves on install (see e.g.
     * Module/Playback/Localplay/Vlc/AmpacheVlc.php) rather than shipping in resources/sql/ampache.sql.
     * Their columns use `COLLATE {$collation}` with no CHARACTER SET override at all, so unlike the
     * core schema there is nothing column-specific to pin - every column just follows the configured
     * site charset, which is exactly what falling back to the table default already gives us.
     *
     * @var list<string>
     */
    private const array PLUGIN_INSTALLED_TABLES = [
        'localplay_httpq',
        'localplay_mpd',
        'localplay_upnp',
        'localplay_vlc',
        'localplay_xbmc',
    ];

    /**
     * Parses resources/sql/ampache.sql - the schema that a fresh install actually gets - into the
     * per-table default charset/collation and any column-level override, so the diff always
     * reflects what the project's own migrations declared rather than a second, separately
     * maintained guess that inevitably drifts from it. Tables a module installs for itself
     * (PLUGIN_INSTALLED_TABLES) are added on top, since they're real and currently installable
     * but never appear in that file.
     *
     * @return array{
     *     tables: array<string, array{charset: string, collation: string}>,
     *     columns: array<string, array<string, array{charset: string, collation: string}>>
     * }
     */
    private function loadSchemaReference(string $targetCharset, string $targetCollation): array
    {
        $path = dirname(__DIR__, 3) . '/resources/sql/ampache.sql';

        $tables  = [];
        $columns = [];

        $table          = null;
        $pendingColumns = [];

        foreach (file($path, FILE_IGNORE_NEW_LINES) ?: [] as $line) {
            if (preg_match('/^CREATE TABLE IF NOT EXISTS `(\w+)` \(/', $line, $created)) {
                $table          = $created[1];
                $pendingColumns = [];
                continue;
            }

            if ($table === null) {
                continue;
            }

            if (preg_match('/^\) ENGINE=\w+ DEFAULT CHARSET=(\w+) COLLATE=(\w+)\b/', $line, $closed)) {
                $default = [
                    'charset' => $this->normalizeCharset($closed[1]),
                    'collation' => $this->normalizeCollation($closed[2]),
                ];

                $tables[$table]  = $default;
                $columns[$table] = [];
                foreach ($pendingColumns as $name => $override) {
                    $columns[$table][$name] = $override ?? $default;
                }

                $table = null;
                continue;
            }

            // a column line always starts with a backtick identifier; KEY/PRIMARY KEY/CONSTRAINT lines don't
            if (preg_match('/^\s*`(\w+)`\s/', $line, $declared)) {
                $override = null;
                if (preg_match('/CHARACTER SET (\w+) COLLATE (\w+)/', $line, $explicit)) {
                    $override = [
                        'charset' => $this->normalizeCharset($explicit[1]),
                        'collation' => $this->normalizeCollation($explicit[2]),
                    ];
                }

                $pendingColumns[$declared[1]] = $override;
            }
        }

        foreach (self::PLUGIN_INSTALLED_TABLES as $pluginTable) {
            $tables[$pluginTable] ??= ['charset' => $targetCharset, 'collation' => $targetCollation];
        }

        return ['tables' => $tables, 'columns' => $columns];
    }

    /**
     * MySQL 8 and recent MariaDB report the 3-byte charset as `utf8mb3`; older servers (and `utf8`
     * written by hand in older migrations) still say plain `utf8`. Same charset, different name.
     */
    private function normalizeCharset(string $charset): string
    {
        return $charset === 'utf8' ? 'utf8mb3' : $charset;
    }

    private function normalizeCollation(string $collation): string
    {
        return str_starts_with($collation, 'utf8_') ? 'utf8mb3_' . substr($collation, 5) : $collation;
    }
}
