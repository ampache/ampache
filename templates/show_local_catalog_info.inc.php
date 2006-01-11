<?php
/*

 Copyright (c) 2001 - 2006 Ampache.org
 All Rights Reserved

 this program is free software; you can redistribute it and/or
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
<table width="100%" class="border" cellpadding="0" cellspacing="0"> 
<tr class="table-header">
	<th colspan="2"><?php echo _("Catalog Statistics"); ?> </th>
</tr>
<tr class="<?php echo flip_class(); ?>">
	<td><?php echo _("Total Users"); ?></td>
	<td><b><?php echo $users[0]; ?></b></td>
  </tr>
<tr class="<?php echo flip_class(); ?>">
	<td><?php echo _("Connected Users"); ?></td>
	<td><b><?php echo $connected_users[0]; ?></b></td>
</tr>
<tr class="<?php echo flip_class(); ?>">
	<td><?php echo _("Albums"); ?></td>
	<td><b><?php echo $albums[0]; ?></b></td>
</tr>
<tr class="<?php echo flip_class(); ?>">
	<td><?php echo _("Artists"); ?></td>
	<td><b><?php echo $artists[0]; ?></b></td>
</tr>
<tr class="<?php echo flip_class(); ?>">
	<td><?php echo _("Songs"); ?></td>
	<td><b><?php echo $songs['songs']; ?></b></td>
</tr>
<tr class="<?php echo flip_class(); ?>">
	<td><?php echo _("Catalog Size"); ?></td>
	<td><b><?php echo $total_size; ?> <?php echo $size_unit; ?></b></td>
</tr>
<tr class="<?php echo flip_class(); ?>">
	<td><?php echo _("Catalog Time"); ?></td>
	<td><b><?php echo $time_text; ?></b></td>
</tr>
</table>
