<?php
/*

 Copyright (c) Ampache.org
 All rights reserved.

 This program is free software; you can redistribute it and/or
 modify it under the terms of the GNU General Public License
1 as published by the Free Software Foundation; either version 2
 of the License, or (at your option) any later version.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with this program; if not, write to the Free Software
 Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.

*/
if (INSTALL != '1') { exit; } 
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?php echo $htmllang; ?>" lang="<?php echo $htmllang; ?>">
<head>
<title>Ampache :: For The Love Of Music - Install</title>
<link rel="stylesheet" href="templates/install.css" type="text/css" media="screen" />
<meta http-equiv="Content-Type" content="text/html; Charset=<?php echo $charset; ?>" />
</head>
<body>
<div id="header"> 
<h1><?php echo _('Ampache Installation'); ?></h1>
<p>Pour l'Amour de la Musique</p>
</div>
<div id="text-box">

	<div class="notify">
		<?php echo _("This Page handles the installation of the Ampache database and the creation of the ampache.cfg.php file. Before you continue please make sure that you have the following pre-requisites"); ?>
		<br />
		<ul>
        	<li><?php echo _("A MySQL Server with a username and password that can create/modify databases"); ?></li>
        	<li><?php echo _("Your webserver has read access to the /sql/ampache.sql file and the /config/ampache.cfg.php.dist file"); ?></li>
		</ul>
		<?php echo _("Once you have ensured that you have the above requirements please fill out the information below. You will only be asked for the required config values. If you would like to make changes to your ampache install at a later date simply edit /config/ampache.cfg.php"); ?>
	</div>

	<div class="content">
		<?php echo _("Step 1 - Creating and Inserting the Ampache Database"); ?><br />
		<strong><?php echo _("Step 2 - Creating the Ampache.cfg.php file"); ?></strong><br />
		<dl>
		<dd><?php echo _("This steps takes the basic config values and generates the config file. It will prompt you to download the config file. Please put the downloaded config file in /config"); ?></dd>
		</dl>
		<?php echo _("Step 3 - Setup Initial Account"); ?><br />
		<?php Error::display('general'); ?>
		<br />

<span class="header2"><?php echo _('Generate Config File'); ?></span>
<?php Error::display('config'); ?>
<form method="post" action="<?php echo WEB_PATH . "?action=create_config"; ?>" enctype="multipart/form-data" >
<table>
<tr>
	<td class="align"><?php echo _('Web Path'); ?></td>
	<td class="align"><input type="text" name="web_path" value="<?php echo $web_path; ?>" /></td>
</tr>
<tr>
	<td class="align"><?php echo _('Desired Database Name'); ?></td>
	<td class="align"><input type="text" name="local_db" value="<?php echo scrub_out($_REQUEST['local_db']); ?>" /></td>
</tr>
<tr>
	<td class="align"><?php echo _('MySQL Hostname'); ?></td>
	<td class="align"><input type="text" name="local_host" value="<?php echo scrub_out($_REQUEST['local_host']); ?>" /></td>
</tr>
<tr>
	<td class="align"><?php echo _('MySQL Username'); ?></td>
	<td class="align"><input type="text" name="local_username" value="<?php echo scrub_out($_REQUEST['local_username']); ?>" /></td>
</tr>
<tr>
	<td class="align"><?php echo _('MySQL Password'); ?></td>
	<td class="align"><input type="password" name="local_pass" value="" /></td>
</tr>
<tr>
	<td>&nbsp;</td>
	<td><input type="submit" value="<?php echo _('Write Config'); ?>" /></td>
</tr>
		</table>
		</form>
		<br />
		<table>
<tr>
        <td class="align"><?php echo _('Ampache.cfg.php Exists'); ?></td>
        <td>[ 
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
</tr>
<tr>
        <td class="align">
                <?php echo _('Ampache.cfg.php Configured?'); ?>
        </td>
        <td>[ 
        <?php
                $results = @parse_ini_file($configfile);
                if (!check_config_values($results)) { 
			echo debug_result('',false); 
                }
                else {
			echo debug_result('',true); 
                }
        ?>
        ]
        </td>
</tr>
<tr>
	<td>&nbsp;</td>
	<td>
	<?php $check_url = WEB_PATH . "?action=show_create_config&amp;htmllang=$htmllang&amp;charset=$charset&amp;local_db=" . $_REQUEST['local_db'] . "&amp;local_host=" . $_REQUEST['local_host']; ?>
	<a href="<?php echo $check_url; ?>">[<?php echo _('Check for Config'); ?>]</a>
	</td>
		</tr>
		</table>
		<br />
		<form method="post" action="<?php echo WEB_PATH . "?action=show_create_account&amp;htmllang=$htmllang&amp;charset=$charset"; ?>" enctype="multipart/form-data">
		<input type="submit" value="<?php echo _('Continue to Step 3'); ?>" />
		</form>
	</div>
	<div id="bottom">
    	<p><strong>Ampache Installation.</strong><br />
    	For the love of Music.</p>
	</div>
</div>

</body>
</html>

