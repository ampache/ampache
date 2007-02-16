<?php
/*

 Copyright (c) 2001 - 2005 Ampache.org
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

//FIXME: I don't like having to re-create this friggin object.. :(
$artist_id = $artist->id;
$web_path = conf('web_path');
?>
<?php require (conf('prefix') . '/templates/show_artist_box.inc.php'); ?>
<!-- *** Multi-Album Art Display Thx MrBlahh Updated by clader *** -->
<?php show_box_top(); ?>
<form id="songs" method="post" enctype="multipart/form-data" action="artists.php">
<table class="tabledata" cellspacing="0" cellpadding="0" border="0">
<tr class="table-header">
	<th align="center">
	&nbsp;&nbsp;<a href="#" onclick="check_select('song'); return false;"><?php echo _('Select'); ?></a>
	</th>
	<th><?php echo _('Cover'); ?></th>
	<th><a href="<?php echo $web_path; ?>/<?php echo $_SESSION['view_script']; ?>?action=<?php echo $_REQUEST['action']; ?>&amp;artist=<?php echo $artist->id  ?>&amp;keep_view=true&amp;sort_type=album.name&amp;sort_order=0"><?php echo _('Album Name'); ?></th>
	<th><a href="<?php echo $web_path; ?>/<?php echo $_SESSION['view_script']; ?>?action=<?php echo $_REQUEST['action']; ?>&amp;artist=<?php echo $artist->id  ?>&amp;keep_view=true&amp;sort_type=album.year&amp;sort_order=0"><?php echo _('Album Year'); ?></th>
	<th><?php echo _('Tracks'); ?></th>
	<th><?php echo _('Action'); ?></th>
</tr>
<?php
foreach ($albums as $album) {
	$id = $album->id;
	$album_name = $album->name;
	$count = $album->songs;
?>
	<tr class="<?php echo flip_class(); ?>">
		<td align="center">
		<input name="song[]" value="<?php echo $id; ?>" type="checkbox" />
		</td>
		<td height="87">
		<a href="<?php echo $web_path; ?>/albums.php?action=show&amp;album=<?php echo $id; ?>&amp;artist=<?php echo $artist->id; ?>">
		<img border="0" src="<?php echo $web_path; ?>/image.php?id=<?php echo $id; ?>&amp;thumb=1&amp;sid=<?php echo session_id(); ?>" alt="<?php echo scrub_out($album_name); ?>" title="<?php echo scrub_out($album_name); ?>" height="75" width="75" />
		</a>
		</td>
		<td>
		<a href="<?php echo $web_path; ?>/albums.php?action=show&amp;album=<?php echo $id; ?>&amp;artist=<?php echo $artist->id; ?>"><?php echo $album_name; ?></a>
		</td>
		<td><?php echo $album->year; ?></td>
		<td><?php echo $count; ?></td>
		<td>
		<a href="<?php echo $web_path; ?>/song.php?action=album&amp;album_id=<?php echo $id; ?>">
			<?php echo get_user_icon('all'); ?>	
		</a>
		<a href="<?php echo $web_path; ?>/song.php?action=album_random&amp;album_id=<?php echo $album->id; ?>">
			<?php echo get_user_icon('random'); ?>
		</a>
		<?php if (batch_ok()) { ?>
			<a href="<?php echo $web_path; ?>/batch.php?action=alb&amp;id=<?php echo $album->id; ?>">
				<?php echo get_user_icon('batch_download'); ?>
			</a>
		<?php } ?> 
		<?php if ($GLOBALS['user']->has_access('100')) { ?>
			<a href="<?php echo $web_path; ?>/admin/flag.php?action=show_edit_album&amp;album_id=<?php echo $album->id; ?>">
				<?php echo get_user_icon('edit'); ?>
			</a>
		<?php } ?>
		</td>
	</tr>
<?php  } //end foreach ($albums as $album)?>
</table>
<br /><br />

<?php show_play_selected(); ?>
<input type="hidden" name="type" value="album" />
<input type="hidden" name="artist_id" value="<?php echo $artist_id; ?>" />
</form>
<?php show_box_bottom(); ?>
