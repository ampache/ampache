<?php
/*

 Copyright (c) 2004 Ampache.org
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
	@header Add Access List Entry
	A template file

*/

?>

<p style="font-size: 10pt; font-weight: bold;"><?php print _("Add Access for a Host"); ?></p>

<p><?php print _("Use the form below to add a host that you want to have access to your Ampache catalog."); ?></p>


<form name="update_catalog" method="post" enctype="multipart/form-data" action="<?php echo conf('web_path'); ?>/admin/access.php">

<table cellpadding="5" cellspacing="0" border="0">
        <tr>
                <td><?php print _("Name"); ?>: </td>
		<td>
			<input type="text" name="name" value="<?php echo $_REQUEST['name']; ?>" size="30" />
		</td>
        </tr>
        <tr>
                <td><?php print _("Start IP Address"); ?>:</td>
		<td>
			<input type="text" name="start" value="<?php echo $_REQUEST['start']; ?>" size="20" maxlength="15" />
		</td>
        </tr>
	<tr>
		<td><?php print _("End IP Address"); ?>:</td>
		<td>
			<input type="text" name="end" value="<?php echo $_REQUEST['end']; ?>" size="20" maxlength="15" />
		</td>
	</tr>
	<tr>
		<td><?php print _("Level"); ?>:</td>
		<td>
			<select name="level">
				<option selected="selected" value="5" >Demo</option>
				<option value="25">Stream</option>
				<option value="50">Stream/Download</option>
				<option value="75">XML-RPC</option>
			</select>
		</td>
	</tr>
        <tr>
                <td>&nbsp;</td>
		<td>
			<input type="hidden" name="action" value="add_host" />
			<input type="submit" value="<?php print _("Add Host"); ?>" />
		</td>
        </tr>
</table>
</form>
