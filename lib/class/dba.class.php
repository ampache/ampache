<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU General Public License, version 2 (GPLv2)
 * Copyright 2001 - 2013 Ampache.org
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License v2
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 *
 */

/* Make sure they aren't directly accessing it */
if (!defined('INIT_LOADED') || INIT_LOADED != '1') { exit; }

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
class Dba {

    public static $stats = array('query'=>0);

    private static $_sql;
    private static $config;

    /**
     * constructor
     * This does nothing with the DBA class
     */
    private function __construct() {

        // Rien a faire

    } // construct

    /**
     * query
     * This is the meat of the class this does a query, it emulates
     * The mysql_query function
     */
    public static function query($sql) {

        // Run the query
        $resource = mysql_query($sql,self::dbh());
        debug_event('Query',$sql,'6');

        // Save the query, to make debug easier
        self::$_sql = $sql;
        self::$stats['query']++;

        // Do a little error checking here and try to recover from some forms of failure
        if (!$resource) {
            switch (mysql_errno(self::dbh())) {
                case '2006':
                case '2013':
                case '2055':
                    debug_event('DBH','Lost connection to database server, trying to re-connect and hope nobody noticed','1');
                    self::disconnect();
                    // Try again
                    $resource = mysql_query($sql,self::dbh());
                    break;
                default:
                    debug_event('DBH',mysql_error(self::dbh()) . ' ['. mysql_errno(self::dbh()) . ']','1');
                    break;
            } // end switch on error #
        } // if failed query

        return $resource;

    } // query

    /**
     * read
     * This is a wrapper for query, it's so that in the future if we ever wanted
     * to split reads and writes we could
     */
    public static function read($sql) {

        return self::query($sql);

    } // read

    /**
     * write
     * This is a wrapper for a write query, it is so that we can split out reads and
     * writes if we want to
     */
    public static function write($sql) {

        return self::query($sql);

    } // write

    /**
     * escape
     * This runs a escape on a variable so that it can be safely inserted
     * into the sql
     */
    public static function escape($var) {

        $string = mysql_real_escape_string($var,self::dbh());

        return $string;

    } // escape

    /**
     * fetch_assoc
     * This emulates the mysql_fetch_assoc and takes a resource result
     * we force it to always return an array, albeit an empty one
     * The optional finish parameter affects whether we automatically clean
     * up the result set after the last row is read.
     */
    public static function fetch_assoc($resource, $finish = true) {

        $result = mysql_fetch_assoc($resource);

        if (!$result) {
            if ($finish) {
                self::finish($resource);
            }
            return array();
        }

        return $result;

    } // fetch_assoc

    /**
     * fetch_row
     * This emulates the mysql_fetch_row and takes a resource result
     * we force it to always return an array, albeit an empty one
     * The optional finish parameter affects whether we automatically clean
     * up the result set after the last row is read.
     */
    public static function fetch_row($resource, $finish = true) {

        $result = mysql_fetch_row($resource);

        if (!$result) {
            if ($finish) {
                self::finish($resource);
            }
            return array();
        }

        return $result;

    } // fetch_row

    /**
     * num_rows
     * This emulates the mysql_num_rows function which is really
     * just a count of rows returned by our select statement, this
     * doesn't work for updates or inserts
     */
    public static function num_rows($resource) {
        if ($resource) {
            $result = mysql_num_rows($resource);
            if ($result) {
                return $result;
            }
        }

        return 0;
    } // num_rows

    /**
     * seek
     * This resets the row pointer to the specified position
     */
    public static function seek($resource, $row) {
        return mysql_data_seek($resource, $row);
    }

    /**
     * finish
     * This closes a result handle and clears the memory associated with it
     */
    public static function finish($resource) {

        // Clear the result memory
        mysql_free_result($resource);

    } // finish

    /**
     * affected_rows
     * This emulates the mysql_affected_rows function
     */
    public static function affected_rows($resource) {

        $result = mysql_affected_rows($resource);

        if (!$result) {
            return '0';
        }

        return $result;

    } // affected_rows

    /**
     * _connect
     * This connects to the database, used by the DBH function
     */
    private static function _connect() {

        $username = Config::get('database_username');
        $hostname = Config::get('database_hostname');
        $password = Config::get('database_password');

        $dbh = mysql_connect($hostname, $username, $password);
        if (!$dbh) {
            debug_event('Database', 'Unable to connect to database: ' . mysql_error(), 1);
            return null;
        }

        return $dbh;
    } // _connect

    private static function _setup_dbh($dbh, $database) {
        $data = self::translate_to_mysqlcharset(Config::get('site_charset'));

        if (function_exists('mysql_set_charset')) {
            if (!$charset = mysql_set_charset($data['charset'], $dbh)) {
                debug_event('Database', 'Unable to set MySQL connection charset to ' . $data['charset'] . ', this may cause issues...', 1);
            }
        }
        else {
            $sql = "SET NAMES " . mysql_real_escape_string($data['charset']);
            $charset = mysql_query($sql,$dbh);
            if ($error = mysql_error($dbh)) {
                debug_event('Database', 'Unable to set MySQL connection charset to ' . $data['charset'] . ' using SET NAMES, this may cause issues: ' . $error, 1);
            }

        }

        $select_db = mysql_select_db($database, $dbh);
        if (!$select_db) {
            debug_event('Database', 'Unable to select database ' . $database . ': ' . mysql_error(), 1);
        }

        if (Config::get('sql_profiling')) {
            mysql_query('set profiling=1', $dbh);
            mysql_query('set profiling_history_size=50', $dbh);
            mysql_query('set query_cache_type=0', $dbh);
        }
    } // _select_db

    /**
     * check_database
     *
     * Make sure that we can connect to the database
     */
    public static function check_database() {

        $dbh = self::_connect();

        if (!is_resource($dbh)) {
            return false;
        }

        mysql_close($dbh);
        return true;

    } // check_database

    public static function check_database_exists() {
        $dbh = self::_connect();
        $select = mysql_select_db(Config::get('database_name'), $dbh);
        mysql_close($dbh);
        return $select;
    }

    /**
     * check_database_inserted
     * checks to make sure that you have inserted the database
     * and that the user you are using has access to it
     */
    public static function check_database_inserted() {

        $sql = "DESCRIBE session";
        $db_results = Dba::read($sql);

        if (!$db_results) {
            return false;
        }

        // Make sure the whole table is there
        if (Dba::num_rows($db_results) != '7') {
            return false;
        }

        return true;

    } // check_database_inserted

    public static function get_client_info() {
        return mysql_get_client_info();
    }

    /**
     * show_profile
     * This function is used for debug, helps with profiling
     */
    public static function show_profile() {

        if (Config::get('sql_profiling')) {
            print '<br/>Profiling data: <br/>';
            $res = Dba::read('show profiles');
            print '<table>';
            while ($r = Dba::fetch_row($res)) {
                print '<tr><td>' . implode('</td><td>', $r) . '</td></tr>';
            }
            print '</table>';
        }
    } // show_profile

    /**
     * dbh
     * This is called by the class to return the database handle
     * for the specified database, if none is found it connects
     */
    public static function dbh($database='') {

        if (!$database) {
            $database = Config::get('database_name');
        }

        // Assign the Handle name that we are going to store
        $handle = 'dbh_' . $database;

        if (!is_resource(Config::get($handle))) {
            $dbh = self::_connect();
            self::_setup_dbh($dbh, $database);
            Config::set($handle, $dbh, true);
            return $dbh;
        }
        else {
            return Config::get($handle);
        }


    } // dbh

    /**
     * disconnect
     * This nukes the dbh connection based, this isn't used very often...
     */
    public static function disconnect($database='') {

        if (!$database) {
            $database = Config::get('database_name'); 
        }

        $handle = 'dbh_' . $database;

        // Try to close it correctly
        mysql_close(Config::get($handle));

        // Nuke it
        Config::set($handle, false, true);

        return true;

    } // disconnect

    /**
     * insert_id
     * This emulates the mysql_insert_id function, it takes
     * an optional database target
     */
    public static function insert_id() {

        $id = mysql_insert_id(self::dbh());
        return $id;

    } // insert_id

    /**
     * error
     * this returns the error of the db
     */
    public static function error() {

        return mysql_error();

    } // error

    /**
     * translate_to_mysqlcharset
     * This translates the specified charset to a mysqlcharset, stupid ass mysql
     * demands that it's charset list is different!
     */
    public static function translate_to_mysqlcharset($charset) {

        // MySQL translte real charset names into fancy smancy MySQL land names
        switch (strtoupper($charset)) {
            case 'CP1250':
            case 'WINDOWS-1250':
                $target_charset = 'cp1250';
                $target_collation = 'cp1250_general_ci';
                break;
            case 'ISO-8859':
            case 'ISO-8859-2':
                $target_charset = 'latin2';
                $target_collation = 'latin2_general_ci';
                break;
            case 'ISO-8859-1':
            case 'CP1252':
            case 'WINDOWS-1252':
                $target_charset = 'latin1';
                $target_collation = 'latin1_general_ci';
                break;
            case 'EUC-KR':
                $target_charset = 'euckr';
                $target_collation = 'euckr_korean_ci';
                break;
            case 'CP932':
                $target_charset = 'sjis';
                $target_collation = 'sjis_japanese_ci';
                break;
            case 'KOI8-U':
                $target_charset = 'koi8u';
                $target_collation = 'koi8u_general_ci';
                break;
            case 'KOI8-R':
                $target_charset = 'koi8r';
                $target_collation = 'koi8r_general_ci';
                break;
            default;
            case 'UTF-8':
                $target_charset = 'utf8';
                $target_collation = 'utf8_unicode_ci';
                break;
        } // end mysql charset translation

        return array('charset'=>$target_charset,'collation'=>$target_collation);

    } // translate_to_mysqlcharset

    /**
     * reset_db_charset
     * This cruises through the database and trys to set the charset to the current
     * site charset, this is an admin function that can be run by an administrator
     * this can mess up data if you switch between charsets that are not overlapping
     * a catalog verify must be re-run to correct them
     */
    public static function reset_db_charset() {

        $translated_charset = self::translate_to_mysqlcharset(Config::get('site_charset'));
        $target_charset = $translated_charset['charset'];
        $target_collation = $translated_charset['collation'];

        // Alter the charset for the entire database
        $sql = "ALTER DATABASE `" . Config::get('database_name') . "` DEFAULT CHARACTER SET $target_charset COLLATE $target_collation";
        $db_results = Dba::write($sql);

        $sql = "SHOW TABLES";
        $db_results = Dba::read($sql);

        // Go through the tables!
        while ($row = Dba::fetch_row($db_results)) {
            $sql = "DESCRIBE `" . $row['0'] . "`";
            $describe_results = Dba::read($sql);

            // Change the tables default charset and colliation
            $sql = "ALTER TABLE `" . $row['0'] . "`  DEFAULT CHARACTER SET $target_charset COLLATE $target_collation";
            $alter_table = Dba::write($sql);

            // Iterate through the columns of the table
            while ($table = Dba::fetch_assoc($describe_results)) {
                if (
                (strpos($table['Type'], 'varchar') !== false) ||
                (strpos($table['Type'], 'enum') !== false) ||
                (strpos($table['Table'],'text') !== false)) {
                    $sql = "ALTER TABLE `" . $row['0'] . "` MODIFY `" . $table['Field'] . "` " . $table['Type'] . " CHARACTER SET " . $target_charset;
                    $charset_results = Dba::write($sql);
                    if (!$charset_results) {
                        debug_event('CHARSET','Unable to update the charset of ' . $table['Field'] . '.' . $table['Type'] . ' to ' . $target_charset,'3');
                    } // if it fails
                } // if its a varchar
            } // end columns

        } // end tables


    } // reset_db_charset

    /**
     * optimize_tables
     *
     * This runs an optimize on the tables and updates the stats to improve
     * join speed.
     * This can be slow, but is a good idea to do from time to time. We do 
     * it in case the dba isn't doing it... which we're going to assume they
     * aren't.
     */
    public static function optimize_tables() {
        $sql = "SHOW TABLES";
        $db_results = Dba::read($sql);

        while($row = Dba::fetch_row($db_results)) {
            $sql = "OPTIMIZE TABLE `" . $row[0] . "`";
            $db_results_inner = Dba::write($sql);

            $sql = "ANALYZE TABLE `" . $row[0] . "`";
            $db_results_inner = Dba::write($sql);
        }
    }

} // dba class

?>
