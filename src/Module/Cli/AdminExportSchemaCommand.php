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

namespace Ampache\Module\Cli;

use Ahc\Cli\Input\Command;
use Ampache\Module\System\Dba;
use Ampache\Module\System\Update\UpdaterInterface;
use Ampache\Repository\Model\UpdateInfoEnum;
use Ampache\Repository\UpdateInfoRepositoryInterface;
use Override;

/**
 * Regenerates `resources/sql/ampache.sql` from the connected database.
 *
 * The seed dump is a snapshot of an already-migrated schema that records the version it was taken at, and the installer
 * replays anything newer, so this only has to be run when the snapshot is refreshed for a release. Preferences are not
 * dumped; they are applied by `Preference::set_defaults()`/`translate_db()` at install time.
 */
final class AdminExportSchemaCommand extends Command
{
    /** Tables whose contents ship with Ampache; everything else is dumped as structure only */
    private const array DATA_TABLES = [
        'access_list',
        'license',
        'search',
    ];

    private const string DEFAULT_PATH = __DIR__ . '/../../../resources/sql/ampache.sql';

    public function __construct(
        private readonly UpdaterInterface $updater,
        private readonly UpdateInfoRepositoryInterface $updateInfoRepository,
    ) {
        // Release-engineering tool: the output is not translated, it is only ever read by a maintainer refreshing the dump.
        parent::__construct('admin:exportSchema', 'Regenerate the ampache.sql seed dump from this database');

        $this
            ->option('-e|--execute', 'Write the file', 'boolval', false)
            ->option('-f|--file', 'Output file', 'strval', '')
            ->usage('<bold>  admin:exportSchema</end> <comment> ## Show what would be written</end><eol/>');
    }

    public function execute(): void
    {
        $interactor = $this->io();
        $execute    = $this->values()['execute'] === true;
        $path       = ($this->values()['file'] !== '')
            ? (string) $this->values()['file']
            : self::DEFAULT_PATH;

        if (!Dba::check_database()) {
            $interactor->error(T_('Database Connection') . ': ' . T_('Error'), true);

            return;
        }

        // Dumping a database that still has migrations waiting would bake a schema older than the recorded version into
        // the file, which is the exact drift this dump exists to avoid.
        if ($this->updater->hasPendingUpdates()) {
            $interactor->error(
                'This database has pending updates. Run admin:updateDatabase -e first',
                true
            );

            return;
        }

        $version = (int) $this->updateInfoRepository->getValueByKey(UpdateInfoEnum::DB_VERSION);
        $tables  = $this->getTables();

        $interactor->info(sprintf(T_('Database version: %s'), $version), true);
        $interactor->info(sprintf('Tables: %s', count($tables)), true);

        foreach (self::DATA_TABLES as $table) {
            $interactor->info(
                sprintf('Dumping %s rows from `%s`', $this->countRows($table), $table),
                true
            );
        }

        $interactor->warn(
            'Row data is taken from this database. Export from a clean install so no local rows are shipped',
            true
        );

        if (!$execute) {
            $interactor->info('Use -e|--execute to write the file', true);

            return;
        }

        if (file_put_contents($path, $this->buildDump($tables, $version)) === false) {
            $interactor->error(sprintf(T_('Unable to write to `%s`'), $path), true);

            return;
        }

        $interactor->ok(sprintf('Wrote %s', $path), true);
    }

    #[Override]
    protected function defaults(): self
    {
        $this->option('-h, --help', T_('Help'))->on($this->showHelp(...));

        $this->onExit(static fn($exitCode = 0) => exit($exitCode));

        return $this;
    }

    /**
     * @param list<string> $tables
     */
    private function buildDump(array $tables, int $version): string
    {
        $output = $this->buildHeader();

        foreach ($tables as $table) {
            $output .= $this->buildTable($table);
        }

        $output .= sprintf(
            "--\n-- Dumping data for table `update_info`\n--\n-- `db_version` is the migration version THIS FILE was dumped at, not the current one. The installer reads it and\n-- runs every later migration, so the dump may lag the code and only has to be refreshed at release. Never raise it\n-- by hand: a value above what the schema above actually contains makes the updater see nothing pending and skip\n-- those migrations forever.\n-- Installed plugins set their own `Plugin_*` version rows via Plugin::set_plugin_version().\n--\n\nINSERT INTO `update_info` (`key`, `value`) VALUES\n('db_version', '%d');\n\n",
            $version
        );

        return $output . "COMMIT;\n\n/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;\n/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;\n/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;\n";
    }

    private function buildHeader(): string
    {
        return "-- GNU Affero General Public License, version 3 (AGPL-3.0-or-later)\n"
            . "-- Copyright Ampache.org, 2001-2026\n"
            . "--\n"
            . "-- This program is free software: you can redistribute it and/or modify\n"
            . "-- it under the terms of the GNU Affero General Public License as published by\n"
            . "-- the Free Software Foundation, either version 3 of the License, or\n"
            . "-- (at your option) any later version.\n"
            . "--\n"
            . "-- This program is distributed in the hope that it will be useful,\n"
            . "-- but WITHOUT ANY WARRANTY; without even the implied warranty of\n"
            . "-- MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the\n"
            . "-- GNU Affero General Public License for more details.\n"
            . "--\n"
            . "-- You should have received a copy of the GNU Affero General Public License\n"
            . "-- along with this program.  If not, see <https://www.gnu.org/licenses/>.\n"
            . "--\n"
            . "-- Generated by `bin/cli admin:exportSchema`. Preference rows are not dumped; the installer applies them\n"
            . "-- from Preference::set_defaults() and Preference::translate_db().\n\n"
            . "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";\n"
            . "START TRANSACTION;\n"
            . "SET time_zone = \"+00:00\";\n\n"
            . "/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;\n"
            . "/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;\n"
            . "/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;\n"
            . "/*!40101 SET NAMES utf8mb4 */;\n\n";
    }

    private function buildTable(string $table): string
    {
        $db_results = Dba::read(sprintf('SHOW CREATE TABLE `%s`;', $table));
        $row        = Dba::fetch_row($db_results);
        $create     = (string) ($row[1] ?? '');
        if ($create === '') {
            return '';
        }

        // AUTO_INCREMENT counters are the state of this database, not part of the schema, and CREATE TABLE is made
        // conditional so the installer can run the dump against a partially created database.
        $create = (string) preg_replace('/ AUTO_INCREMENT=\d+/', '', $create);
        $create = (string) preg_replace('/^CREATE TABLE /', 'CREATE TABLE IF NOT EXISTS ', $create);

        $output = sprintf(
            "-- --------------------------------------------------------\n\n--\n-- Table structure for table `%s`\n--\n\nDROP TABLE IF EXISTS `%s`;\n%s;\n\n",
            $table,
            $table,
            $create
        );

        if (in_array($table, self::DATA_TABLES, true)) {
            $output .= $this->buildTableData($table);
        }

        return $output;
    }

    private function buildTableData(string $table): string
    {
        $db_results = Dba::read(sprintf('SELECT * FROM `%s`;', $table));
        $rows       = [];
        $columns    = [];
        while ($row = Dba::fetch_assoc($db_results, false)) {
            if ($columns === []) {
                $columns = array_keys($row);
            }

            $values = [];
            foreach ($row as $value) {
                $values[] = ($value === null)
                    ? 'NULL'
                    : sprintf("'%s'", Dba::escape($value));
            }

            $rows[] = sprintf('(%s)', implode(', ', $values));
        }

        if ($rows === []) {
            return '';
        }

        return sprintf(
            "--\n-- Dumping data for table `%s`\n--\n\nINSERT INTO `%s` (`%s`) VALUES\n%s;\n\n",
            $table,
            $table,
            implode('`, `', $columns),
            implode(",\n", $rows)
        );
    }

    private function countRows(string $table): int
    {
        $db_results = Dba::read(sprintf('SELECT COUNT(*) FROM `%s`;', $table));
        $row        = Dba::fetch_row($db_results);

        return (int) ($row[0] ?? 0);
    }

    /**
     * @return list<string>
     */
    private function getTables(): array
    {
        $db_results = Dba::read('SHOW TABLES;');
        $tables     = [];
        while ($row = Dba::fetch_row($db_results, false)) {
            $tables[] = (string) $row[0];
        }

        sort($tables);

        return $tables;
    }
}
