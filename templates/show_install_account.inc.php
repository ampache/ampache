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
<?php echo _("This Page handles the installation of the ampache database and the creation of the ampache.cfg.php file. Before you continue please make sure that you have the following pre-requisits"); ?>
</br />
<ul>
        <li><?php echo _("A MySQL Server with a username and password that can create/modify databases"); ?></li>
        <li><?php echo _("Your webserver has read access to the /sql/ampache.sql file and the /config/ampache.cfg.dist.php file"); ?></li>
</ul>
<?php echo _("Once you have ensured that you have the above requirements please fill out the information below. You will only be asked for the required config values. If you would like to make changes to your ampache install at a later date simply edit /config/ampache.cfg.php"); ?>
</p>
</div>

<div class="text-box">
<?php echo _("Step 1 - Creating and Inserting the Ampache Database"); ?><br />
<?php echo _("Step 2 - Creating the ampache.cfg.php file"); ?><br />
<b><?php echo _("Step 3 - Setup Initial Account"); ?></b><br />
<dl>
	<dd><?php echo _("This step creates your initial Ampache admin account. Once your admin account has been created you will be directed to the login page"); ?></dd>
</dl>
<br /><br />
<span class="header2">Create Admin Account</span>
<form method="post" action="<?php echo $_SERVER['PHP_SELF'] . "?action=create_account"; ?>" enctype="multipart/form-data" >
<table cellpadding="5" cellspacing="0" border="0">
<tr>
	<td><?php echo _("Username"); ?></td>
	<td><input type="textbox" name="local_username" value="admin" /></td>
</tr>
<tr>
	<td><?php echo _("Password"); ?></td>
	<td><input type="password" name="local_pass" value="" /></td>
</tr>
<tr>
	<td>&nbsp;</td>
	<td><input type="submit" value="<?php echo _("Create Account"); ?>" /></td>
</tr>
</table>
</form>
</div>

</body>
</html>

