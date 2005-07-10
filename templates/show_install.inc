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
 @header Show Install Form and instructions

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
<b><?php echo _("Step 1 - Creating and Inserting the Ampache Database"); ?></b><br />
<dl>
	<dd><?php echo _("This step creates and inserts the Ampache database, as such please provide a mysql account with database creation rights. This step may take a while depending upon the speed of your computer"); ?></dd>
</dl>
<?php echo _("Step 2 - Creating the Ampache.cfg.php file"); ?><br />
<?php echo _("Step 3 - Setup Initial Account"); ?><br />
<br /><br />
<span class="header2">Insert Ampache Database</span>
<form method="post" action="<?php echo $http_type . $_SERVER['HTTP_HOST'] .  $_SERVER['PHP_SELF'] . "?action=create_db"; ?>" enctype="multipart/form-data" >
<table cellpadding="5" cellspacing="0" border="0">
<tr>
	<td><?php echo _("Desired Database Name"); ?></td>
	<td><input type="textbox" name="local_db" value="ampache" /></td>
</tr>
<tr>
	<td><?php echo _("MySQL Hostname"); ?></td>
	<td><input type="textbox" name="local_host" value="localhost" /></td>
</tr>
<tr>
	<td><?php echo _("MySQL Administrative Username"); ?></td>
	<td><input type="textbox" name="local_username" value="root" /></td>
</tr>
<tr>
	<td><?php echo _("MySQL Administrative Password"); ?></td>
	<td><input type="password" name="local_pass" /></td>
</tr>
<tr>
	<td>&nbsp;</td>
	<td><input type="submit" value="<?php echo _("Insert Database"); ?>" /></td>
</tr>
	
</table>
</form>
</div>

</body>
</html>
