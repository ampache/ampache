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
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Repository\Model\Preference;
use Ampache\Repository\Model\User;
use Ampache\Module\Util\Horde_Browser;
use Exception;

final class InstallationHelper implements InstallationHelperInterface
{
    /**
     * splits up a standard SQL dump file into distinct sql queries
     */
    private function split_sql(string $sql): array
    {
        $sql       = trim($sql);
        $sql       = (string)preg_replace("/\n--[^\n]*\n/", "\n", $sql);
        $buffer    = [];
        $ret       = [];
        $in_string = false;
        for ($count = 0; $count < strlen($sql) - 1; $count++) {
            if ($sql[$count] == ";" && !$in_string) {
                $ret[] = substr($sql, 0, $count);
                $sql   = substr($sql, $count + 1);
                $count = 0;
            }
            if ($in_string && ($sql[$count] == $in_string) && $buffer[1] != "\\") {
                $in_string = false;
            } elseif (
                !$in_string &&
                (
                    $sql[$count] == '"' ||
                    $sql[$count] == "'"
                ) &&
                (
                    !isset($buffer[0]) ||
                    $buffer[0] != "\\"
                )
            ) {
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

        return $ret;
    }

    /**
     * this function checks to see if we actually still need to install Ampache.
     * This function is very important, we don't want to reinstall over top of an existing install
     */
    public function install_check_status(string $configfile): bool
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
        if (!$results) {
            AmpError::add('general', T_("Invalid configuration settings"));

            return false;
        }

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
        }

        AmpError::add('general', T_('Existing database was detected, unable to continue the installation'));

        return false;
    }

    /**
     * install_check_server_apache
     */
    public function install_check_server_apache(): bool
    {
        return (strpos($_SERVER['SERVER_SOFTWARE'], "Apache/") === 0);
    }

    public function install_check_rewrite_rules(string $file, string $web_path, bool $fix = false): bool|string
    {
        if (!is_readable($file)) {
            $file .= '.dist';
        }
        $valid     = true;
        $htaccess  = (string)file_get_contents($file);
        $new_lines = [];
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

    public function install_rewrite_rules(string $file, string $web_path, bool $download): bool
    {
        $final = $this->install_check_rewrite_rules($file, $web_path, true);
        if (empty($final)) {
            AmpError::add('general', T_('Config file is not writable') . ': ' . $file);

            return false;
        }

        if (!$download) {
            if (!file_put_contents($file, $final)) {
                AmpError::add('general', T_('Failed to write config file') . ': ' . $file);

                return false;
            }
        } else {
            $browser = new Horde_Browser();
            $headers = $browser->getDownloadHeaders(basename($file), 'text/plain', false, (string)strlen((string) $final));

            foreach ($headers as $headerName => $value) {
                header(sprintf('%s: %s', $headerName, $value));
            }
            echo $final;

            return false;
        }

        return true;
    }

    /**
     * install_insert_db
     *
     * Inserts the database using the values from Config.
     */
    public function install_insert_db(
        ?string $db_user = null,
        ?string $db_pass = null,
        bool $create_db = true,
        bool $overwrite = false,
        bool $create_tables = true,
        string $charset = 'utf8mb4',
        string $collation = 'utf8mb4_unicode_ci'
    ): bool {
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

        if (is_object($db_exists) && $create_db) {
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
            $db_host = AmpConfig::get('database_hostname');
            // create the user account
            $sql_user = "CREATE USER '" . Dba::escape($db_user) . "'";
            if ($db_host == 'localhost' || strpos($db_host, '/') === 0) {
                $sql_user .= "@'localhost'";
            }
            $sql_user .= " IDENTIFIED BY '" . Dba::escape($db_pass) . "'";
            if (!Dba::write($sql_user)) {
                AmpError::add(
                    'general',
                    sprintf(
                        /* HINT: %1 user, %2 database, %3 host, %4 error message */
                        T_('Unable to create the user "%1$s" with permissions to "%2$s" on "%3$s": %4$s'),
                        $db_user,
                        $database,
                        $db_host,
                        Dba::error()
                    )
                );
                // this user might exist but we don't always care
                if (!$overwrite) {
                    return false;
                }
            }
            // grant database access to that account
            $sql_grant = "GRANT ALL PRIVILEGES ON `" . Dba::escape($database) . "`.* TO '" . Dba::escape($db_user) . "'";
            if ($db_host == 'localhost' || strpos($db_host, '/') === 0) {
                $sql_grant .= "@'localhost'";
            }
            $sql_grant .= " WITH GRANT OPTION";

            if (!Dba::write($sql_grant)) {
                AmpError::add(
                    'general',
                    sprintf(
                        /* HINT: %1 database, %2 user, %3 host, %4 error message */
                        T_('Unable to grant permissions to "%1$s" for the user "%2$s" on "%3$s": %4$s'),
                        $database,
                        $db_user,
                        $db_host,
                        Dba::error()
                    )
                );

                return false;
            }
        } // end if we are creating a user

        if ($create_tables) {
            $sql_file   = __DIR__ . '/../../../resources/sql/ampache.sql';
            $sql_handle = fopen($sql_file, 'r');
            $length     = Core::get_filesize($sql_file);
            if (!$sql_handle || $length <= 0) {
                AmpError::add('general', T_('Unable to open ampache.sql'));

                return false;
            }

            $query = fread($sql_handle, $length);
            if (!$query) {
                AmpError::add('general', T_('Unable to open ampache.sql'));

                return false;
            }

            $pieces  = $this->split_sql($query);
            $p_count = count($pieces);
            $errors  = [];
            for ($count = 0; $count < $p_count; $count++) {
                $pieces[$count] = trim((string) $pieces[$count]);
                if (!empty($pieces[$count]) && $pieces[$count] != '#') {
                    if (!Dba::write($pieces[$count])) {
                        $errors[] = [Dba::error(), $pieces[$count]];
                    }
                }
            }
        }

        if ($create_db) {
            $sql = "ALTER DATABASE `" . $database . "` DEFAULT CHARACTER SET $charset COLLATE " . $collation;
            Dba::write($sql);
            // if you've set a custom collation we need to change it
            $tables = ["access_list", "album", "artist", "bookmark", "broadcast", "cache_object_count", "cache_object_count_run", "catalog", "catalog_local", "catalog_remote", "channel", "daap_session", "democratic", "image", "ip_history", "label", "label_asso", "license", "live_stream", "localplay_httpq", "localplay_mpd", "metadata", "metadata_field", "now_playing", "object_count", "player_control", "playlist", "playlist_data", "podcast", "podcast_episode", "preference", "rating", "recommendation", "recommendation_item", "search", "session", "session_remember", "session_stream", "share", "song", "song_data", "song_preview", "stream_playlist", "tag", "tag_map", "tag_merge", "tmp_browse", "tmp_playlist", "tmp_playlist_data", "update_info", "user", "user_activity", "user_catalog", "user_flag", "user_follower", "user_preference", "user_pvmsg", "user_shout", "user_vote", "video", "wanted"];
            foreach ($tables as $table_name) {
                $sql = "ALTER TABLE `" . $table_name . "` CHARACTER SET $charset COLLATE " . $collation;
                Dba::write($sql);
            }
        }

        // If they've picked something other than English update default preferences
        if (AmpConfig::get('lang', 'en_US') != 'en_US') {
            $sql = 'UPDATE `preference` SET `value` = ? WHERE `name` = \'lang\';';
            Dba::write($sql, [AmpConfig::get('lang', 'en_US')]);
            $sql = 'UPDATE `user_preference` SET `value` = ? WHERE `name` = \'lang\';';
            Dba::write($sql, [AmpConfig::get('lang', 'en_US')]);
        }

        return true;
    }

    /**
     * Attempts to write out the config file or offer it as a download.
     * @throws Exception
     */
    public function install_create_config(bool $download = false): bool
    {
        $config_file = __DIR__ . '/../../../config/ampache.cfg.php';

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

        $final = $this->generate_config($params);
        if (empty($final)) {
            AmpError::add('general', T_('Config file is not writable'));

            return false;
        }

        // Make sure the directory is writable OR the empty config file is
        if (!$download) {
            if (!check_config_writable()) {
                AmpError::add('general', T_('Config file is not writable'));

                return false;
            } elseif (!file_put_contents($config_file, $final)) {
                // Given that $final is > 0, we can ignore lazy comparison problems
                AmpError::add('general', T_('Failed writing config file'));

                return false;
            }
        } else {
            $browser = new Horde_Browser();
            $headers = $browser->getDownloadHeaders('ampache.cfg.php', 'text/plain', false, (string)strlen($final));
            foreach ($headers as $headerName => $value) {
                header(sprintf('%s: %s', $headerName, $value));
            }

            echo $final;

            return false;
        }

        return true;
    }

    /**
     * this creates your initial account and sets up the preferences for the -1 user and you
     */
    public function install_create_account(string $username, string $password, string $password2): bool
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

        $user_id = User::create($username, 'Administrator', '', '', $password, AccessLevelEnum::ADMIN);
        if ($user_id < 1) {
            /* HINT: Database error message */
            AmpError::add('general', sprintf(T_('Administrative user creation failed: %s'), Dba::error()));

            return false;
        }

        // Fix the system user preferences
        User::fix_preferences(-1);

        return true;
    }

    private function command_exists(string $command): bool
    {
        if (!function_exists('proc_open')) {
            return false;
        }

        $whereIsCommand = (PHP_OS == 'WINNT') ? 'where' : 'which';
        $process        = proc_open(
            "$whereIsCommand $command",
            [
                0 => ["pipe", "r"], // STDIN
                1 => ["pipe", "w"], // STDOUT
                2 => ["pipe", "w"], // STDERR
            ],
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
     * get transcode modes available on this machine.
     * @return string[]
     */
    public function install_get_transcode_modes(): array
    {
        $modes = [];

        if ($this->command_exists('ffmpeg')) {
            $modes[] = 'ffmpeg';
        }
        if ($this->command_exists('avconv')) {
            $modes[] = 'avconv';
        }

        return $modes;
    }

    public function install_config_transcode_mode(string $mode): void
    {
        $trconfig = [
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
        ];
        if ($mode == 'ffmpeg' || $mode == 'avconv') {
            $trconfig['transcode_cmd']          = $mode;
            $trconfig['transcode_input']        = '-i %FILE%';
            $trconfig['waveform']               = 'true';
            $trconfig['generate_video_preview'] = 'true';

            AmpConfig::set_by_array($trconfig, true);
        }
    }

    public function install_config_use_case(string $case): void
    {
        $trconfig = [
            'use_auth' => 'true',
            'ratings' => 'true',
            'sociable' => 'true',
            'licensing' => 'false',
            'wanted' => 'false',
            'live_stream' => 'true',
            'allow_public_registration' => 'false',
            'cookie_disclaimer' => 'false',
            'share' => 'false',
        ];

        $dbconfig = [
            'download' => '1',
            'share' => '0',
            'allow_video' => '0',
            'home_now_playing' => '1',
            'home_recently_played' => '1',
        ];

        switch ($case) {
            case 'minimalist':
                $trconfig['ratings']     = 'false';
                $trconfig['sociable']    = 'false';
                $trconfig['wanted']      = 'false';
                $trconfig['live_stream'] = 'false';

                $dbconfig['download']    = '0';
                $dbconfig['allow_video'] = '0';

                $cookie_options = [
                    'expires' => time() + (60 * 60 * 24 * 30), // 30 day
                    'path' => '/',
                    'samesite' => 'Strict'
                ];

                // Default local UI preferences to have a better 'minimalist first look'.
                setcookie('sidebar_state', 'collapsed', $cookie_options);
                setcookie('browse_album_grid_view', 'false', $cookie_options);
                setcookie('browse_artist_grid_view', 'false', $cookie_options);
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
        }

        AmpConfig::set_by_array($trconfig, true);
        foreach ($dbconfig as $preference => $value) {
            Preference::update($preference, -1, $value, true, true);
        }
    }

    /**
     * @param string[] $backends
     */
    public function install_config_backends(array $backends): void
    {
        $dbconfig = [
            'subsonic_backend' => '0',
            'daap_backend' => '0',
            'upnp_backend' => '0',
            'webdav_backend' => '0',
            'stream_beautiful_url' => '0',
        ];

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

    /**
     * Write new configuration into the current configuration file by keeping old values.
     */
    public function write_config(string $current_file_path): bool
    {
        if (
            !is_writeable($current_file_path) ||
            !parse_ini_file($current_file_path)
        ) {
            return false;
        }

        $new_data = $this->generate_config(parse_ini_file($current_file_path));

        // Start writing into the current config file
        $handle = fopen($current_file_path, 'w+');
        $length = strlen((string) $new_data);
        if (
            empty($new_data) ||
            !$handle ||
            $length <= 0
        ) {
            return false;
        }

        fwrite($handle, $new_data, $length);
        fclose($handle);

        return true;
    }

    /**
     * This takes an array of results and re-generates the config file
     * this is used by the installer and by the admin/system page
     * @param array $current
     * @return string
     * @throws Exception
     */
    public function generate_config(array $current): string
    {
        // Start building the new config file
        $distfile = __DIR__ . '/../../../config/ampache.cfg.php.dist';
        $handle   = fopen($distfile, 'r');
        $length   = Core::get_filesize($distfile);
        if (!$handle || $length <= 0) {
            return '';
        }

        $dist = fread($handle, $length);
        fclose($handle);

        $data  = explode("\n", (string) $dist);
        $final = "";
        foreach ($data as $line) {
            if (
                preg_match("/^;?([\w\d]+)\s+=\s+[\"]{1}(.*?)[\"]{1}$/", $line, $matches) ||
                preg_match("/^;?([\w\d]+)\s+=\s+[\']{1}(.*?)[\']{1}$/", $line, $matches) ||
                preg_match("/^;?([\w\d]+)\s+=\s+[\'\"]{0}(.*)[\'\"]{0}$/", $line, $matches) ||
                preg_match("/^;?([\w\d]+)\s{0}=\s{0}[\'\"]?(.*?)[\'\"]?$/", $line, $matches)
            ) {
                $key   = $matches[1];
                $value = $matches[2];

                // Put in the current value
                if ($key == 'config_version') {
                    $line = $key . ' = ' . $this->escape_ini($value);
                } elseif ($key == 'secret_key' && !isset($current[$key])) {
                    $secret_key = Core::gen_secure_token(31);
                    if ($secret_key !== null) {
                        $line = $key . ' = "' . $this->escape_ini($secret_key) . '"';
                    }
                } elseif (isset($current[$key])) {
                    // unable to generate a cryptographically secure token, use the default one
                    $line = $key . ' = "' . $this->escape_ini((string) $current[$key]) . '"';
                    unset($current[$key]);
                }
            }

            $final .= $line . "\n";
        }

        return $final;
    }

    /**
     * Escape a value used for inserting into an ini file.
     * Won't quote ', like addslashes does.
     * @param string|string[] $str
     * @return string|string[]
     */
    private function escape_ini(array|string $str): array|string
    {
        return str_replace('"', '\"', $str);
    }
}
