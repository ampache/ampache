<?php
/*

 Copyright (c) 2001 - 2007 Ampache.org
 All rights reserved.

 This program is free software; you can redistribute it and/or
 modify it under the terms of the GNU General Public License v2
 as published by the Free Software Foundation. 

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with this program; if not, write to the Free Software
 Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
*/

$web_path = Config::get('web_path');

?>
<table class="tabledata" cellspacing="0" cellpadding="0" border="0">
<tr class="table-header" align="center">
	<td colspan="5">
	<?php if ($GLOBALS['view']->offset_limit) { require Config::get('prefix') . '/templates/list_header.inc'; } ?>
	</td>
</tr>
<tr class="table-header">
	<td><?php echo _('Add'); ?>
	<td><?php echo _('Artist'); ?></td>
	<td> <?php echo _('Songs');  ?> </td>
	<td> <?php echo _('Albums'); ?> </td>
	<td> <?php echo _('Action'); ?> </td>
</tr>
<?php 
/* Foreach through every artist that has been passed to us */
//FIXME: These should come in as objects...
foreach ($object_ids as $artist_id) { 
	$artist = new Artist($artist_id); 
	$artist->format(); 
?>
	<tr class="<?php echo flip_class(); ?>">
		<td>
			<span onclick="ajaxPut('<?php echo Config::get('ajax_url'); ?>?action=basket&amp;type=artist&amp;id=<?php echo $artist->id; ?>');return true;" >
				<?php echo get_user_icon('add'); ?>	
			</span>
			<span onclick="ajaxPut('<?php echo Config::get('ajax_url'); ?>?action=basket&amp;type=artist_random&amp;id=<?php echo $artist->id; ?>');return true;" >
				<?php echo get_user_icon('random'); ?>
			</span> 
		</td>
		<td><?php echo $artist->f_name_link; ?></td>
		<td><?php echo $artist->songs; ?></td>
		<td><?php echo $artist->albums; ?></td>	
		<td nowrap="nowrap"> 
		<?php if (Access::check_function('batch_download')) { ?>
                        <a href="<?php echo Config::get('web_path'); ?>/batch.php?action=artist&amp;id=<?php echo $artist->id; ?>">
                                <?php echo get_user_icon('batch_download','',_('Batch Download')); ?>
                        </a>
                <?php } ?>
		<?php if ($GLOBALS['user']->has_access(100)) { ?>
			<a href="<?php echo $web_path; ?>/admin/flag.php?action=show_edit_artist&amp;artist_id=<?php echo $artist->id; ?>">
				<?php echo get_user_icon('edit'); ?>
			</a>
		<?php } ?>
		</td>
	</tr>
<?php } //end foreach ($artists as $artist) ?>
<tr class="table-header">
	<td><?php echo _('Add'); ?>
        <td><?php echo _("Artist"); ?></td>
        <td><?php echo _('Songs');  ?></td>
        <td><?php echo _('Albums'); ?></td>
	<td><?php echo _('Action'); ?></td>

</tr>
<tr class="even" align="center">
	<td colspan="4">
	<?php if ($view->offset_limit) { require (conf('prefix') . "/templates/list_header.inc"); } ?>
	</td>
</tr>
</table>
