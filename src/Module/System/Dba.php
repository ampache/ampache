<?php

declare(strict_types=0);

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
 *
 */

namespace Ampache\Module\System;

use Ampache\Config\AmpConfig;
use PDO;
use PDOException;
use PDOStatement;

/**
 * This is the database abstraction class
 * It duplicates the functionality of mysql_???
 * with a few exceptions, the row and assoc will always
 * return an array, simplifying checking on the far end
 * it will also auto-connect as needed, and has a default
 * database simplifying queries in most cases.
 */
class Dba
{
    public static $stats = ['query' => 0];

    private static $_sql;
    private static string $_error;

    /**
     * query
     * @param string $sql
     * @param array $params
     * @return PDOStatement|false
     */
    public static function query($sql, $params = [])
    {
        // json_encode throws errors about UTF-8 cleanliness, which we don't care about here.
        //debug_event(self::class, $sql . ' ' . json_encode($params), 5);
        if (empty(trim($sql))) {
            return false;
        }

        // Be aggressive, be strong, be dumb
        $tries = 0;
        do {
            $stmt = self::_query($sql, $params);
        } while (!$stmt && $tries++ < 3);

        return $stmt;
    }

    /**
     * _query
     * @param string $sql
     * @param array $params
     * @return PDOStatement|false
     */
    private static function _query($sql, $params)
    {
        $dbh = self::dbh();
        if (!$dbh) {
            debug_event(self::class, 'Error: failed to get database handle', 1);

            return false;
        }

        self::$_sql = $sql;
        try {
            // Run the query
            if (!empty($params) && strpos((string)self::$_sql, '?')) {
                $stmt = $dbh->prepare(self::$_sql);
                $stmt->execute($params);
            } else {
                $stmt = $dbh->query(self::$_sql);
            }
        } catch (PDOException $error) {
            // are you trying to write to something that doesn't exist?
            self::$_error = $error->getMessage();
            debug_event(self::class, 'Error_query SQL: ' . self::$_sql . ' ' . json_encode($params), 5);
            debug_event(self::class, 'Error_query MSG: ' . $error->getMessage(), 1);

            return false;
        }

        if (!$stmt) {
            self::$_error = (string)json_encode($dbh->errorInfo());
            debug_event(self::class, 'Error_query SQL: ' . self::$_sql . ' ' . json_encode($params), 5);
            debug_event(self::class, 'Error_query MSG: ' . json_encode($dbh->errorInfo()), 1);
            self::disconnect();
        } elseif ($stmt->errorCode() && $stmt->errorCode() != '00000') {
            self::$_error = (string)json_encode($stmt->errorInfo());
            debug_event(self::class, 'Error_query SQL: ' . self::$_sql . ' ' . json_encode($params), 5);
            debug_event(self::class, 'Error_query MSG: ' . json_encode($stmt->errorInfo()), 1);
            self::finish($stmt);
            self::disconnect();

            return false;
        }
        self::$stats['query']++;

        return $stmt;
    }

    /**
     * read
     * @param string $sql
     * @param array $params
     * @return PDOStatement|false
     */
    public static function read($sql, $params = [])
    {
        return self::query($sql, $params);
    }

    /**
     * write
     * @param string $sql
     * @param array $params
     * @return PDOStatement|false
     */
    public static function write($sql, $params = [])
    {
        return self::query($sql, $params);
    }

    /**
     * escape
     *
     * This runs an escape on a variable so that it can be safely inserted
     * into the sql
     * @param mixed $var
     */
    public static function escape($var): ?string
    {
        $dbh = self::dbh();
        if (!$dbh) {
            debug_event(self::class, 'Wrong dbh.', 1);

            return '';
        }
        if ($var === null) {
            return '';
        }
        $out_var = $dbh->quote($var);

        // This is slightly less ugly than it was, but still ugly
        return substr($out_var, 1, -1);
    }

    /**
     * check_length
     * Truncate strings for the database that are longer than the limits
     * @param string $value
     * @param int $length
     */
    public static function check_length($value, $length): string
    {
        $result = substr($value, 0, $length);
        if (!$result) {
            return $value;
        }

        return $result;
    }

    /**
     * fetch_assoc
     *
     * This emulates the mysql_fetch_assoc.
     * We force it to always return an array, albeit an empty one
     * The optional finish parameter affects whether we automatically clean
     * up the result set after the last row is read.
     * @param false|PDOStatement $resource
     * @param bool $finish
     * @return array
     */
    public static function fetch_assoc($resource, $finish = true): array
    {
        if (!$resource) {
            return [];
        }

        $result = $resource->fetch(PDO::FETCH_ASSOC);

        if (!$result) {
            if ($finish) {
                self::finish($resource);
            }

            return [];
        }

        return $result;
    }

    /**
     * fetch_row
     *
     * This emulates the mysql_fetch_row
     * we force it to always return an array, albeit an empty one
     * The optional finish parameter affects whether we automatically clean
     * up the result set after the last row is read.
     * @param false|PDOStatement $resource
     * @param bool $finish
     * @return array
     */
    public static function fetch_row($resource, $finish = true): array
    {
        if (!$resource) {
            return [];
        }

        $result = $resource->fetch(PDO::FETCH_NUM);

        if (!$result) {
            if ($finish) {
                self::finish($resource);
            }

            return [];
        }

        return $result;
    }

    /**
     * Returns the value from a single column
     *
     * Returns just the first column of a db-query result
     * (or null, if the query fails). Useful, e.g. for count-results
     *
     * @param string $query
     * @param list<scalar> $parameter
     * @param bool $finish
     */
    public static function fetch_single_column(
        string $query,
        array $parameter = [],
        bool $finish = true
    ): ?string {
        $resource = self::query(
            $query,
            $parameter
        );

        if (!$resource) {
            return null;
        }

        $result = $resource->fetch(PDO::FETCH_COLUMN);

        if ($result === false) {
            if ($finish === true) {
                self::finish($resource);
            }

            return null;
        }

        return (string) $result;
    }

    /**
     * @param false|PDOStatement $resource
     * @param class-string<object> $class
     * @param bool $finish
     * @return null|object
     */
    public static function fetch_object($resource, $class = 'stdClass', $finish = true)
    {
        if (!$resource) {
            return null;
        }

        $result = $resource->fetchObject($class);

        if (!$result) {
            if ($finish) {
                self::finish($resource);
            }

            return null;
        }

        return $result;
    }

    /**
     * num_rows
     *
     * This emulates the mysql_num_rows function which is really
     * just a count of rows returned by our select statement, this
     * doesn't work for updates or inserts.
     * @param false|PDOStatement $resource
     */
    public static function num_rows($resource): int
    {
        if ($resource) {
            $result = $resource->rowCount();
            if ($result) {
                return $result;
            }
        }

        return 0;
    }

    /**
     * finish
     *
     * This closes a result handle and clears the memory associated with it
     * @param false|PDOStatement $resource
     */
    public static function finish($resource): void
    {
        if ($resource) {
            $resource->closeCursor();
        }
    }

    /**
     * affected_rows
     *
     * This emulates the mysql_affected_rows function
     * @param false|PDOStatement $resource
     */
    public static function affected_rows($resource): int
    {
        if ($resource) {
            $result = $resource->rowCount();
            if ($result) {
                return $result;
            }
        }

        return 0;
    }

    /**
     * _connect
     *
     * This connects to the database, used by the DBH function
     * @return PDO|null
     */
    private static function _connect()
    {
        $username = AmpConfig::get('database_username');
        $hostname = AmpConfig::get('database_hostname', '');
        $password = AmpConfig::get('database_password');
        $port     = AmpConfig::get('database_port');

        if ($hostname === '') {
            return null;
        }

        // Build the data source name
        if (strpos($hostname, '/') === 0) {
            $dsn = 'mysql:unix_socket=' . $hostname;
        } else {
            $dsn = 'mysql:host=' . $hostname;
        }
        if ($port) {
            $dsn .= ';port=' . (int)($port);
        }

        try {
            debug_event(self::class, 'Database connection...', 5);
            $dbh = new PDO($dsn, $username, $password);
        } catch (PDOException $error) {
            self::$_error = $error->getMessage();
            debug_event(self::class, 'Connection failed: ' . $error->getMessage(), 1);

            return null;
        }

        return $dbh;
    }

    /**
     * _setup_dbh
     * @param null|PDO $dbh
     * @param string $database
     */
    private static function _setup_dbh($dbh, $database): bool
    {
        if (
            !$dbh ||
            $dbh->errorCode()
        ) {
            return false;
        }

        $charset = self::translate_to_mysqlcharset(AmpConfig::get('site_charset'));
        $charset = $charset['charset'];
        if ($dbh->exec('SET NAMES ' . $charset) === false) {
            debug_event(self::class, 'Unable to set connection charset to ' . $charset, 1);
        }

        try {
            $dbh->exec('USE `' . $database . '`');
        } catch (PDOException $error) {
            self::$_error = (string)json_encode($dbh->errorInfo());
            debug_event(self::class, 'Unable to select database ' . $database . ': ' . json_encode($dbh->errorInfo()), 1);
        }

        if (AmpConfig::get('sql_profiling')) {
            $dbh->exec('SET profiling=1');
            $dbh->exec('SET profiling_history_size=50');
            $dbh->exec('SET query_cache_type=0');
        }

        return true;
    }

    /**
     * check_database
     *
     * Make sure that we can connect to the database
     */
    public static function check_database(): bool
    {
        $dbh = self::_connect();

        if (!$dbh || $dbh->errorCode()) {
            if ($dbh) {
                self::$_error = (string)json_encode($dbh->errorInfo());
            }

            return false;
        }

        return true;
    }

    /**
     * check_database_inserted
     *
     * Checks to make sure that you have inserted the database
     * and that the user you are using has access to it.
     */
    public static function check_database_inserted(): bool
    {
        $sql        = "DESCRIBE `session`";
        $db_results = self::read($sql);

        if (!$db_results) {
            return false;
        }

        // Make sure the table is there
        if (self::num_rows($db_results) < 1) {
            return false;
        }

        return true;
    }

    /**
     * show_profile
     *
     * This function is used for debug, helps with profiling
     */
    public static function show_profile(): void
    {
        if (AmpConfig::get('sql_profiling')) {
            print '<br/>Profiling data: <br/>';
            $res = self::read('SHOW PROFILES');
            print '<table>';
            while ($row = self::fetch_row($res)) {
                print '<tr><td>' . implode('</td><td>', $row) . '</td></tr>';
            }
            print '</table>';
        }
    }

    /**
     * dbh
     *
     * This is called by the class to return the database handle
     * for the specified database, if none is found it connects
     * @return PDO|null
     */
    public static function dbh()
    {
        $database = AmpConfig::get('database_name');
        if ($database == '') {
            return null;
        }

        // Assign the Handle name that we are going to store
        $handle = sprintf('dbh_%s', $database);

        /** * @var null|PDO $connection */
        $dbh = AmpConfig::get($handle);

        if ($dbh === null) {
            $dbh = self::_connect();
            if (!self::_setup_dbh($dbh, $database)) {
                return null;
            }

            AmpConfig::set($handle, $dbh, true);
        }

        return $dbh;
    }

    /**
     * disconnect
     *
     * This nukes the dbh connection, this isn't used very often...
     * @param string $database
     */
    public static function disconnect($database = ''): bool
    {
        if (!$database) {
            $database = AmpConfig::get('database_name');
        }

        $handle = 'dbh_' . $database;

        // Nuke it
        debug_event(self::class, 'Database disconnection.', 6);
        AmpConfig::set($handle, null, true);

        return true;
    }

    /**
     * insert_id
     * @return string|false
     */
    public static function insert_id()
    {
        $dbh = self::dbh();
        if ($dbh !== null) {
            return $dbh->lastInsertId();
        }

        return false;
    }

    /**
     * error
     * this returns the error of the db
     */
    public static function error(): string
    {
        return self::$_error;
    }

    /**
     * translate_to_mysqlcharset
     *
     * This translates the specified charset to a mysql charset.
     * @param string|null $charset
     * @return array
     */
    public static function translate_to_mysqlcharset($charset): array
    {
        // Translate real charset names into fancy MySQL land names
        switch (strtoupper((string)$charset)) {
            case 'CP1250':
            case 'WINDOWS-1250':
                $target_charset   = AmpConfig::get('database_charset', 'cp1250');
                $target_collation = AmpConfig::get('database_collation', 'cp1250_general_ci');
                break;
            case 'ISO-8859':
            case 'ISO-8859-2':
                $target_charset   = AmpConfig::get('database_charset', 'latin2');
                $target_collation = AmpConfig::get('database_collation', 'latin2_general_ci');
                break;
            case 'ISO-8859-1':
            case 'CP1252':
            case 'WINDOWS-1252':
                $target_charset   = AmpConfig::get('database_charset', 'latin1');
                $target_collation = AmpConfig::get('database_collation', 'latin1_general_ci');
                break;
            case 'EUC-KR':
                $target_charset   = AmpConfig::get('database_charset', 'euckr');
                $target_collation = AmpConfig::get('database_collation', 'euckr_korean_ci');
                break;
            case 'CP932':
                $target_charset   = AmpConfig::get('database_charset', 'sjis');
                $target_collation = AmpConfig::get('database_collation', 'sjis_japanese_ci');
                break;
            case 'KOI8-U':
                $target_charset   = AmpConfig::get('database_charset', 'koi8u');
                $target_collation = AmpConfig::get('database_collation', 'koi8u_general_ci');
                break;
            case 'KOI8-R':
                $target_charset   = AmpConfig::get('database_charset', 'koi8r');
                $target_collation = AmpConfig::get('database_collation', 'koi8r_general_ci');
                break;
            case 'UTF-8':
            default:
                $target_charset   = AmpConfig::get('database_charset', 'utf8mb4');
                $target_collation = AmpConfig::get('database_collation', 'utf8mb4_unicode_ci');
                break;
        }

        return [
            'charset' => $target_charset,
            'collation' => $target_collation
        ];
    }

    /**
     * optimize_tables
     *
     * This runs an optimize on the tables and updates the stats to improve join speed.
     * This can be slow, but is a good idea to do from time to time.
     * We do it in case the dba isn't doing it... which we're going to assume they aren't.
     */
    public static function optimize_tables(): void
    {
        $sql        = "SHOW TABLES";
        $db_results = self::read($sql);

        while ($row = self::fetch_row($db_results)) {
            debug_event(self::class, 'optimize_tables ' . $row[0], 5);
            $sql = "OPTIMIZE TABLE `" . $row[0] . "`;";
            self::write($sql);

            $sql = "ANALYZE TABLE `" . $row[0] . "`;";
            self::write($sql);
        }
    }
}
