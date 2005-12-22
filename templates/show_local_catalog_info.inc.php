<?php


?>
<table width="100%" class="border" cellpadding="0" cellspacing="0"> 
<tr class="table-header">
	<th colspan="2"><?php echo _("Catalog Statistics"); ?> </th>
</tr>
<tr class="<?php echo flip_class(); ?>">
	<td><?php echo _("Total Users"); ?></td>
	<td> <b><?php echo $users[0]; ?></b> </td>
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
	<td> <?php echo _("Catalog Time"); ?></td>
	<td><b><?php echo $time_text; ?></b></td>
</tr>
</table>
