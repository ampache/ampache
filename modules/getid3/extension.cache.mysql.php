<?php
// +----------------------------------------------------------------------+
// | PHP version 5                                                        |
// +----------------------------------------------------------------------+
// | Copyright (c) 2002-2006 James Heinrich, Allan Hansen                 |
// +----------------------------------------------------------------------+
// | This source file is subject to version 2 of the GPL license,         |
// | that is bundled with this package in the file license.txt and is     |
// | available through the world-wide-web at the following url:           |
// | http://www.gnu.org/copyleft/gpl.html                                 |
// +----------------------------------------------------------------------+
// | getID3() - http://getid3.sourceforge.net or http://www.getid3.org    |
// +----------------------------------------------------------------------+
// | Authors: James Heinrich <infoØgetid3*org>                            |
// |          Allan Hansen <ahØartemis*dk>                                |
// +----------------------------------------------------------------------+
// | extension.cache.mysql.php                                            |
// | MySQL Cache Extension.                                               |
// | dependencies: getid3.                                                |
// +----------------------------------------------------------------------+
//
// $Id: extension.cache.mysql.php,v 1.2 2006/11/02 10:47:59 ah Exp $


/**
* This is a caching extension for getID3(). It works the exact same
* way as the getID3 class, but return cached information very fast
*
* Example:  (see also demo.cache.mysql.php in /demo/)
*
*    Normal getID3 usage (example):
*
*       require_once 'getid3/getid3.php';
*       $getid3 = new getid3;
*       $getid3->encoding = 'UTF-8';
*       try { 
*           $info1 = $getid3->Analyse('file1.flac');
*           $info2 = $getid3->Analyse('file2.wv');
*           ....
*
*    getID3_cached usage:
*
*       require_once 'getid3/getid3.php';
*       require_once 'getid3/getid3/extension.cache.mysql.php';
*       $getid3 = new getid3_cached_mysql('localhost', 'database', 'username', 'password');
*       $getid3->encoding = 'UTF-8';
*       try {
*           $info1 = $getid3->analyse('file1.flac');
*           $info2 = $getid3->analyse('file2.wv');
*           ...
*
*
* Supported Cache Types    (this extension)
*
*   SQL Databases:
*
*   cache_type          cache_options
*   -------------------------------------------------------------------
*   mysql               host, database, username, password
*
*
*   DBM-Style Databases:    (use extension.cache.dbm)
*
*   cache_type          cache_options
*   -------------------------------------------------------------------
*   gdbm                dbm_filename, lock_filename
*   ndbm                dbm_filename, lock_filename
*   db2                 dbm_filename, lock_filename
*   db3                 dbm_filename, lock_filename
*   db4                 dbm_filename, lock_filename  (PHP5 required)
*
*   PHP must have write access to both dbm_filename and lock_filename.
*
*
* Recommended Cache Types
*
*   Infrequent updates, many reads      any DBM
*   Frequent updates                    mysql
*/


class getid3_cached_mysql extends getID3
{

    private $cursor;
    private $connection;


    public function __construct($host, $database, $username, $password) {

        // Check for mysql support
        if (!function_exists('mysql_pconnect')) {
            throw new getid3_exception('PHP not compiled with mysql support.');
        }

        // Connect to database
        $this->connection = @mysql_pconnect($host, $username, $password);
        if (!$this->connection) {
            throw new getid3_exception('mysql_pconnect() failed - check permissions and spelling.');
        }

        // Select database
        if (!@mysql_select_db($database, $this->connection)) {
            throw new getid3_exception('Cannot use database '.$database);
        }

        // Create cache table if not exists
        $this->create_table();

        // Check version number and clear cache if changed
        $this->cursor = mysql_query("SELECT `value` FROM `getid3_cache` WHERE (`filename` = '".getid3::VERSION."') AND (`filesize` = '-1') AND (`filetime` = '-1') AND (`analyzetime` = '-1')", $this->connection);
        list($version) = @mysql_fetch_array($this->cursor);
        if ($version != getid3::VERSION) {
            $this->clear_cache();
        }

        parent::__construct();
    }



    public function clear_cache() {

        $this->cursor = mysql_query("DELETE FROM `getid3_cache`", $this->connection);
        $this->cursor = mysql_query("INSERT INTO `getid3_cache` VALUES ('".getid3::VERSION."', -1, -1, -1, '".getid3::VERSION."')", $this->connection);
    }



    public function Analyze($filename) {

        if (file_exists($filename)) {

            // Short-hands
            $filetime = filemtime($filename);
            $filesize = filesize($filename);
            $filenam2 = mysql_escape_string($filename);

            // Loopup file
            $this->cursor = mysql_query("SELECT `value` FROM `getid3_cache` WHERE (`filename`='".$filenam2."') AND (`filesize`='".$filesize."') AND (`filetime`='".$filetime."')", $this->connection);
            list($result) = @mysql_fetch_array($this->cursor);

            // Hit
            if ($result) {
                return unserialize($result);
            }
        }

        // Miss
        $result = parent::Analyze($filename);

        // Save result
        if (file_exists($filename)) {
            $res2 = mysql_escape_string(serialize($result));
            $this->cursor = mysql_query("INSERT INTO `getid3_cache` (`filename`, `filesize`, `filetime`, `analyzetime`, `value`) VALUES ('".$filenam2."', '".$filesize."', '".$filetime."', '".time()."', '".$res2."')", $this->connection);
        }
        return $result;
    }



    // (re)create sql table
    private function create_table($drop = false) {

        $this->cursor = mysql_query("CREATE TABLE IF NOT EXISTS `getid3_cache` (
            `filename` VARCHAR(255) NOT NULL DEFAULT '',
            `filesize` INT(11) NOT NULL DEFAULT '0',
            `filetime` INT(11) NOT NULL DEFAULT '0',
            `analyzetime` INT(11) NOT NULL DEFAULT '0',
            `value` TEXT NOT NULL,
            PRIMARY KEY (`filename`,`filesize`,`filetime`)) TYPE=MyISAM", $this->connection);
        echo mysql_error($this->connection);
    }
}


?>