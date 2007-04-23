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
// $Id: extension.cache.dbm.php,v 1.2 2006/11/02 10:47:59 ah Exp $


/**
* This is a caching extension for getID3(). It works the exact same
* way as the getID3 class, but return cached information very fast
*
* Example:  (see also demo.cache.dbm.php in /demo/)
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
* Supported Cache Types
*
*   SQL Databases:          (use extension.cache.mysql)
*
*   cache_type          cache_options
*   -------------------------------------------------------------------
*   mysql               host, database, username, password
*
*
*   DBM-Style Databases:    (this extension)
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


class getid3_cached_dbm extends getid3
{

    public function __construct($cache_type, $dbm_filename, $lock_filename) {

        // Check for dba extension
        if (!extension_loaded('dba')) {
            throw new getid3_exception('PHP is not compiled with dba support, required to use DBM style cache.');
        }

        if (!in_array($cache_type, dba_handlers())) {
            throw new getid3_exception('PHP is not compiled --with '.$cache_type.' support, required to use DBM style cache.');
        }

        // Create lock file if needed
        if (!file_exists($lock_filename)) {
            if (!touch($lock_filename)) {
                die('failed to create lock file: ' . $lock_filename);
            }
        }

        // Open lock file for writing
        if (!is_writeable($lock_filename)) {
            die('lock file: ' . $lock_filename . ' is not writable');
        }
        $this->lock = fopen($lock_filename, 'w');

        // Acquire exclusive write lock to lock file
        flock($this->lock, LOCK_EX);

        // Create dbm-file if needed
        if (!file_exists($dbm_filename)) {
            if (!touch($dbm_filename)) {
                die('failed to create dbm file: ' . $dbm_filename);
            }
        }

        // Try to open dbm file for writing
        $this->dba = @dba_open($dbm_filename, 'w', $cache_type);
        if (!$this->dba) {

            // Failed - create new dbm file
            $this->dba = dba_open($dbm_filename, 'n', $cache_type);

            if (!$this->dba) {
                die('failed to create dbm file: ' . $dbm_filename);
            }

            // Insert getID3 version number
            dba_insert(getid3::VERSION, getid3::VERSION, $this->dba);
        }

        // Init misc values
        $this->cache_type   = $cache_type;
        $this->dbm_filename = $dbm_filename;

        // Register destructor
        register_shutdown_function(array($this, '__destruct'));

        // Check version number and clear cache if changed
        if (dba_fetch(getid3::VERSION, $this->dba) != getid3::VERSION) {
            $this->clear_cache();
        }

        parent::__construct();
    }



    public function __destruct() {

        // Close dbm file
        @dba_close($this->dba);

        // Release exclusive lock
        @flock($this->lock, LOCK_UN);

        // Close lock file
        @fclose($this->lock);
    }



    public function clear_cache() {

        // Close dbm file
        dba_close($this->dba);

        // Create new dbm file
        $this->dba = dba_open($this->dbm_filename, 'n', $this->cache_type);

        if (!$this->dba) {
            die('failed to clear cache/recreate dbm file: ' . $this->dbm_filename);
        }

        // Insert getID3 version number
        dba_insert(getid3::VERSION, getid3::VERSION, $this->dba);

        // Reregister shutdown function
        register_shutdown_function(array($this, '__destruct'));
    }



    // public: analyze file
    public function Analyze($filename) {

        if (file_exists($filename)) {

            // Calc key     filename::mod_time::size    - should be unique
            $key = $filename . '::' . filemtime($filename) . '::' . filesize($filename);

            // Loopup key
            $result = dba_fetch($key, $this->dba);

            // Hit
            if ($result !== false) {
                return unserialize($result);
            }
        }

        // Miss
        $result = parent::Analyze($filename);

        // Save result
        if (file_exists($filename)) {
            dba_insert($key, serialize($result), $this->dba);
        }

        return $result;
    }

}


?>