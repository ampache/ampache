<?php
/*

 Copyright (c) 2001 - 2006 Ampache.org
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
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?php echo $htmllang; ?>" lang="<?php echo $htmllang; ?>">
<head>
<title>Ampache :: For The Love Of Music - Install</title>
<?php require_once(conf('prefix') . "/templates/install.css"); ?>
</head>
<body>
<script src="lib/javascript-base.js" language="javascript" type="text/javascript"></script>
<div id="header"> 
<h1><?php echo _("Ampache Installation"); ?></h1>
<p>For the love of Music</p>
</div>
<div id="text-box">

	<div class="notify">
		<b><?php echo _("Requirements"); ?></b>
		<p>
		<?php echo _("This Page handles the installation of the Ampache database and the creation of the ampache.cfg.php file. Before you continue please make sure that you have the following pre-requisites"); ?>
		</p>
		<ul>
			<li><?php echo _("A MySQL Server with a username and password that can create/modify databases"); ?></li>
			<li><?php echo _("Your webserver has read access to the /sql/ampache.sql file and the /config/ampache.cfg.php.dist file"); ?></li>
		</ul>
		<p>
<?php echo _("Once you have ensured that you have the above requirements please fill out the information below. You will only be asked for the required config values. If you would like to make changes to your ampache install at a later date simply edit /config/ampache.cfg.php"); ?>
		</p>
	</div>
	
	<div class="content">
		<b><?php echo _("Step 1 - Creating and Inserting the Ampache Database"); ?></b><br />
		<dl>
			<dd><?php echo _("This step creates and inserts the Ampache database, as such please provide a mysql account with database creation rights. This step may take a while depending upon the speed of your computer"); ?></dd>
		</dl>
		<?php echo _("Step 2 - Creating the Ampache.cfg.php file"); ?><br />
		<?php echo _("Step 3 - Setup Initial Account"); ?><br />
		<br /><br />
		<span class="header2">Insert Ampache Database</span>
		<?php echo $GLOBALS['error']->print_error('general'); ?>
		<form method="post" action="<?php echo $http_type . $_SERVER['HTTP_HOST'] .  $_SERVER['PHP_SELF'] . "?action=create_db&amp;htmllang=$htmllang&amp;charset=$charset"; ?>" enctype="multipart/form-data" >
<table>
<tr>
	<td class="align"><?php echo _("Desired Database Name"); ?></td>
	<td><input type="text" name="local_db" value="ampache" /></td>
</tr>
<tr>
	<td class="align"><?php echo _("MySQL Hostname"); ?></td>
	<td><input type="text" name="local_host" value="localhost" /></td>
</tr>
<tr>
	<td class="align"><?php echo _("MySQL Administrative Username"); ?></td>
	<td><input type="text" name="local_username" value="root" /></td>
</tr>
<tr>
	<td class="align"><?php echo _("MySQL Administrative Password"); ?></td>
	<td><input type="password" name="local_pass" /></td>
</tr>
<tr>
	<td class="align"><?php echo _("Create Database User for New Database"); ?>? </td>
	<td><input type="checkbox" value="create_db_user" name="db_user" onclick="flipField('db_username');flipField('db_password');" /></td>
</tr>
<tr>
	<td class="align"><?php echo _("Ampache Database Username"); ?></td>
	<td><input type="text" id="db_username" name="db_username" value="ampache" disabled="disabled" /></td>
</tr>
<tr>
	<td class="align"><?php echo _("Ampache Database User Password"); ?></td>
	<td><input type="password" id="db_password" name="db_password" value="" disabled="disabled" /></td>
</tr>
<tr>
	<td>&nbsp;</td>
	<td><input type="submit" value="<?php echo _("Insert Database"); ?>" /></td>
</tr>
	
		</table>
		</form>
		
	</div>
	<div id="bottom">
    	<p><b>Ampache Installation.</b><br />
    	For the love of Music.</p>
   </div>
</div>

</body>
</html>
