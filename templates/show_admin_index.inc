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
	@header Show Admin Index
	@discussion shows the admin index 
*/
?>
<p><font size="+1"><?php print _("Admin Section"); ?>:</font></p>
<dl>
	<li><a href="<?php echo conf('web_path'); ?>/admin/users.php"><?php print _("Users"); ?></a> - <?php print _("Create/Modify User Accounts for Ampache"); ?></li>
	<li><a href="<?php echo conf('web_path'); ?>/admin/mail.php"><?php print _("Mail"); ?></a> - <?php print _("Mail your users to notfiy them of changes"); ?></li>
	<li><a href="<?php echo conf('web_path'); ?>/admin/catalog.php"><?php print _("Catalog"); ?></a> - <?php print _("Create/Update/Clean your catalog here"); ?></li>
	<li><a href="<?php echo conf('web_path'); ?>/admin/preferences.php"><?php print _("Admin Preferences"); ?></a> - <?php print _("Modify Site-wide preferences"); ?></li>
	<li><a href="<?php echo conf('web_path'); ?>/admin/access.php"><?php print _("Access Lists"); ?></a> - <?php print _("Modify Access List Permissions"); ?> (<?php print _("Must have access_control=true in ampache.cfg") ?>)</li>
</dl>

