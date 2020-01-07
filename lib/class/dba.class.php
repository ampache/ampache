<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPLv3)
 * Copyright 2001 - 2020 Ampache.org
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

/* Make sure they aren't directly accessing it */
if (!defined('INIT_LOADED') || INIT_LOADED != '1') {
    return false;
}

/**
 * Dba Class
 *
 * This is the database abstraction class
 * It duplicates the functionality of mysql_???
 * with a few exceptions, the row and assoc will always
 * return an array, simplifying checking on the far end
 * it will also auto-connect as needed, and has a default
 * database simplifying queries in most cases.
 *
 */
class Dba
{
    public static $stats = array('query' => 0);

    private static $_sql;
    private static $_error;

    /**
     * constructor
     * This does nothing with the DBA class
     */
    private function __construct()
    {
        // Rien a faire
    } // construct

    /**
     * query
     * @return PDOStatement|boolean
     */
    public static function query($sql, $params = array())
    {
        // json_encode throws errors about UTF-8 cleanliness, which we don't
        // care about here.
        debug_event('dba.class', $sql . ' ' . json_encode($params), 6);

        // Be aggressive, be strong, be dumb
        $tries = 0;
        do {
            $stmt = self::_query($sql, $params);
        } while (!$stmt && $tries++ < 3);

        return $stmt;
    }

    /**
     * _query
     * @return PDOStatement|boolean
     */
    private static function _query($sql, $params)
    {
        $dbh = self::dbh();
        if (!$dbh) {
            debug_event('dba.class', 'Error: failed to get database handle', 1);

            return false;
        }

        // Run the query
        if ($params) {
            $stmt = $dbh->prepare($sql);
            $stmt->execute($params);
        } else {
            $stmt = $dbh->query($sql);
        }

        // Save the query, to make debug easier
        self::$_sql = $sql;
        self::$stats['query']++;

        if (!$stmt) {
            self::$_error = json_encode($dbh->errorInfo());
            debug_event('dba.class', 'Error_query SQL: ' . $sql, 5);
            debug_event('dba.class', 'Error_query MSG: ' . json_encode($dbh->errorInfo()), 1);
            self::disconnect();
        } else {
            if ($stmt->errorCode() && $stmt->errorCode() != '00000') {
                self::$_error = json_encode($stmt->errorInfo());
                debug_event('dba.class', 'Error_query SQL: ' . $sql, 5);
                debug_event('dba.class', 'Error_query MSG: ' . json_encode($stmt->errorInfo()), 1);
                self::finish($stmt);
                self::disconnect();

                return false;
            }
        }

        return $stmt;
    }

    /**
     * read
     */
    public static function read($sql, $params = null)
    {
        return self::query($sql, $params);
    }

    /**
     * write
     * @return PDOStatement|boolean
     */
    public static function write($sql, $params = null)
    {
        return self::query($sql, $params);
    }

    /**
     * escape
     *
     * This runs an escape on a variable so that it can be safely inserted
     * into the sql
     * @return string
     */
    public static function escape($var)
    {
        $dbh = self::dbh();
        if (!$dbh) {
            debug_event('dba.class', 'Wrong dbh.', 1);

            return '';
        }
        $out_var = $dbh->quote(filter_var($var, FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES));
        // This is slightly less ugly than it was, but still ugly
        return substr($out_var, 1, -1);
    }

    /**
     * fetch_assoc
     *
     * This emulates the mysql_fetch_assoc.
     * We force it to always return an array, albeit an empty one
     * The optional finish parameter affects whether we automatically clean
     * up the result set after the last row is read.
     * @return array
     */
    public static function fetch_assoc($resource, $finish = true)
    {
        if (!$resource) {
            return array();
        }

        $result = $resource->fetch(PDO::FETCH_ASSOC);

        if (!$result) {
            if ($finish) {
                self::finish($resource);
            }

            return array();
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
     */
    public static function fetch_row($resource, $finish = true)
    {
        if (!$resource) {
            return array();
        }

        $result = $resource->fetch(PDO::FETCH_NUM);

        if (!$result) {
            if ($finish) {
                self::finish($resource);
            }

            return array();
        }

        return $result;
    }

    public static function fetch_object($resource, $class = 'stdClass', $finish = true)
    {
        if (!$resource) {
            return array();
        }

        $result = $resource->fetchObject($class);

        if (!$result) {
            if ($finish) {
                self::finish($resource);
            }

            return array();
        }

        return $result;
    }

    /**
     * num_rows
     *
     * This emulates the mysql_num_rows function which is really
     * just a count of rows returned by our select statement, this
     * doesn't work for updates or inserts.
     * @return integer
     */
    public static function num_rows($resource)
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
     */
    public static function finish($resource)
    {
        if ($resource) {
            $resource->closeCursor();
        }
    }

    /**
     * affected_rows
     *
     * This emulates the mysql_affected_rows function
     * @return integer
     */
    public static function affected_rows($resource)
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
     */
    private static function _connect()
    {
        $username = AmpConfig::get('database_username');
        $hostname = AmpConfig::get('database_hostname');
        $password = AmpConfig::get('database_password');
        $port     = AmpConfig::get('database_port');

        // Build the data source name
        if (strpos($hostname, '/') === 0) {
            $dsn = 'mysql:unix_socket=' . $hostname;
        } else {
            $dsn = 'mysql:host=' . $hostname ?: 'localhost';
        }
        if ($port) {
            $dsn .= ';port=' . (int) ($port);
        }

        try {
            debug_event('dba.class', 'Database connection...', 6);
            $dbh = new PDO($dsn, $username, $password);
        } catch (PDOException $error) {
            self::$_error = $error->getMessage();
            debug_event('dba.class', 'Connection failed: ' . $error->getMessage(), 1);

            return null;
        }

        return $dbh;
    }

    /**
     * _setup_dbh
     */
    private static function _setup_dbh($dbh, $database)
    {
        if (!$dbh) {
            return false;
        }

        $charset = self::translate_to_mysqlcharset(AmpConfig::get('site_charset'));
        $charset = $charset['charset'];
        if ($dbh->exec('SET NAMES ' . $charset) === false) {
            debug_event('dba.class', 'Unable to set connection charset to ' . $charset, 1);
        }

        if ($dbh->exec('USE `' . $database . '`') === false) {
            self::$_error = json_encode($dbh->errorInfo());
            debug_event('dba.class', 'Unable to select database ' . $database . ': ' . json_encode($dbh->errorInfo()), 1);
        }

        if (AmpConfig::get('sql_profiling')) {
            $dbh->exec('SET profiling=1');
            $dbh->exec('SET profiling_history_size=50');
            $dbh->exec('SET query_cache_type=0');
        }
    }

    /**
     * check_database
     *
     * Make sure that we can connect to the database
     * @return boolean
     */
    public static function check_database()
    {
        $dbh = self::_connect();

        if (!$dbh || $dbh->errorCode()) {
            if ($dbh) {
                self::$_error = json_encode($dbh->errorInfo());
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
     * @return boolean
     */
    public static function check_database_inserted()
    {
        $sql        = "DESCRIBE session";
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
    public static function show_profile()
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
     */
    public static function dbh($database = '')
    {
        if (!$database) {
            $database = AmpConfig::get('database_name');
        }

        // Assign the Handle name that we are going to store
        $handle = 'dbh_' . $database;

        if (!is_object(AmpConfig::get($handle))) {
            $dbh = self::_connect();
            self::_setup_dbh($dbh, $database);
            AmpConfig::set($handle, $dbh, true);

            return $dbh;
        } else {
            return AmpConfig::get($handle);
        }
    }

    /**
     * disconnect
     *
     * This nukes the dbh connection, this isn't used very often...
     * @return true
     */
    public static function disconnect($database = '')
    {
        if (!$database) {
            $database = AmpConfig::get('database_name');
        }

        $handle = 'dbh_' . $database;

        // Nuke it
        debug_event('dba.class', 'Database disconnection.', 6);
        AmpConfig::set($handle, null, true);

        return true;
    }

    /**
     * insert_id
     * @return string|null
     */
    public static function insert_id()
    {
        $dbh = self::dbh();
        if ($dbh) {
            return $dbh->lastInsertId();
        }

        return null;
    }

    /**
     * error
     * this returns the error of the db
     */
    public static function error()
    {
        return self::$_error;
    }

    /**
     * translate_to_mysqlcharset
     *
     * This translates the specified charset to a mysql charset.
     */
    public static function translate_to_mysqlcharset($charset)
    {
        // Translate real charset names into fancy MySQL land names
        switch (strtoupper((string) $charset)) {
            case 'CP1250':
            case 'WINDOWS-1250':
                $target_charset   = 'cp1250';
                $target_collation = 'cp1250_general_ci';
                break;
            case 'ISO-8859':
            case 'ISO-8859-2':
                $target_charset   = 'latin2';
                $target_collation = 'latin2_general_ci';
                break;
            case 'ISO-8859-1':
            case 'CP1252':
            case 'WINDOWS-1252':
                $target_charset   = 'latin1';
                $target_collation = 'latin1_general_ci';
                break;
            case 'EUC-KR':
                $target_charset   = 'euckr';
                $target_collation = 'euckr_korean_ci';
                break;
            case 'CP932':
                $target_charset   = 'sjis';
                $target_collation = 'sjis_japanese_ci';
                break;
            case 'KOI8-U':
                $target_charset   = 'koi8u';
                $target_collation = 'koi8u_general_ci';
                break;
            case 'KOI8-R':
                $target_charset   = 'koi8r';
                $target_collation = 'koi8r_general_ci';
                break;
            case 'UTF-8':
            default:
                $target_charset   = 'utf8';
                $target_collation = 'utf8_unicode_ci';
                break;
        }

        return array(
            'charset' => $target_charset,
            'collation' => $target_collation
        );
    }

    /**
     * reset_db_charset
     *
     * This cruises through the database and trys to set the charset to the
     * current site charset. This is an admin function that can be run by an
     * administrator only. This can mess up data if you switch between charsets
     * that are not overlapping.
     */
    public static function reset_db_charset()
    {
        $translated_charset = self::translate_to_mysqlcharset(AmpConfig::get('site_charset'));
        $target_charset     = $translated_charset['charset'];
        $target_collation   = $translated_charset['collation'];

        // Alter the charset for the entire database
        $sql = "ALTER DATABASE `" . AmpConfig::get('database_name') . "` DEFAULT CHARACTER SET $target_charset COLLATE $target_collation";
        self::write($sql);

        $sql        = "SHOW TABLES";
        $db_results = self::read($sql);

        // Go through the tables!
        while ($row = self::fetch_row($db_results)) {
            $sql              = "DESCRIBE `" . $row['0'] . "`";
            $describe_results = self::read($sql);

            // Change the tables default charset and colliation
            $sql = "ALTER TABLE `" . $row['0'] . "`  DEFAULT CHARACTER SET $target_charset COLLATE $target_collation";
            self::write($sql);

            // Iterate through the columns of the table
            while ($table = self::fetch_assoc($describe_results)) {
                if (
                (strpos($table['Type'], 'varchar') !== false) ||
                (strpos($table['Type'], 'enum') !== false) ||
                (strpos($table['Table'], 'text') !== false)) {
                    $sql             = "ALTER TABLE `" . $row['0'] . "` MODIFY `" . $table['Field'] . "` " . $table['Type'] . " CHARACTER SET " . $target_charset;
                    $charset_results = self::write($sql);
                    if (!$charset_results) {
                        debug_event('dba.class', 'Unable to update the charset of ' . $table['Field'] . '.' . $table['Type'] . ' to ' . $target_charset, 3);
                    } // if it fails
                }
            }
        }
    }

    /**
     * optimize_tables
     *
     * This runs an optimize on the tables and updates the stats to improve
     * join speed.
     * This can be slow, but is a good idea to do from time to time. We do
     * it in case the dba isn't doing it... which we're going to assume they
     * aren't.
     */
    public static function optimize_tables()
    {
        $sql        = "SHOW TABLES";
        $db_results = self::read($sql);

        while ($row = self::fetch_row($db_results)) {
            $sql = "OPTIMIZE TABLE `" . $row[0] . "`";
            self::write($sql);

            $sql = "ANALYZE TABLE `" . $row[0] . "`";
            self::write($sql);
        }
    }
}
