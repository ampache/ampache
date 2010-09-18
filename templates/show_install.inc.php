<?php
/* vim:set tabstop=8 softtabstop=8 shiftwidth=8 noexpandtab: */
/*

 Copyright (c) Ampache.org
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
$prefix = realpath(dirname(__FILE__). "/../");
require $prefix . '/templates/install_header.inc.php';
?>
	<div class="content">
		<strong><?php echo _('Step 1 - Create the Ampache database'); ?></strong><br />
		<dl>
			<dd><?php echo _('This step creates and inserts the Ampache database, so please provide a MySQL account with database creation rights. This step may take some time on slower computers.'); ?></dd>
		</dl>
		<?php echo _('Step 2 - Create ampache.cfg.php'); ?><br />
		<?php echo _('Step 3 - Set up the initial account'); ?><br />
		<br />
		<?php Error::display('general'); ?>
		<br />
		<span class="header2"><?php echo _('Insert Ampache Database'); ?></span>
		<form method="post" action="<?php echo WEB_PATH . "?action=create_db&amp;htmllang=$htmllang&amp;charset=$charset"; ?>" enctype="multipart/form-data" >
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
	<td class="align"><?php echo _('Overwrite Existing'); ?></td>
	<td><input type="checkbox" name="overwrite_db" value="1" /></td>
</tr>
<tr>
	<td class="align"><?php echo _('Use Existing Database'); ?></td>
	<td><input type="checkbox" name="existing_db" value="1" /></td>
</tr>
<tr>
	<td>&nbsp;</td>
	<td><input type="submit" value="<?php echo _("Insert Database"); ?>" /></td>
</tr>
</table>
</form>
	</div>
	<div id="bottom">
    	<p><strong>Ampache Installation.</strong><br />
    	Pour l'Amour de la Musique</p>
   </div>
</div>

</body>
</html>
