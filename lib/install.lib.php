<?php
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

/**
 * split_sql
 * splits up a standard SQL dump file into distinct sql queries
 * @param string $sql
 * @return array
 */
function split_sql($sql)
{
    $sql       = trim((string) $sql);
    $sql       = preg_replace("/\n#[^\n]*\n/", "\n", $sql);
    $buffer    = array();
    $ret       = array();
    $in_string = false;
    for ($count = 0; $count < strlen((string) $sql) - 1; $count++) {
        if ($sql[$count] == ";" && !$in_string) {
            $ret[] = substr($sql, 0, $count);
            $sql   = substr($sql, $count + 1);
            $count = 0;
        }
        if ($in_string && ($sql[$count] == $in_string) && $buffer[1] != "\\") {
            $in_string = false;
        } elseif (!$in_string && ($sql[$count] == '"' || $sql[$count] == "'") && (!isset($buffer[0]) || $buffer[0] != "\\")) {
            $in_string = $sql[$count];
        }
        if (isset($buffer[1])) {
            $buffer[0] = $buffer[1];
        }
        $buffer[1] = $sql[$count];
    }
    if (!empty($sql)) {
        $ret[] = $sql;
    }

    return ($ret);
} // split_sql

/**
 * install_check_status
 * this function checks to see if we actually
 * still need to install ampache. This function is
 * very important, we don't want to reinstall over top of an existing install
 * @param $configfile
 * @return boolean
 */
function install_check_status($configfile)
{
    /**
     * Check and see if the config file exists
     * if it does they can't use the web interface
     * to install ampache.
     */
    if (!file_exists($configfile)) {
        return true;
    }

    /**
     * Check and see if they've got _any_ account
     * if they don't then they're cool
     */
    $results = parse_ini_file($configfile);
    AmpConfig::set_by_array($results, true);

    if (!Dba::check_database()) {
        AmpError::add('general', T_('Unable to connect to the database, check your Ampache config'));

        return false;
    }

    $sql        = 'SELECT * FROM `user`';
    $db_results = Dba::read($sql);

    if (!$db_results) {
        AmpError::add('general', T_('Unable to query the database, check your Ampache config'));

        return false;
    }

    if (!Dba::num_rows($db_results)) {
        return true;
    } else {
        AmpError::add('general', T_('Existing database was detected, unable to continue the installation'));

        return false;
    }
} // install_check_status

/**
 * @return boolean
 */
function install_check_server_apache()
{
    return (strpos($_SERVER['SERVER_SOFTWARE'], "Apache/") === 0);
}

/**
 * @param string $file
 * @param $web_path
 * @param boolean $fix
 * @return boolean|string
 */
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
        $parts   = explode(' ', (string) $line);
        $p_count = count($parts);
        for ($count = 0; $count < $p_count; $count++) {
            // Matching url rewriting rule syntax
            if ($parts[$count] === 'RewriteRule' && $count < ($p_count - 2)) {
                $reprule = $parts[$count + 2];
                if (!empty($web_path) && strpos($reprule, $web_path) !== 0) {
                    $reprule = $web_path . $reprule;
                    if ($fix) {
                        $parts[$count + 2] = $reprule;
                        $line              = implode(' ', $parts);
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

/**
 * @param string $file
 * @param $web_path
 * @param boolean $download
 * @return boolean
 */
function install_rewrite_rules($file, $web_path, $download)
{
    $final = install_check_rewrite_rules($file, $web_path, true);
    if (!$download) {
        if (!file_put_contents($file, $final)) {
            AmpError::add('general', T_('Failed to write config file'));

            return false;
        }
    } else {
        $browser = new Horde_Browser();
        $browser->downloadHeaders(basename($file), 'text/plain', false, strlen((string) $final));
        echo $final;

        return false;
    }

    return true;
}

/**
 * install_insert_db
 *
 * Inserts the database using the values from Config.
 * @param string $db_user
 * @param string $db_pass
 * @param boolean $create_db
 * @param boolean $overwrite
 * @param boolean $create_tables
 * @param string $charset
 * @param string $collation
 * @return boolean
 */
function install_insert_db($db_user = null, $db_pass = null, $create_db = true, $overwrite = false, $create_tables = true, $charset = 'utf8', $collation = 'utf8_unicode_ci')
{
    $database = (string) AmpConfig::get('database_name');
    // Make sure that the database name is valid
    preg_match('/([^\d\w\_\-])/', $database, $matches);

    if (count($matches)) {
        AmpError::add('general', T_('Database name is invalid'));

        return false;
    }

    if (!Dba::check_database()) {
        /* HINT: Database error message */
        AmpError::add('general', sprintf(T_('Unable to connect to the database: %s'), Dba::error()));

        return false;
    }

    $db_exists = Dba::read('SHOW TABLES');

    if ($db_exists && $create_db) {
        if ($overwrite) {
            Dba::write('DROP DATABASE `' . $database . '`');
        } else {
            AmpError::add('general', T_('Database already exists and "overwrite" was not checked'));

            return false;
        }
    }

    if ($create_db) {
        if (!Dba::write('CREATE DATABASE `' . $database . '`')) {
            /* HINT: Database error message */
            AmpError::add('general', sprintf(T_('Unable to create the database: %s'), Dba::error()));

            return false;
        }
    }

    Dba::disconnect();

    // Check to see if we should create a user here
    if (strlen((string) $db_user) && strlen((string) $db_pass)) {
        $db_host  = AmpConfig::get('database_hostname');
        // create the user account
        $sql_user = "CREATE USER '" . Dba::escape($db_user) . "'";
        if ($db_host == 'localhost' || strpos($db_host, '/') === 0) {
            $sql_user .= "@'localhost'";
        }
        $sql_user .= " IDENTIFIED BY '" . Dba::escape($db_pass) . "'";
        if (!Dba::write($sql_user)) {
            AmpError::add('general', sprintf(
                /* HINT: %1 user, %2 database, %3 host, %4 error message */
                T_('Unable to create the user "%1$s" with permissions to "%2$s" on "%3$s": %4$s'), $db_user, $database, $db_host, Dba::error()));

            return false;
        }
        // grant database access to that account
        $sql_grant = "GRANT ALL PRIVILEGES ON `" . Dba::escape($database) . "`.* TO '" . Dba::escape($db_user) . "'";
        if ($db_host == 'localhost' || strpos($db_host, '/') === 0) {
            $sql_grant .= "@'localhost'";
        }
        $sql_grant .= "  WITH GRANT OPTION";

        if (!Dba::write($sql_grant)) {
            AmpError::add('general', sprintf(
                /* HINT: %1 database, %2 user, %3 host, %4 error message */
                T_('Unable to grant permissions to "%1$s" for the user "%2$s" on "%3$s": %4$s'), $database, $db_user, $db_host, Dba::error()));

            return false;
        }
    } // end if we are creating a user

    if ($create_tables) {
        $sql_file = AmpConfig::get('prefix') . '/sql/ampache.sql';
        $query    = fread(fopen($sql_file, 'r'), filesize($sql_file));
        $pieces   = split_sql($query);
        $p_count  = count($pieces);
        $errors   = array();
        for ($count = 0; $count < $p_count; $count++) {
            $pieces[$count] = trim((string) $pieces[$count]);
            if (!empty($pieces[$count]) && $pieces[$count] != '#') {
                if (!Dba::write($pieces[$count])) {
                    $errors[] = array(Dba::error(), $pieces[$count]);
                }
            }
        }
    }

    if ($create_db) {
        $sql = "ALTER DATABASE `" . $database . "` DEFAULT CHARACTER SET $charset COLLATE " . $collation;
        Dba::write($sql);
        // if you've set a custom collation we need to change it
        $tables = array("access_list", "album", "artist", "bookmark", "broadcast", "cache_object_count", "cache_object_count_run", "catalog", "catalog_local", "catalog_remote", "channel", "clip", "daap_session", "democratic", "image", "ip_history", "label", "label_asso", "license", "live_stream", "localplay_httpq", "localplay_mpd", "metadata", "metadata_field", "movie", "now_playing", "object_count", "personal_video", "player_control", "playlist", "playlist_data", "podcast", "podcast_episode", "preference", "rating", "recommendation", "recommendation_item", "search", "session", "session_remember", "session_stream", "share", "song", "song_data", "song_preview", "stream_playlist", "tag", "tag_map", "tag_merge", "tmp_browse", "tmp_playlist", "tmp_playlist_data", "tvshow", "tvshow_episode", "tvshow_season", "update_info", "user", "user_activity", "user_catalog", "user_flag", "user_follower", "user_preference", "user_pvmsg", "user_shout", "user_vote", "video", "wanted");
        foreach ($tables as $table_name) {
            $sql = "ALTER TABLE `" . $table_name . "` CHARACTER SET $charset COLLATE " . $collation;
            Dba::write($sql);
        }
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
 * @param boolean $download
 * @return boolean
 * @throws Exception
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
        AmpError::add('general', T_("Connection to the database failed: Check hostname, username and password"));

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
                AmpError::add('general', T_('Failed writing config file'));

                return false;
            }
        }
    } else {
        $browser = new Horde_Browser();
        $browser->downloadHeaders('ampache.cfg.php', 'text/plain', false, strlen((string) $final));
        echo $final;

        return false;
    }

    return true;
}

/**
 * install_create_account
 * this creates your initial account and sets up the preferences for the -1 user and you
 * @param string $username
 * @param string $password
 * @param string $password2
 * @return boolean
 */
function install_create_account($username, $password, $password2)
{
    if (!strlen((string) $username) || !strlen((string) $password)) {
        AmpError::add('general', T_('No username or password was specified'));

        return false;
    }

    if ($password !== $password2) {
        AmpError::add('general', T_('Passwords do not match'));

        return false;
    }

    if (!Dba::check_database()) {
        /* HINT: Database error message */
        AmpError::add('general', sprintf(T_('Connection to the database failed: %s'), Dba::error()));

        return false;
    }

    if (!Dba::check_database_inserted()) {
        /* HINT: Database error message */
        AmpError::add('general', sprintf(T_('Database select failed: %s'), Dba::error()));

        return false;
    }

    $username = Dba::escape($username);
    $password = Dba::escape($password);
    $user_id  = User::create($username, 'Administrator', '', '', $password, '100');

    if ($user_id < 1) {
        /* HINT: Database error message */
        AmpError::add('general', sprintf(T_('Administrative user creation failed: %s'), Dba::error()));

        return false;
    }

    // Fix the system users preferences
    User::fix_preferences('-1');

    return true;
} // install_create_account

/**
 * @param string $command
 * @return boolean
 */
function command_exists($command)
{
    if (!function_exists('proc_open')) {
        return false;
    }

    $whereIsCommand = (PHP_OS == 'WINNT') ? 'where' : 'which';
    $process        = proc_open(
        "$whereIsCommand $command",
        array(
            0 => array("pipe", "r"), // STDIN
            1 => array("pipe", "w"), // STDOUT
            2 => array("pipe", "w"), // STDERR
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
 * @return array
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

/**
 * @param $mode
 */
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

/**
 * @param $case
 */
function install_config_use_case($case)
{
    $trconfig = array(
        'use_auth' => 'true',
        'ratings' => 'true',
        'userflags' => 'true',
        'sociable' => 'true',
        'licensing' => 'false',
        'wanted' => 'false',
        'channel' => 'false',
        'live_stream' => 'true',
        'allow_public_registration' => 'false',
        'cookie_disclaimer' => 'false',
        'share' => 'false'
    );

    $dbconfig = array(
        'download' => '1',
        'share' => '0',
        'allow_video' => '0',
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

/**
 * @param array $backends
 */
function install_config_backends(array $backends)
{
    $dbconfig = array(
        'subsonic_backend' => '0',
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
