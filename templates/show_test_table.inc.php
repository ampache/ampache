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
 */ ?>
<tr>
    <td><?php echo T_('PHP version'); ?></td>
    <td><?php echo debug_result(check_php_version()); ?></td>
    <td><?php echo T_('This tests whether you are running at least the minimum version of PHP required by Ampache.'); ?></td>
</tr>
<tr>
    <td><?php echo T_('Dependencies'); ?></td>
    <td><?php echo debug_result(check_dependencies_folder()); ?></td>
    <td>
    <?php echo T_('This tests whether Ampache dependencies are installed.'); ?>
    <?php if (!check_dependencies_folder()) { ?>
        <br />
        <b><?php echo T_('Please download Composer from http://getcomposer.org, and install it (e.g: mv composer.phar /usr/local/bin/composer). Then run `composer install --prefer-source --no-interaction` on the Ampache directory.'); ?></b>
    <?php
} ?>
    </td>
</tr>
<tr>
    <td><?php echo T_('PHP hash extension'); ?></td>
    <td><?php echo debug_result(check_php_hash()); ?></td>
    <td><?php echo T_('This tests whether you have the hash extension enabled. This extension is required by Ampache.'); ?></td>
</tr>
<tr>
    <td><?php echo T_('SHA256'); ?></td>
    <td><?php echo debug_result(check_php_hash_algo()); ?></td>
    <td><?php echo T_('This tests whether the hash extension supports SHA256. This algorithm is required by Ampache.'); ?></td>
</tr>
<tr>
    <td><?php echo T_('PHP PDO extension'); ?></td>
    <td><?php echo debug_result(check_php_pdo()); ?></td>
    <td><?php echo T_('This tests whether you have the PDO extension enabled. This extension is required by Ampache.'); ?></td>
</tr>
<tr>
    <td><?php echo T_('MySQL'); ?></td>
    <td><?php echo debug_result(check_php_pdo_mysql()); ?></td>
    <td><?php echo T_('This tests whether the MySQL driver for PDO is enabled. This driver is required by Ampache.'); ?></td>
</tr>
<tr>
    <td><?php echo T_('PHP session extension'); ?></td>
    <td><?php echo debug_result(check_php_session()); ?></td>
    <td><?php echo T_('This tests whether you have the session extension enabled. This extension is required by Ampache.'); ?></td>
</tr>
<tr>
    <td><?php echo T_('PHP iconv extension'); ?></td>
    <td><?php echo debug_result(UI::check_iconv()); ?></td>
    <td><?php echo T_('This tests whether you have the iconv extension enabled. This extension is required by Ampache.'); ?></td>
</tr>
<tr>
    <td><?php echo T_('PHP JSON extension'); ?></td>
    <td><?php echo debug_result(check_php_json()); ?></td>
    <td><?php echo T_('This tests whether you have the JSON extension enabled. This extension is required by Ampache.'); ?></td>
</tr>
<tr>
    <td><?php echo T_('PHP cURL extension'); ?></td>
    <td><?php echo debug_wresult(check_php_curl()); ?></td>
    <td><?php echo T_('This tests whether you have the cURL extension enabled. This is not strictly necessary, but may result in a better experience.'); ?></td>
</tr>
<tr>
    <td><?php echo T_('PHP zlib extension'); ?></td>
    <td><?php echo debug_wresult(check_php_zlib()); ?></td>
    <td><?php echo T_('This tests whether you have the zlib extension enabled. This is not strictly necessary, but may result in a better experience (zip download).'); ?></td>
</tr>
<tr>
    <td><?php echo T_('PHP SimpleXML extension'); ?></td>
    <td><?php echo debug_wresult(check_php_simplexml()); ?></td>
    <td><?php echo T_('This tests whether you have the SimpleXML extension enabled. This is not strictly necessary, but may result in a better experience.'); ?></td>
</tr>
<tr>
    <td><?php echo T_('PHP GD extension'); ?></td>
    <td><?php echo debug_wresult(check_php_gd()); ?></td>
    <td><?php echo T_('This tests whether you have the GD extension enabled. This is not strictly necessary, but may result in a better experience.'); ?></td>
</tr>
<tr>
    <td><?php echo T_('PHP memory limit override'); ?></td>
    <td><?php echo debug_wresult(check_override_memory()); ?></td>
    <td><?php echo T_('This tests whether Ampache can override the memory limit. This is not strictly necessary, but may result in a better experience.'); ?></td>
</tr>
<tr>
    <td><?php echo T_('PHP execution time override'); ?></td>
    <td><?php echo debug_wresult(check_override_exec_time()); ?></td>
    <td><?php echo T_('This tests whether Ampache can override the limit on maximum execution time. This is not strictly necessary, but may result in a better experience.'); ?></td>
</tr>
<tr>
    <td><?php echo T_('PHP max upload size'); ?></td>
    <td><?php echo debug_wresult(check_upload_size()); ?></td>
    <td><?php echo T_('This tests whether Ampache can upload medium files (>= 20M). This is not strictly necessary, but may result in a better experience.'); ?></td>
</tr>
<tr>
    <td><?php echo T_('PHP integer size'); ?></td>
    <td><?php echo debug_wresult(check_php_int_size()); ?></td>
    <td><?php echo T_('This tests whether Ampache can manage large files (> 2GB). This is not strictly necessary, but may result in a better experience. This generally requires a 64-bit operating system.'); ?></td>
</tr>
<tr>
    <td><?php echo T_('PHP mbstring.func_overload'); ?></td>
    <td><?php echo debug_result(check_mbstring_func_overload()); ?></td>
    <td><?php /* HINT: Shows mbstring.func_overload */ printf(T_('This tests whether PHP %s is set as it may break the ID3 tag support. This is not strictly necessary, but enabling Ampache ID3 tag write support (disabled by default) along with mbstring.func_overload may result in irreversible corruption of your music files.'), '<a href="http://php.net/manual/en/mbstring.overload.php">mbstring.func_overload</a>'); ?></td>
</tr>
<?php
if (!defined('INSTALL')) { ?>
<tr>
    <td><?php echo T_('Configuration file readability'); ?></td>
    <td><?php echo debug_result(is_readable($configfile), "WARNING"); ?></td>
    <td>
        <?php echo T_('This test attempts to read config/ampache.cfg.php. If this fails the file is either not in the correct location, or not readable.'); ?> </br>
        <?php echo T_('If you are installing Ampache for the first time you can ignore this warning and proceed to the installer.'); ?> &nbsp;<a href="install.php"><?php echo T_('Web Installation'); ?></a>
    </td>
</tr>
<?php if (is_readable($configfile)) { ?>
<tr>
    <td><?php echo T_('Configuration file validity'); ?></td>
    <td>
    <?php
        $results = @parse_ini_file($configfile);
        if ($results) {
            AmpConfig::set_by_array($results);
            echo debug_result(check_config_values($results));
        } ?>
    </td>
    <td><?php echo T_("This test makes sure that you have set all of the required configuration variables and that Ampache is able to completely parse your config file."); ?></td>
</tr>
<tr>
    <td><?php echo T_("Database connection"); ?></td>
    <td><?php echo debug_result(check_php_pdo() && Dba::check_database()); ?></td>
    <td><?php echo T_('This attempts to connect to your database using the values read from your configuration file.'); ?></td>
</tr>
<tr>
    <td><?php echo T_('Database tables'); ?></td>
    <td><?php echo debug_result(check_php_pdo() && Dba::check_database_inserted()); ?></td>
    <td><?php echo T_('This checks a few key tables to make sure that the Ampache database exists, and the user has access to the database'); ?></td>
</tr>
<tr>
    <td><?php echo T_('Web path'); ?></td>
    <td>
    <?php
        if ($results && check_config_values($results)) {
            echo "&nbsp;&nbsp;&nbsp;" . UI::get_icon('enable', T_('Enable')) . "&nbsp;&nbsp;&nbsp;";
        } else {
            echo debug_result(false, "SKIPPED");
        } ?>
    </td>
    <td><?php echo T_('This test makes sure that your web_path variable is set correctly and that we are able to get to the index page. If you do not see a check mark here then your web_path is not set correctly.'); ?></td>
</tr>
<?php
    }
} ?>
