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
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "DTD/xhtml1-transitional.dtd">
<html lang="en-US">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Ampache -- Debug Page</title>
<link rel="stylesheet" href="templates/install.css" type="text/css" media="screen" />
</head>
<body bgcolor="#f0f0f0">
<div id="header">
<h1><?php echo T_('Ampache Debug'); ?></h1>
<p><?php echo T_('You may have reached this page because a configuration error has occured. Debug information is below.'); ?></p>
</div>
<div>
<table align="center" cellpadding="3" cellspacing="0">
<tr>
    <td><font size="+1"><?php echo T_('CHECK'); ?></font></td>
    <td>
        <font size="+1"><?php echo T_('STATUS'); ?></font>
    </td>
    <td><font size="+1"><?php echo T_('DESCRIPTION'); ?></font></td>
</tr>
<tr>
    <td valign="top"><?php echo T_('PHP version'); ?></td>
    <td valign="top">[
    <?php
        if (!check_php_ver()) {
            echo debug_result('',false);
            if (function_exists('hash_algos')) { $algos = hash_algos(); }
            $string = "<strong>" .  phpversion() . " " . T_('Hash Function Exists') . " " . print_bool(function_exists('hash_algos')) . " " . T_('SHA256 Support') . " " . print_bool(in_array('sha256',$algos)) . "</strong>";
        }
        else {
            echo debug_result('',true);
        }
    ?>
    ]
    </td>
    <td>
    <?php echo T_('This tests whether you are running at least the minimum version of PHP required by Ampache.'); ?>
    <?php echo $string; ?>
    </td>
</tr>
<tr>
    <td valign="top"><?php echo T_('PHP session extension'); ?></td>
    <td valign="top">[
    <?php
        if (!check_php_session()) {
            echo debug_result('',false);
        }
        else {
            echo debug_result('',true);
        }
    ?>
    ]
    </td>
    <td>
    <?php echo T_('This tests whether you have the session extension enabled. This extension is required by Ampache.'); ?>
    </td>
</tr>
<tr>
    <td valign="top"><?php echo T_('PHP iconv extension'); ?></td>
    <td valign="top">[
    <?php
        if (!UI::check_iconv()) {
            echo debug_result('',false);
        }
        else {
            echo debug_result('',true);
        }
    ?>]
    </td>
    <td>
    <?php echo T_('This tests whether you have the iconv extension enabled. This extension is required by Ampache.'); ?>
    </td>
</tr>
<tr>
    <td valign="top"><?php echo T_('PHP safe mode disabled'); ?></td>
    <td valign="top">[
    <?php
        if (!check_safemode()) {
            echo debug_result('',false);
        }
        else {
            echo debug_result('',true);
        }
    ?>]
    </td>
    <td>
    <?php echo T_('This test makes sure that PHP is not running in safe mode.  Some features of Ampache will not work correctly in safe mode.'); ?>
    </td>
</tr>
<tr>
    <td valign="top"><?php echo T_('PHP memory limit override'); ?></td>
    <td valign="top">[
    <?php echo debug_result('', check_override_memory()); ?>]
    </td>
    <td>
    <?php echo T_('This tests whether Ampache can override the memory limit.  This is not strictly necessary, but may result in a better experience.'); ?>
    </td>
</tr>
<tr>
    <td valign="top"><?php echo T_('PHP execution time override'); ?></td>
    <td valign="top">[
    <?php echo debug_result('', check_override_exec_time()); ?>]
    </td>
    <td>
    <?php echo T_('This tests whether Ampache can override the limit on maximum execution time.  This is not strictly necessary, but may result in a better experience.'); ?>
    </td>
</tr>    
<tr>
    <td valign="top"><?php echo T_('Configuration file readability'); ?></td>
    <td valign="top">[
    <?php
        if (!is_readable($configfile)) {
            echo debug_result('',false);
        }
        else {
            echo debug_result('',true);
        }
    ?>
    ]
    </td>
    <td width="350px">
    <?php echo T_('This test attempts to read config/ampache.cfg.php. If this fails the file either is not in the correct location or is not currently readable.'); ?>
    </td>
</tr>
<tr>
    <td valign="top">
        <?php echo T_('Configuration file validity'); ?>
    </td>
    <td valign="top">[
    <?php
        $results = @parse_ini_file($configfile);
        Config::set_by_array($results);
        if (!check_config_values($results)) {
            echo debug_result('',false);
        }
        else {
            echo debug_result('',true);
        }
    ?>
    ]
    </td>
    <td>
    <?php echo T_("This test makes sure that you have set all of the required configuration variables and that we are able to completely parse your config file."); ?>
    </td>
</tr>
<tr>
    <td valign="top"><?php echo T_("Database connection"); ?></td>
    <td valign="top">[
    <?php
        if (!Dba::check_database()) {
            echo debug_result('',false);
        }
        else {
            echo debug_result('',true);
        }
    ?>
    ]
    </td>
    <td>
    <?php echo T_('This attempts to connect to your database using the values read from your configuration file.'); ?>
    </td>
</tr>
<tr>
    <td valign="top"><?php echo T_('Database tables'); ?></td>
    <td valign="top">[
    <?php
        $db_inserted = Dba::check_database_inserted();
        if (!$db_inserted) {
            echo debug_result('',false);
        }
        else {
            echo debug_result('',true);
        }
    ?>
    ]
    </td>
    <td>
    <?php echo T_('This checks a few key tables to make sure that you have successfully inserted the Ampache database and that the user has access to the database'); ?>
    </td>
</tr>
<tr>

    <td valign="top"><?php echo T_('Web path'); ?></td>
    <td valign="top">[
    <?php
        if ($results['force_ssl']) {
            $http_type = 'https://';
        }

        $results['web_path'] = $http_type . $_SERVER['HTTP_HOST'] . Config::get('web_path');
        if (check_config_values($results)) {
            echo "&nbsp;&nbsp;&nbsp;<img src=\"" . $results['web_path'] ."/images/icon_enable.png\" />&nbsp;&nbsp;&nbsp;";
        }
        else {
            echo debug_result('',false);
        }

    ?>
    ]
    </td>
    <td>
    <?php echo T_('This test makes sure that your web_path variable is set correctly and that we are able to get to the index page. If you do not see a check mark here then your web_path is not set correctly.'); ?>
    </td>
</tr>
</table>
</div>
<div id="bottom">
<p><strong>Ampache Debug.</strong><br />
Pour l'Amour de la Musique.</p>
</div>
</body>
</html>
