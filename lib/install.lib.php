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

/**
 * split_sql
 * splits up a standard SQL dump file into distinct sql queries
 */
function split_sql($sql) {
        $sql = trim($sql);
        $sql = preg_replace("/\n#[^\n]*\n/", "\n", $sql);
        $buffer = array();
        $ret = array();
        $in_string = false;
        for($i=0; $i<strlen($sql)-1; $i++) {
                if($sql[$i] == ";" && !$in_string) {
                        $ret[] = substr($sql, 0, $i);
                        $sql = substr($sql, $i + 1);
                        $i = 0;
                }
                if($in_string && ($sql[$i] == $in_string) && $buffer[1] != "\\") {
                        $in_string = false;
                }
                elseif(!$in_string && ($sql[$i] == '"' || $sql[$i] == "'") && (!isset($buffer[0]) || $buffer[0] != "\\")) {
                        $in_string = $sql[$i];
                }
                if(isset($buffer[1])) {
                        $buffer[0] = $buffer[1];
                }
                $buffer[1] = $sql[$i];
        }
        if(!empty($sql)) {
                $ret[] = $sql;
        }
        return($ret);
} // split_sql

/**
 * install_check_status
 * this function checks to see if we actually
 * still need to install ampache. This function is
 * very important, we don't want to reinstall over top of an existing install
 */
function install_check_status($configfile) {

    /*
      Check and see if the config file exists
      if it does they can't use the web interface
      to install ampache.
    */
    if (!file_exists($configfile)) {
        return true;
    } else {
        Error::add('general', T_('Config file already exists, install is probably completed'));
    }

    /*
      Check and see if they've got _any_ account
      if they don't then they're cool
    */
    $results = parse_ini_file($configfile);
    Config::set_by_array($results, true);

    if (!Dba::check_database()) {
        Error::add('general', T_('Unable to connect to database, check your ampache config'));
        return false;
    }

    $sql = 'SELECT * FROM `user`';
    $db_results = Dba::read($sql);

    if (!$db_results) {
        Error::add('general', T_('Unable to query database, check your ampache config'));
        return false;
    }

    if (!Dba::num_rows($db_results)) {
        return true;
    }
    else {
        Error::add('general', T_('Existing Database detected, unable to continue installation'));
        return false;
    }

    /* Defaut to no */
    return false;

} // install_check_status

/**
 * install_insert_db
 *
 * Inserts the database using the values from Config.
 */
function install_insert_db($db_user = null, $db_pass = null, $overwrite = false) {
    $database = Config::get('database_name');
    // Make sure that the database name is valid
    $is_valid = preg_match('/([^\d\w\_\-])/', $database, $matches);

    if (count($matches)) {
        Error::add('general', T_('Error: Invalid database name.'));
        return false;
    }

    if (!Dba::check_database()) {
        Error::add('general', sprintf(T_('Error: Unable to make Database Connection %s'), Dba::error()));
        return false;
    }

    $db_exists = Dba::read('SHOW TABLES');

    if ($db_exists && $_POST['existing_db']) {
        // Rien a faire, we've got the db just blow through
    }
    elseif ($db_exists && !$overwrite) {
        Error::add('general', T_('Error: Database Already exists and Overwrite not checked'));
        return false;
    }
    elseif (!$db_exists) {
        $sql = 'CREATE DATABASE `' . $database . '`';
        if (!Dba::write($sql)) {
            Error::add('general',sprintf(T_('Error: Unable to Create Database %s'), Dba::error()));
            return false;
        }
    } // if db can't be selected
    else {
        $sql = 'DROP DATABASE `' . $database . '`';
        Dba::write($sql);
        $sql = 'CREATE DATABASE `' . $database . '`';
        if (!Dba::write($sql)) {
            Error::add('general', sprintf(T_('Error: Unable to Create Database %s'), Dba::error()));
            return false;
        }
    } // end if selected and overwrite

    Dba::disconnect();

    // Check to see if we should create a user here
    if (strlen($db_user) && strlen($db_pass)) {
		$checkhost = Config::get('database_hostname');
		if (strpos($checkhost, '/') === 0) {
			$sql = 'GRANT ALL PRIVILEGES ON `' . Dba::escape($database) . '`.* TO ' .		
				"'" . Dba::escape($db_user) . "'@'" . localhost . "' IDENTIFIED BY '" . Dba::escape($db_pass) . "' WITH GRANT OPTION";		
		}
        else {
			$sql = 'GRANT ALL PRIVILEGES ON `' . Dba::escape($database) . '`.* TO ' .
				"'" . Dba::escape($db_user) . "'@'" . Dba::escape(Config::get('database_hostname')) . "' IDENTIFIED BY '" . Dba::escape($db_pass) . "' WITH GRANT OPTION";
		}
        if (!Dba::write($sql)) {
            Error::add('general', sprintf(T_('Error: Unable to Insert %1$s with permissions to %2$s on %3$s %4$s'), $db_user, $database, $hostname, Dba::error()));
            return false;
        }
    } // end if we are creating a user

    $sql_file = Config::get('prefix') . '/sql/ampache.sql';

    $query = fread(fopen($sql_file, 'r'), filesize($sql_file));
    $pieces  = split_sql($query);
    for ($i=0; $i<count($pieces); $i++) {
        $pieces[$i] = trim($pieces[$i]);
        if(!empty($pieces[$i]) && $pieces[$i] != '#') {
            if (!$result = Dba::write($pieces[$i])) {
                $errors[] = array ( Dba::error(), $pieces[$i] );
            }
        }
    }

    $sql = 'ALTER DATABASE `' . $database . '` DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci';
    $db_results = Dba::write($sql, array(Config::get('database_name')));

    // If they've picked something other than English update default preferences
    if (Config::get('lang') != 'en_US') {
        // FIXME: 31? I hate magic.
        $sql = 'UPDATE `preference` SET `value`= ? WHERE `id` = 31';
        $db_results = Dba::write($sql, array(Config::get('lang')));
        $sql = 'UPDATE `user_preference` SET `value` = ? WHERE `preference` = 31';
        $db_results = Dba::write($sql, array(Config::get('lang')));
    }

    return true;
}

/**
 * install_create_config
 *
 * Attempts to write out the config file or offer it as a download.
 */
function install_create_config($download = false) {

    $config_file = Config::get('prefix') . '/config/ampache.cfg.php';

    /* Attempt to make DB connection */
    $dbh = Dba::dbh();

    // Connect to the DB
    if(!Dba::check_database()) {
        Error::add('general', T_("Database Connection Failed Check Hostname, Username and Password"));
        return false;
    }

    $final = generate_config(Config::get_all());

    // Make sure the directory is writable OR the empty config file is
    if (!$download) {
        if (!check_config_writable()) {
            Error::add('general', T_('Config file is not writable'));
            return false;
        }
        else {
            // Given that $final is > 0, we can ignore lazy comparison problems
            if (!file_put_contents($config_file, $final)) {
                Error::add('general', T_('Error writing config file'));
                return false;
            }
        }
    }
    else {
        $browser = new Horde_Browser();
        $browser->downloadHeaders('ampache.cfg.php', 'text/plain', false, strlen($final));
        echo $final;
        exit();
    }

    return true;
}

/**
 * install_create_account
 * this creates your initial account and sets up the preferences for the -1 user and you
 */
function install_create_account($username, $password, $password2) {

    if (!strlen($username) OR !strlen($password)) {
        Error::add('general', T_('No Username/Password specified'));
        return false;
    }

    if ($password !== $password2) {
        Error::add('general', T_('Passwords do not match'));
        return false;
    }

    if (!Dba::check_database()) {
        Error::add('general', sprintf(T_('Database Connection Failed: %s'), Dba::error()));
        return false;
    }

    if (!Dba::check_database_inserted()) {
        Error::add('general', sprintf(T_('Database Select Failed: %s'), Dba::error()));
        return false;
    }

    $username = Dba::escape($username);
    $password = Dba::escape($password);

    $insert_id = User::create($username,'Administrator','',$password,'100');

    if (!$insert_id) {
        Error::add('general', sprintf(T_('Insert of Base User Failed %s'), Dba::error()));
        return false;
    }

    // Fix the system users preferences
    User::fix_preferences('-1');

    return true;

} // install_create_account

?>
