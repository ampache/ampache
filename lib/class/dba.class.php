<?php
declare(strict_types=0);
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
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
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
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
     * @param string $sql
     * @param array $params
     * @return PDOStatement|boolean
     */
    public static function query($sql, $params = array())
    {
        // json_encode throws errors about UTF-8 cleanliness, which we don't
        // care about here.
        debug_event(self::class, $sql . ' ' . json_encode($params), 6);

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
     * @return PDOStatement|boolean
     */
    private static function _query($sql, $params)
    {
        $dbh = self::dbh();
        if (!$dbh) {
            debug_event(self::class, 'Error: failed to get database handle', 1);

            return false;
        }

        // Run the query
        if (!empty($params)) {
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
            debug_event(self::class, 'Error_query SQL: ' . $sql, 5);
            debug_event(self::class, 'Error_query MSG: ' . json_encode($dbh->errorInfo()), 1);
            self::disconnect();
        } else {
            if ($stmt->errorCode() && $stmt->errorCode() != '00000') {
                self::$_error = json_encode($stmt->errorInfo());
                debug_event(self::class, 'Error_query SQL: ' . $sql, 5);
                debug_event(self::class, 'Error_query MSG: ' . json_encode($stmt->errorInfo()), 1);
                self::finish($stmt);
                self::disconnect();

                return false;
            }
        }

        return $stmt;
    }

    /**
     * read
     * @param string $sql
     * @param array $params
     * @return PDOStatement|boolean
     */
    public static function read($sql, $params = array())
    {
        return self::query($sql, $params);
    }

    /**
     * write
     * @param string $sql
     * @param array $params
     * @return PDOStatement|boolean
     */
    public static function write($sql, $params = array())
    {
        return self::query($sql, $params);
    }

    /**
     * escape
     *
     * This runs an escape on a variable so that it can be safely inserted
     * into the sql
     * @param $var
     * @return string
     */
    public static function escape($var)
    {
        $dbh = self::dbh();
        if (!$dbh) {
            debug_event(self::class, 'Wrong dbh.', 1);

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
     * @param $resource
     * @param boolean $finish
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
     * @param $resource
     * @param boolean $finish
     * @return array
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

    /**
     * @param $resource
     * @param string $class
     * @param boolean $finish
     * @return array
     */
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
     * @param $resource
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
     * @param $resource
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
     * @param $resource
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
     * @return PDO|null
     */
    private static function _connect()
    {
        $username = AmpConfig::get('database_username');
        $hostname = AmpConfig::get('database_hostname', '');
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
            debug_event(self::class, 'Database connection...', 6);
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
     * @return boolean
     */
    private static function _setup_dbh($dbh, $database)
    {
        if (!$dbh) {
            return false;
        }

        $charset = self::translate_to_mysqlcharset(AmpConfig::get('site_charset'));
        $charset = $charset['charset'];
        if ($dbh->exec('SET NAMES ' . $charset) === false) {
            debug_event(self::class, 'Unable to set connection charset to ' . $charset, 1);
        }

        if ($dbh->exec('USE `' . $database . '`') === false) {
            self::$_error = json_encode($dbh->errorInfo());
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
     * @param string $database
     * @return mixed|PDO|null
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
     * @param string $database
     * @return boolean
     */
    public static function disconnect($database = '')
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
     * @param $charset
     * @return array
     */
    public static function translate_to_mysqlcharset($charset)
    {
        // Translate real charset names into fancy MySQL land names
        switch (strtoupper((string) $charset)) {
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
                $target_charset   = AmpConfig::get('database_charset', 'utf8');
                $target_collation = AmpConfig::get('database_collation', 'utf8_unicode_ci');
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
        $database           = AmpConfig::get('database_name');
        $translated_charset = self::translate_to_mysqlcharset(AmpConfig::get('site_charset'));
        $target_charset     = $translated_charset['charset'];
        $engine_sql         = ($translated_charset['charset'] == 'utf8mb4') ? 'ENGINE=InnoDB' : 'ENGINE=MYISAM';
        $target_collation   = $translated_charset['collation'];

        // Alter the charset for the entire database
        $sql = "ALTER DATABASE `$database` DEFAULT CHARACTER SET $target_charset COLLATE $target_collation";
        self::write($sql);

        $sql        = "SHOW TABLES";
        $db_results = self::read($sql);

        // Go through the tables!
        while ($row = self::fetch_row($db_results)) {
            $sql              = "DESCRIBE `" . $row['0'] . "`";
            $describe_results = self::read($sql);

            // Change the table engine
            $sql = "ALTER TABLE `" . $row['0'] . "` $engine_sql";
            self::write($sql);
            // Change the tables default charset and collation
            $sql = "ALTER TABLE `" . $row['0'] . "` CONVERT TO CHARACTER SET $target_charset COLLATE $target_collation";
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
                        debug_event(self::class, 'Unable to update the charset of ' . $table['Field'] . '.' . $table['Type'] . ' to ' . $target_charset, 3);
                    } // if it fails
                }
            }
        }
        // Convert all the table columns which (probably) didn't convert
        self::write("ALTER TABLE `access_list` MODIFY COLUMN `name` varchar(255) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        self::write("ALTER TABLE `access_list` MODIFY COLUMN `type` varchar(64) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        self::write("ALTER TABLE `album` MODIFY COLUMN `name` varchar(255) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        self::write("ALTER TABLE `album` MODIFY COLUMN `prefix` varchar(32) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        self::write("ALTER TABLE `album` MODIFY COLUMN `mbid` varchar(36) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        self::write("ALTER TABLE `album` MODIFY COLUMN `mbid_group` varchar(36) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        self::write("ALTER TABLE `album` MODIFY COLUMN `release_type` varchar(32) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        self::write("ALTER TABLE `album` MODIFY COLUMN `barcode` varchar(64) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        self::write("ALTER TABLE `album` MODIFY COLUMN `catalog_number` varchar(64) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        self::write("ALTER TABLE `artist` MODIFY COLUMN `name` varchar(255) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        self::write("ALTER TABLE `artist` MODIFY COLUMN `prefix` varchar(32) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        self::write("ALTER TABLE `artist` MODIFY COLUMN `mbid` varchar(1369) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        self::write("ALTER TABLE `artist` MODIFY COLUMN `summary` text CHARACTER SET $target_charset COLLATE $target_collation;");
        self::write("ALTER TABLE `artist` MODIFY COLUMN `placeformed` varchar(64) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        self::write("ALTER TABLE `bookmark` MODIFY COLUMN `comment` varchar(255) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        self::write("ALTER TABLE `bookmark` MODIFY COLUMN `object_type` varchar(64) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        self::write("ALTER TABLE `broadcast` MODIFY COLUMN `name` varchar(64) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        self::write("ALTER TABLE `broadcast` MODIFY COLUMN `description` varchar(256) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        self::write("ALTER TABLE `broadcast` MODIFY COLUMN `key` varchar(32) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        self::write("ALTER TABLE `cache_object_count` MODIFY COLUMN `object_type` enum('album','artist','song','playlist','genre','catalog','live_stream','video','podcast_episode') CHARACTER SET $target_charset COLLATE $target_collation NOT NULL;");
        self::write("ALTER TABLE `cache_object_count` MODIFY COLUMN `count_type` varchar(16) CHARACTER SET $target_charset COLLATE $target_collation NOT NULL;");
        self::write("ALTER TABLE `cache_object_count_run` MODIFY COLUMN `object_type` enum('album','artist','song','playlist','genre','catalog','live_stream','video','podcast_episode') CHARACTER SET $target_charset COLLATE $target_collation NOT NULL;");
        self::write("ALTER TABLE `cache_object_count_run` MODIFY COLUMN `count_type` varchar(16) CHARACTER SET $target_charset COLLATE $target_collation NOT NULL;");
        self::write("ALTER TABLE `catalog` MODIFY COLUMN `name` varchar(128) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        self::write("ALTER TABLE `catalog` MODIFY COLUMN `catalog_type` varchar(128) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        self::write("ALTER TABLE `catalog` MODIFY COLUMN `rename_pattern` varchar(255) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        self::write("ALTER TABLE `catalog` MODIFY COLUMN `sort_pattern` varchar(255) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        self::write("ALTER TABLE `catalog` MODIFY COLUMN `gather_types` varchar(255) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        self::write("ALTER TABLE `catalog_local` MODIFY COLUMN `path` varchar(255) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        self::write("ALTER TABLE `catalog_remote` MODIFY COLUMN `uri` varchar(255) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        self::write("ALTER TABLE `catalog_remote` MODIFY COLUMN `username` varchar(255) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        self::write("ALTER TABLE `catalog_remote` MODIFY COLUMN `password` varchar(255) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        self::write("ALTER TABLE `channel` MODIFY COLUMN `name` varchar(64) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        self::write("ALTER TABLE `channel` MODIFY COLUMN `description` varchar(256) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        self::write("ALTER TABLE `channel` MODIFY COLUMN `url` varchar(256) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        self::write("ALTER TABLE `channel` MODIFY COLUMN `interface` varchar(64) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        self::write("ALTER TABLE `channel` MODIFY COLUMN `object_type` varchar(32) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        self::write("ALTER TABLE `channel` MODIFY COLUMN `admin_password` varchar(20) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        self::write("ALTER TABLE `channel` MODIFY COLUMN `stream_type` varchar(8) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        self::write("ALTER TABLE `democratic` MODIFY COLUMN `name` varchar(64) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        self::write("ALTER TABLE `image` MODIFY COLUMN `mime` varchar(64) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        self::write("ALTER TABLE `image` MODIFY COLUMN `size` varchar(64) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        self::write("ALTER TABLE `image` MODIFY COLUMN `object_type` varchar(64) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        self::write("ALTER TABLE `image` MODIFY COLUMN `kind` varchar(32) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        self::write("ALTER TABLE `ip_history` MODIFY COLUMN `agent` varchar(255) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        self::write("ALTER TABLE `label` MODIFY COLUMN `name` varchar(80) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        self::write("ALTER TABLE `label` MODIFY COLUMN `category` varchar(40) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        self::write("ALTER TABLE `label` MODIFY COLUMN `summary` text CHARACTER SET $target_charset COLLATE $target_collation;");
        self::write("ALTER TABLE `label` MODIFY COLUMN `address` varchar(256) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        self::write("ALTER TABLE `label` MODIFY COLUMN `email` varchar(128) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        self::write("ALTER TABLE `label` MODIFY COLUMN `website` varchar(256) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        self::write("ALTER TABLE `license` MODIFY COLUMN `name` varchar(80) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        self::write("ALTER TABLE `license` MODIFY COLUMN `description` varchar(256) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        self::write("ALTER TABLE `license` MODIFY COLUMN `external_link` varchar(256) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        self::write("ALTER TABLE `live_stream` MODIFY COLUMN `name` varchar(128) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        self::write("ALTER TABLE `live_stream` MODIFY COLUMN `site_url` varchar(255) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        self::write("ALTER TABLE `live_stream` MODIFY COLUMN `url` varchar(4096) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        self::write("ALTER TABLE `live_stream` MODIFY COLUMN `codec` varchar(32) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        self::write("ALTER TABLE `localplay_httpq` MODIFY COLUMN `name` varchar(128) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        self::write("ALTER TABLE `localplay_httpq` MODIFY COLUMN `host` varchar(255) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        self::write("ALTER TABLE `localplay_httpq` MODIFY COLUMN `password` varchar(255) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        self::write("ALTER TABLE `localplay_mpd` MODIFY COLUMN `name` varchar(128) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        self::write("ALTER TABLE `localplay_mpd` MODIFY COLUMN `host` varchar(255) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        self::write("ALTER TABLE `localplay_mpd` MODIFY COLUMN `password` varchar(255) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        self::write("ALTER TABLE `metadata` MODIFY COLUMN `type` varchar(50) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        self::write("ALTER TABLE `metadata_field` MODIFY COLUMN `name` varchar(255) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        self::write("ALTER TABLE `movie` MODIFY COLUMN `original_name` varchar(80) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        self::write("ALTER TABLE `movie` MODIFY COLUMN `summary` varchar(256) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        self::write("ALTER TABLE `movie` MODIFY COLUMN `prefix` varchar(32) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        self::write("ALTER TABLE `now_playing` MODIFY COLUMN `id` varchar(64) CHARACTER SET $target_charset COLLATE $target_collation NOT NULL;");
        self::write("ALTER TABLE `now_playing` MODIFY COLUMN `object_type` varchar(255) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        self::write("ALTER TABLE `object_count` MODIFY COLUMN `object_type` enum('album','artist','song','playlist','genre','catalog','live_stream','video','podcast_episode') CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        self::write("ALTER TABLE `object_count` MODIFY COLUMN `agent` varchar(255) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        self::write("ALTER TABLE `object_count` MODIFY COLUMN `geo_name` varchar(255) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        self::write("ALTER TABLE `object_count` MODIFY COLUMN `count_type` varchar(16) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        self::write("ALTER TABLE `personal_video` MODIFY COLUMN `location` varchar(256) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        self::write("ALTER TABLE `personal_video` MODIFY COLUMN `summary` varchar(256) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        self::write("ALTER TABLE `player_control` MODIFY COLUMN `cmd` varchar(32) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        self::write("ALTER TABLE `player_control` MODIFY COLUMN `value` varchar(256) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        self::write("ALTER TABLE `player_control` MODIFY COLUMN `object_type` varchar(32) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        self::write("ALTER TABLE `playlist` MODIFY COLUMN `name` varchar(128) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        self::write("ALTER TABLE `playlist` MODIFY COLUMN `type` enum('private','public') CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        self::write("ALTER TABLE `playlist_data` MODIFY COLUMN `object_type` varchar(32) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        self::write("ALTER TABLE `podcast` MODIFY COLUMN `feed` varchar(4096) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        self::write("ALTER TABLE `podcast` MODIFY COLUMN `title` varchar(255) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        self::write("ALTER TABLE `podcast` MODIFY COLUMN `website` varchar(255) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        self::write("ALTER TABLE `podcast` MODIFY COLUMN `description` varchar(4096) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        self::write("ALTER TABLE `podcast` MODIFY COLUMN `language` varchar(5) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        self::write("ALTER TABLE `podcast` MODIFY COLUMN `copyright` varchar(255) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        self::write("ALTER TABLE `podcast` MODIFY COLUMN `generator` varchar(64) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        self::write("ALTER TABLE `podcast_episode` MODIFY COLUMN `title` varchar(255) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        self::write("ALTER TABLE `podcast_episode` MODIFY COLUMN `guid` varchar(255) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        self::write("ALTER TABLE `podcast_episode` MODIFY COLUMN `state` varchar(32) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        self::write("ALTER TABLE `podcast_episode` MODIFY COLUMN `file` varchar(4096) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        self::write("ALTER TABLE `podcast_episode` MODIFY COLUMN `source` varchar(4096) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        self::write("ALTER TABLE `podcast_episode` MODIFY COLUMN `website` varchar(255) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        self::write("ALTER TABLE `podcast_episode` MODIFY COLUMN `description` varchar(4096) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        self::write("ALTER TABLE `podcast_episode` MODIFY COLUMN `author` varchar(64) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        self::write("ALTER TABLE `podcast_episode` MODIFY COLUMN `category` varchar(64) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        self::write("ALTER TABLE `preference` MODIFY COLUMN `name` varchar(128) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        self::write("ALTER TABLE `preference` MODIFY COLUMN `value` varchar(255) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        self::write("ALTER TABLE `preference` MODIFY COLUMN `description` varchar(255) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        self::write("ALTER TABLE `preference` MODIFY COLUMN `type` varchar(128) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        self::write("ALTER TABLE `preference` MODIFY COLUMN `catagory` varchar(128) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        self::write("ALTER TABLE `preference` MODIFY COLUMN `subcatagory` varchar(128) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        self::write("ALTER TABLE `rating` MODIFY COLUMN `object_type` enum('artist','album','song','stream','video','playlist','tvshow','tvshow_season','podcast','podcast_episode') CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        self::write("ALTER TABLE `recommendation` MODIFY COLUMN `object_type` varchar(32) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        self::write("ALTER TABLE `recommendation_item` MODIFY COLUMN `name` varchar(256) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        self::write("ALTER TABLE `recommendation_item` MODIFY COLUMN `rel` varchar(256) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        self::write("ALTER TABLE `recommendation_item` MODIFY COLUMN `mbid` varchar(1369) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        self::write("ALTER TABLE `search` MODIFY COLUMN `type` enum('private','public') CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        self::write("ALTER TABLE `search` MODIFY COLUMN `name` varchar(255) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        self::write("ALTER TABLE `search` MODIFY COLUMN `logic_operator` varchar(3) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        self::write("ALTER TABLE `session` MODIFY COLUMN `id` varchar(256) CHARACTER SET $target_charset COLLATE $target_collation NOT NULL;");
        self::write("ALTER TABLE `session` MODIFY COLUMN `username` varchar(255) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        self::write("ALTER TABLE `session` MODIFY COLUMN `type` varchar(16) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        self::write("ALTER TABLE `session` MODIFY COLUMN `agent` varchar(255) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        self::write("ALTER TABLE `session` MODIFY COLUMN `geo_name` varchar(255) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        self::write("ALTER TABLE `session_remember` MODIFY COLUMN `username` varchar(255) CHARACTER SET $target_charset COLLATE $target_collation NOT NULL;");
        self::write("ALTER TABLE `session_remember` MODIFY COLUMN `token` varchar(32) CHARACTER SET $target_charset COLLATE $target_collation NOT NULL;");
        self::write("ALTER TABLE `session_stream` MODIFY COLUMN `id` varchar(64) CHARACTER SET $target_charset COLLATE $target_collation NOT NULL;");
        self::write("ALTER TABLE `session_stream` MODIFY COLUMN `agent` varchar(255) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        self::write("ALTER TABLE `share` MODIFY COLUMN `object_type` varchar(32) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        self::write("ALTER TABLE `share` MODIFY COLUMN `secret` varchar(20) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        self::write("ALTER TABLE `share` MODIFY COLUMN `public_url` varchar(255) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        self::write("ALTER TABLE `share` MODIFY COLUMN `description` varchar(255) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        self::write("ALTER TABLE `song` MODIFY COLUMN `file` varchar(4096) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        self::write("ALTER TABLE `song` MODIFY COLUMN `title` varchar(255) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        self::write("ALTER TABLE `song` MODIFY COLUMN `mode` enum('abr','vbr','cbr') CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        self::write("ALTER TABLE `song` MODIFY COLUMN `mbid` varchar(36) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        self::write("ALTER TABLE `song` MODIFY COLUMN `composer` varchar(256) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        self::write("ALTER TABLE `song_data` MODIFY COLUMN `label` varchar(128) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        self::write("ALTER TABLE `song_data` MODIFY COLUMN `language` varchar(128) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        self::write("ALTER TABLE `song_preview` MODIFY COLUMN `session` varchar(256) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        self::write("ALTER TABLE `song_preview` MODIFY COLUMN `artist_mbid` varchar(1369) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        self::write("ALTER TABLE `song_preview` MODIFY COLUMN `title` varchar(255) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        self::write("ALTER TABLE `song_preview` MODIFY COLUMN `album_mbid` varchar(36) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        self::write("ALTER TABLE `song_preview` MODIFY COLUMN `mbid` varchar(36) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        self::write("ALTER TABLE `song_preview` MODIFY COLUMN `file` varchar(255) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        self::write("ALTER TABLE `stream_playlist` MODIFY COLUMN `sid` varchar(256) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        self::write("ALTER TABLE `stream_playlist` MODIFY COLUMN `title` varchar(255) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        self::write("ALTER TABLE `stream_playlist` MODIFY COLUMN `author` varchar(255) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        self::write("ALTER TABLE `stream_playlist` MODIFY COLUMN `album` varchar(255) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        self::write("ALTER TABLE `stream_playlist` MODIFY COLUMN `type` varchar(255) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        self::write("ALTER TABLE `stream_playlist` MODIFY COLUMN `codec` varchar(32) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        self::write("ALTER TABLE `tag` MODIFY COLUMN `name` varchar(255) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        self::write("ALTER TABLE `tag_map` MODIFY COLUMN `object_type` varchar(16) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        self::write("ALTER TABLE `tmp_browse` MODIFY COLUMN `sid` varchar(128) CHARACTER SET $target_charset COLLATE $target_collation NOT NULL;");
        self::write("ALTER TABLE `tmp_playlist` MODIFY COLUMN `session` varchar(256) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        self::write("ALTER TABLE `tmp_playlist` MODIFY COLUMN `type` varchar(32) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        self::write("ALTER TABLE `tmp_playlist` MODIFY COLUMN `object_type` varchar(32) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        self::write("ALTER TABLE `tmp_playlist_data` MODIFY COLUMN `object_type` varchar(32) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        self::write("ALTER TABLE `tvshow` MODIFY COLUMN `name` varchar(80) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        self::write("ALTER TABLE `tvshow` MODIFY COLUMN `summary` varchar(256) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        self::write("ALTER TABLE `tvshow` MODIFY COLUMN `prefix` varchar(32) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        self::write("ALTER TABLE `tvshow_episode` MODIFY COLUMN `original_name` varchar(80) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        self::write("ALTER TABLE `tvshow_episode` MODIFY COLUMN `summary` varchar(256) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        self::write("ALTER TABLE `update_info` MODIFY COLUMN `key` varchar(128) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        self::write("ALTER TABLE `update_info` MODIFY COLUMN `value` varchar(255) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        self::write("ALTER TABLE `user` MODIFY COLUMN `username` varchar(255) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        self::write("ALTER TABLE `user` MODIFY COLUMN `fullname` varchar(255) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        self::write("ALTER TABLE `user` MODIFY COLUMN `email` varchar(128) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        self::write("ALTER TABLE `user` MODIFY COLUMN `website` varchar(255) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        self::write("ALTER TABLE `user` MODIFY COLUMN `apikey` varchar(255) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        self::write("ALTER TABLE `user` MODIFY COLUMN `password` varchar(64) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        self::write("ALTER TABLE `user` MODIFY COLUMN `validation` varchar(128) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        self::write("ALTER TABLE `user` MODIFY COLUMN `state` varchar(64) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        self::write("ALTER TABLE `user` MODIFY COLUMN `city` varchar(64) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        self::write("ALTER TABLE `user` MODIFY COLUMN `rsstoken` varchar(255) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        self::write("ALTER TABLE `user_activity` MODIFY COLUMN `action` varchar(20) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        self::write("ALTER TABLE `user_activity` MODIFY COLUMN `object_type` varchar(32) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        self::write("ALTER TABLE `user_activity` MODIFY COLUMN `name_track` varchar(255) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        self::write("ALTER TABLE `user_activity` MODIFY COLUMN `name_artist` varchar(255) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        self::write("ALTER TABLE `user_activity` MODIFY COLUMN `name_album` varchar(255) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        self::write("ALTER TABLE `user_activity` MODIFY COLUMN `mbid_track` varchar(255) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        self::write("ALTER TABLE `user_activity` MODIFY COLUMN `mbid_artist` varchar(255) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        self::write("ALTER TABLE `user_activity` MODIFY COLUMN `mbid_album` varchar(255) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        self::write("ALTER TABLE `user_flag` MODIFY COLUMN `object_type` varchar(32) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        self::write("ALTER TABLE `user_preference` MODIFY COLUMN `value` varchar(255) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        self::write("ALTER TABLE `user_pvmsg` MODIFY COLUMN `subject` varchar(80) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        self::write("ALTER TABLE `user_pvmsg` MODIFY COLUMN `message` text CHARACTER SET $target_charset COLLATE $target_collation;");
        self::write("ALTER TABLE `user_shout` MODIFY COLUMN `object_type` varchar(32) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        self::write("ALTER TABLE `user_shout` MODIFY COLUMN `data` varchar(256) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        self::write("ALTER TABLE `user_vote` MODIFY COLUMN `sid` varchar(256) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        self::write("ALTER TABLE `video` MODIFY COLUMN `file` varchar(4096) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        self::write("ALTER TABLE `video` MODIFY COLUMN `title` varchar(255) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        self::write("ALTER TABLE `video` MODIFY COLUMN `video_codec` varchar(255) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        self::write("ALTER TABLE `video` MODIFY COLUMN `audio_codec` varchar(255) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        self::write("ALTER TABLE `video` MODIFY COLUMN `mime` varchar(255) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        self::write("ALTER TABLE `video` MODIFY COLUMN `mode` enum('abr','vbr','cbr') CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        self::write("ALTER TABLE `wanted` MODIFY COLUMN `artist_mbid` varchar(1369) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        self::write("ALTER TABLE `wanted` MODIFY COLUMN `mbid` varchar(36) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        self::write("ALTER TABLE `wanted` MODIFY COLUMN `name` varchar(255) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
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
} // end dba.class
