<?php
/*

 Copyright (c) 2001 - 2005 Ampache.org
 All rights reserved.

 This program is free software; you can redistribute it and/or
 modify it under the terms of the GNU General Public License
 as published by the Free Software Foundation; either version 2
 of the License, or (at your option) any later version.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with this program; if not, write to the Free Software
 Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.

*/
/*!
 @header Show Install Config File

*/

?>
<html>
<head>
<title>Ampache :: For The Love Of Music - Install</title>
</head>
<body>
<?php require_once(conf('prefix') . "/templates/style.inc"); ?>
<div class="text-box">
<span class="header1"><?php echo _("Ampache Installation"); ?></span>
<p>
<?php echo _("This Page handles the installation of the Ampache database and the creation of the ampache.cfg.php file. Before you continue please make sure that you have the following pre-requisites"); ?>
</br />
<ul>
        <li><?php echo _("A MySQL Server with a username and password that can create/modify databases"); ?></li>
        <li><?php echo _("Your webserver has read access to the /sql/ampache.sql file and the /config/ampache.cfg.php.dist file"); ?></li>
</ul>
<?php echo _("Once you have ensured that you have the above requirements please fill out the information below. You will only be asked for the required config values. If you would like to make changes to your ampache install at a later date simply edit /config/ampache.cfg.php"); ?>
</p>
</div>

<div class="text-box">
<?php echo _("Step 1 - Creating and Inserting the Ampache Database"); ?><br />
<b><?php echo _("Step 2 - Creating the Ampache.cfg.php file"); ?></b><br />
<dl>
	<dd><?php echo _("This steps takes the basic config values, and first attempts to write them out directly to your webserver. If access is denied it will prompt you to download the config file. Please put the downloaded config file in /config"); ?></dd>
</dl>
<?php echo _("Step 3 - Setup Initial Account"); ?></a><br />
<br /><br />
<span class="header2">Generate Config File</span>
<?php echo $GLOBALS['error']->print_error('general'); ?>
<form method="post" action="<?php echo $_SERVER['PHP_SELF'] . "?action=create_config"; ?>" enctype="multipart/form-data" >
<table cellpadding="5" cellspacing="0" border="0">
<tr>
	<td><?php echo _("Web Path"); ?></td>
	<td><input type="textbox" name="web_path" value="<?php echo $web_path; ?>" /></td>
</tr>
<tr>
	<td><?php echo _("Desired Database Name"); ?></td>
	<td><input type="textbox" name="local_db" value="<?php echo scrub_out($_REQUEST['local_db']); ?>" /></td>
</tr>
<tr>
	<td><?php echo _("MySQL Hostname"); ?></td>
	<td><input type="textbox" name="local_host" value="<?php echo scrub_out($_REQUEST['local_host']); ?>" /></td>
</tr>
<tr>
	<td><?php echo _("MySQL Username"); ?></td>
	<td><input type="textbox" name="local_username" value="<?php echo scrub_out($_REQUEST['local_username']); ?>" /></td>
</tr>
<tr>
	<td><?php echo _("MySQL Password"); ?></td>
	<td><input type="password" name="local_pass" value="" /></td>
</tr>
<tr>
	<td>&nbsp;</td>
	<td><input type="submit" value="<?php echo _("Write Config"); ?>" /></td>
</tr>
</table>
</form>
<br />
<table border="0" cellpadding="0">
<tr>
        <td valign="top"><?php echo _("Ampache.cfg.php Exists"); ?></td>
        <td valign="top">[ 
        <?php
                if (!read_config_file($configfile)) {
                        $status['read_config'] = 'false';
                        echo " <font color=\"red\">ERROR</font> "; 
                }
                else {
                        $status['read_config'] = 'true';
                        echo " <font color=\"green\">&nbsp;&nbsp;&nbsp;OK&nbsp;&nbsp&nbsp;&nbsp;</font> ";
                }
        ?>
        ]
        </td>
</tr>
<tr>
        <td valign="top">
                <?php echo _("Ampache.cfg.php Configured?"); ?>
        </td>
        <td valign="top">[ 
        <?php
                $results = read_config($configfile, 0, 0);
                if (!check_config_values($results)) { 
                        $status['parse_config'] = 'false';
                        echo " <font color=\"red\">ERROR</font> ";
                }
                else {
                        $status['parse_config'] = 'true';
                        echo " <font color=\"green\">&nbsp;&nbsp;&nbsp;OK&nbsp;&nbsp;&nbsp;&nbsp;</font> ";
                }
        ?>
        ]
        </td>
</tr>
<tr>
	<td>&nbsp;</td>
	<td>
	<a href="<?php echo $http_type . $_SERVER['HTTP_HOST'] . $GLOBALS['php_self']; ?>?action=show_create_config&local_db=<?php echo $_REQUEST['local_db']; ?>&local_host=<?php echo $_REQUEST['local_host']; ?>">[<?php echo _("Check for Config"); ?>]</a>
	</td>
</tr>
</table>
<br />
<form method="post" action="<?php echo $GLOBALS['php_self'] . "?action=show_create_account"; ?>" enctype="multipart/form-data">
<input type="Submit" value="Continue to Step 3" />
</form>
</div>

</body>
</html>

