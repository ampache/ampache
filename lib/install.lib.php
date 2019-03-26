<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPLv3)
 * Copyright 2001 - 2017 Ampache.org
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

/**
 * split_sql
 * splits up a standard SQL dump file into distinct sql queries
 */
function split_sql($sql)
{
    $sql       = trim($sql);
    $sql       = preg_replace("/\n#[^\n]*\n/", "\n", $sql);
    $buffer    = array();
    $ret       = array();
    $in_string = false;
    for ($i=0; $i < strlen($sql) - 1; $i++) {
        if ($sql[$i] == ";" && !$in_string) {
            $ret[] = substr($sql, 0, $i);
            $sql   = substr($sql, $i + 1);
            $i     = 0;
        }
        if ($in_string && ($sql[$i] == $in_string) && $buffer[1] != "\\") {
            $in_string = false;
        } elseif (!$in_string && ($sql[$i] == '"' || $sql[$i] == "'") && (!isset($buffer[0]) || $buffer[0] != "\\")) {
            $in_string = $sql[$i];
        }
        if (isset($buffer[1])) {
            $buffer[0] = $buffer[1];
        }
        $buffer[1] = $sql[$i];
    }
    if (!empty($sql)) {
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
function install_check_status($configfile)
{
    /*
      Check and see if the config file exists
      if it does they can't use the web interface
      to install ampache.
    */
    if (!file_exists($configfile)) {
        return true;
    } else {
        //AmpError::add('general', T_('Config file already exists, install is probably completed'));
    }

    /*
      Check and see if they've got _any_ account
      if they don't then they're cool
    */
    $results = parse_ini_file($configfile);
    AmpConfig::set_by_array($results, true);

    if (!Dba::check_database()) {
        AmpError::add('general', T_('Unable to connect to database, check your ampache config'));

        return false;
    }

    $sql        = 'SELECT * FROM `user`';
    $db_results = Dba::read($sql);

    if (!$db_results) {
        AmpError::add('general', T_('Unable to query database, check your ampache config'));

        return false;
    }

    if (!Dba::num_rows($db_results)) {
        return true;
    } else {
        AmpError::add('general', T_('Existing Database detected, unable to continue installation'));

        return false;
    }
} // install_check_status

function install_check_server_apache()
{
    return (strpos($_SERVER['SERVER_SOFTWARE'], "Apache/") === 0);
}

function install_check_rewrite_rules($file, $web_path, $fix = false)
{
    if (!is_readable($file)) {
        $file .= '.dist';
    }
    $valid     = true;
    $htaccess  = file_get_contents($file);
    $new_lines = array();
    $lines     = explode("\n", $htaccess);
    foreach ($lines as $line) {
        $parts = explode(' ', $line);
        for ($i = 0; $i < count($parts); $i++) {
            // Matching url rewriting rule syntax
            if ($parts[$i] == 'RewriteRule' && $i < (count($parts) - 2)) {
                $reprule = $parts[$i + 2];
                if (!empty($web_path) && strpos($reprule, $web_path) !== 0) {
                    $reprule = $web_path . $reprule;
                    if ($fix) {
                        $parts[$i + 2] = $reprule;
                        $line          = implode(' ', $parts);
                    } else {
                        $valid = false;
                    }
                }
                break;
            }
        }

        if ($fix) {
            $new_lines[] = $line;
        }
    }

    if ($fix) {
        return implode("\n", $new_lines);
    }

    return $valid;
}

function install_rewrite_rules($file, $web_path, $download)
{
    $final = install_check_rewrite_rules($file, $web_path, true);
    if (!$download) {
        if (!file_put_contents($file, $final)) {
            AmpError::add('general', T_('Error writing config file'));

            return false;
        }
    } else {
        $browser = new Horde_Browser();
        $browser->downloadHeaders(basename($file), 'text/plain', false, strlen($final));
        echo $final;
        exit();
    }

    return true;
}

/**
 * install_insert_db
 *
 * Inserts the database using the values from Config.
 */
function install_insert_db($db_user = null, $db_pass = null, $create_db = true, $overwrite = false, $create_tables = true)
{
    $database = AmpConfig::get('database_name');
    // Make sure that the database name is valid
    preg_match('/([^\d\w\_\-])/', $database, $matches);

    if (count($matches)) {
        AmpError::add('general', T_('Error: Invalid database name.'));

        return false;
    }

    if (!Dba::check_database()) {
        AmpError::add('general', sprintf(T_('Error: Unable to make database connection: %s'), Dba::error()));

        return false;
    }

    $db_exists = Dba::read('SHOW TABLES');

    if ($db_exists && $create_db) {
        if ($overwrite) {
            Dba::write('DROP DATABASE `' . $database . '`');
        } else {
            AmpError::add('general', T_('Error: Database already exists and overwrite not checked'));

            return false;
        }
    }

    if ($create_db) {
        if (!Dba::write('CREATE DATABASE `' . $database . '`')) {
            AmpError::add('general', sprintf(T_('Error: Unable to create database: %s'), Dba::error()));

            return false;
        }
    }

    Dba::disconnect();

    // Check to see if we should create a user here
    if (strlen($db_user) && strlen($db_pass)) {
        $db_host = AmpConfig::get('database_hostname');
        $sql     = 'GRANT ALL PRIVILEGES ON `' . Dba::escape($database) . '`.* TO ' .
            "'" . Dba::escape($db_user) . "'";
        if ($db_host == 'localhost' || strpos($db_host, '/') === 0) {
            $sql .= "@'localhost'";
        }
        $sql .= "IDENTIFIED BY '" . Dba::escape($db_pass) . "' WITH GRANT OPTION";
        if (!Dba::write($sql)) {
            AmpError::add('general', sprintf(T_('Error: Unable to create user %1$s with permissions to %2$s on %3$s: %4$s'), $db_user, $database, $db_host, Dba::error()));

            return false;
        }
    } // end if we are creating a user

    if ($create_tables) {
        $sql_file = AmpConfig::get('prefix') . '/sql/ampache.sql';
        $query    = fread(fopen($sql_file, 'r'), filesize($sql_file));
        $pieces   = split_sql($query);
        $errors   = array();
        for ($i=0; $i < count($pieces); $i++) {
            $pieces[$i] = trim($pieces[$i]);
            if (!empty($pieces[$i]) && $pieces[$i] != '#') {
                if (!$result = Dba::write($pieces[$i])) {
                    $errors[] = array( Dba::error(), $pieces[$i] );
                }
            }
        }
    }

    if ($create_db) {
        $sql = 'ALTER DATABASE `' . $database . '` DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci';
        Dba::write($sql);
    }

    // If they've picked something other than English update default preferences
    if (AmpConfig::get('lang') != 'en_US') {
        // FIXME: 31? I hate magic.
        $sql = 'UPDATE `preference` SET `value`= ? WHERE `id` = 31';
        Dba::write($sql, array(AmpConfig::get('lang')));
        $sql = 'UPDATE `user_preference` SET `value` = ? WHERE `preference` = 31';
        Dba::write($sql, array(AmpConfig::get('lang')));
    }

    return true;
}

/**
 * install_create_config
 *
 * Attempts to write out the config file or offer it as a download.
 */
function install_create_config($download = false)
{
    $config_file = AmpConfig::get('prefix') . '/config/ampache.cfg.php';

    /* Attempt to make DB connection */
    Dba::dbh();

    $params = AmpConfig::get_all();
    if (empty($params['database_username']) || (empty($params['database_password']) && strpos($params['database_hostname'], '/') !== 0)) {
        AmpError::add('general', T_("Invalid configuration settings"));

        return false;
    }

    // Connect to the DB
    if (!Dba::check_database()) {
        AmpError::add('general', T_("Database Connection Failed Check Hostname, Username and Password"));

        return false;
    }

    $final = generate_config($params);

    // Make sure the directory is writable OR the empty config file is
    if (!$download) {
        if (!check_config_writable()) {
            AmpError::add('general', T_('Config file is not writable'));

            return false;
        } else {
            // Given that $final is > 0, we can ignore lazy comparison problems
            if (!file_put_contents($config_file, $final)) {
                AmpError::add('general', T_('Error writing config file'));

                return false;
            }
        }
    } else {
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
function install_create_account($username, $password, $password2)
{
    if (!strlen($username) or !strlen($password)) {
        AmpError::add('general', T_('No Username/Password specified'));

        return false;
    }

    if ($password !== $password2) {
        AmpError::add('general', T_('Passwords do not match'));

        return false;
    }

    if (!Dba::check_database()) {
        AmpError::add('general', sprintf(T_('Database connection failed: %s'), Dba::error()));

        return false;
    }

    if (!Dba::check_database_inserted()) {
        AmpError::add('general', sprintf(T_('Database select failed: %s'), Dba::error()));

        return false;
    }

    $username = Dba::escape($username);
    $password = Dba::escape($password);

    $insert_id = User::create($username, 'Administrator', '', '', $password, '100');

    if (!$insert_id) {
        AmpError::add('general', sprintf(T_('Administrative user creation failed: %s'), Dba::error()));

        return false;
    }

    // Fix the system users preferences
    User::fix_preferences('-1');

    return true;
} // install_create_account

function command_exists($command)
{
    if (!function_exists('proc_open')) {
        return false;
    }

    $whereIsCommand = (PHP_OS == 'WINNT') ? 'where' : 'which';
    $process        = proc_open(
        "$whereIsCommand $command",
        array(
            0 => array("pipe", "r"), //STDIN
            1 => array("pipe", "w"), //STDOUT
            2 => array("pipe", "w"), //STDERR
        ),
        $pipes
    );

    if ($process !== false) {
        $stdout = stream_get_contents($pipes[1]);
        stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        proc_close($process);

        return $stdout != '';
    }

    return false;
}

/**
 * install_get_transcode_modes
 * get transcode modes available on this machine.
 */
function install_get_transcode_modes()
{
    $modes = array();

    if (command_exists('ffmpeg')) {
        $modes[] = 'ffmpeg';
    }
    if (command_exists('avconv')) {
        $modes[] = 'avconv';
    }

    return $modes;
} // install_get_transcode_modes

function install_config_transcode_mode($mode)
{
    $trconfig = array(
        'encode_target' => 'mp3',
        'encode_video_target' => 'webm',
        'transcode_m4a' => 'required',
        'transcode_flac' => 'required',
        'transcode_mpc' => 'required',
        'transcode_ogg' => 'allowed',
        'transcode_wav' => 'required',
        'transcode_avi' => 'allowed',
        'transcode_mpg' => 'allowed',
        'transcode_mkv' => 'allowed',
    );
    if ($mode == 'ffmpeg' || $mode == 'avconv') {
        $trconfig['transcode_cmd']          = $mode;
        $trconfig['transcode_input']        = '-i %FILE%';
        $trconfig['waveform']               = 'true';
        $trconfig['generate_video_preview'] = 'true';

        AmpConfig::set_by_array($trconfig, true);
    }
}

function install_config_use_case($case)
{
    $trconfig = array(
        'use_auth' => 'true',
        'ratings' => 'true',
        'userflags' => 'true',
        'sociable' => 'true',
        'licensing' => 'false',
        'wanted' => 'true',
        'channel' => 'true',
        'live_stream' => 'true',
        'allow_public_registration' => 'false',
        'cookie_disclaimer' => 'false',
        'share' => 'false'
    );

    $dbconfig = array(
        'download' => '1',
        'share' => '0',
        'allow_video' => '1',
        'home_now_playing' => '1',
        'home_recently_played' => '1'
    );

    switch ($case) {
        case 'minimalist':
            $trconfig['ratings']     = 'false';
            $trconfig['userflags']   = 'false';
            $trconfig['sociable']    = 'false';
            $trconfig['wanted']      = 'false';
            $trconfig['channel']     = 'false';
            $trconfig['live_stream'] = 'false';

            $dbconfig['download']    = '0';
            $dbconfig['allow_video'] = '0';

            // Default local UI preferences to have a better 'minimalist first look'.
            setcookie('sidebar_state', 'collapsed', time() + (30 * 24 * 60 * 60), '/');
            setcookie('browse_album_grid_view', 'false', time() + (30 * 24 * 60 * 60), '/');
            setcookie('browse_artist_grid_view', 'false', time() + (30 * 24 * 60 * 60), '/');
            break;
        case 'community':
            $trconfig['use_auth']                  = 'false';
            $trconfig['licensing']                 = 'true';
            $trconfig['wanted']                    = 'false';
            $trconfig['live_stream']               = 'false';
            $trconfig['allow_public_registration'] = 'true';
            $trconfig['cookie_disclaimer']         = 'true';
            $trconfig['share']                     = 'true';

            $dbconfig['download']             = '0';
            $dbconfig['share']                = '1';
            $dbconfig['home_now_playing']     = '0';
            $dbconfig['home_recently_played'] = '0';
            break;
        default:
            break;
    }

    AmpConfig::set_by_array($trconfig, true);
    foreach ($dbconfig as $preference => $value) {
        Preference::update($preference, -1, $value, true, true);
    }
}

function install_config_backends(array $backends)
{
    $dbconfig = array(
        'subsonic_backend' => '0',
        'plex_backend' => '0',
        'daap_backend' => '0',
        'upnp_backend' => '0',
        'webdav_backend' => '0',
        'stream_beautiful_url' => '0'
    );

    foreach ($backends as $backend) {
        switch ($backend) {
            case 'subsonic':
                $dbconfig['subsonic_backend'] = '1';
                break;
            case 'plex':
                $dbconfig['plex_backend'] = '1';
                break;
            case 'upnp':
                $dbconfig['upnp_backend']         = '1';
                $dbconfig['stream_beautiful_url'] = '1';
                break;
            case 'daap':
                $dbconfig['daap_backend'] = '1';
                break;
            case 'webdav':
                $dbconfig['webdav_backend'] = '1';
                break;
        }
    }

    foreach ($dbconfig as $preference => $value) {
        Preference::update($preference, -1, $value, true, true);
    }
}
