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
<?php show_box_top($working_user->fullname . ' ' . _('IP History')); ?>
<div class="text-action">
<?php if (isset($_REQUEST['all'])){ ?>
	<a href="<?php echo conf('web_path')?>/admin/users.php?action=show_ip_history&user_id=<?php echo $working_user->id?>"><?php echo _('Show Unique'); ?>...</a>
<?php }else{ ?>
	<a href="<?php echo conf('web_path')?>/admin/users.php?action=show_ip_history&user_id=<?php echo $working_user->id?>&all"><?php echo _('Show All'); ?>...</a>
<?php }?>
</div>
<table border="0">
<tr class="table-header">
        <td align="center">
     		<?php echo _('Date'); ?>
     	</td>
     	<td align=\"center\">
     		<?php echo _('IP Address'); ?>
     	</td>
</tr>
<?php foreach ($history as $data) { ?>
<tr class="<?php echo flip_class(); ?>">
	<td>
		<?php echo date("d/m/Y H\hi",$data['date']); ?>
	</td>
	<td>
		<?php echo int2ip($data['ip']); ?>
	</td>
</tr>
<?php } ?>
</table>
<?php show_box_bottom(); ?>
