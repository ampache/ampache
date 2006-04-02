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
/*!
@header Show Now Playing Ajax
*/


// would change this to input some with the current now playing data.
// so this same script can be used even if the ajax doesnt refresh it.
$c=0;
if (count($results)) {
foreach ($results as $item) {
	$song = $item['song'];
	$np_user = $item['user'];
	if (is_object($song)) {
		$result = $result.'<song>';
		if (!$np_user->fullname) { $np_user->fullname = "Unknown User"; }
		if (conf('use_auth')) { $result = $result.'<fullname>'.$np_user->fullname.'</fullname>';} 
		else { $result = $result.'<fullname></fullname>'; }
?>
		<div id='np_container_<?php echo $c; ?>' style="width:43.5em; display: block; z-index: -10000;">
		<?php if ($c==0) { ?>
			<table class="border" cellspacing="1" cellpadding="3" border="0" width="100%">
			<tr class="table-header">
				<td><?php echo _('Now Playing'); ?></td>
			</tr>
			</table>
		<?php } ?>
		<table class="border" cellspacing="1" cellpadding="3" border="0" width="100%">
		<tr class="even">
			<td width="20%">
				<div id="np_songid_<?php echo $c; ?>_holder" style="visibility: hidden; position:absolute; z-index: 10000;">
				<?php echo $song->id; ?>
				</div>
				<div id="np_fullname_<?php echo $c; ?>">
				<?php if (conf('use_auth')) { echo $np_user->fullname; } ?>
				</div>
			</td>
			<td width="30%">
				<div id="np_song_<?php echo $c; ?>">
				<a title="<?php echo scrub_out($song->f_title); ?>" href="<?php echo $web_path; ?>/song.php?action=single_song&amp;song_id=<?php echo $song->id; ?>">
				<?php echo $song->f_title; ?>
				</a>
				</div>
			</td>
			<td width="30%">
				<div id="np_albumartist_<?php echo $c; ?>">
				<a title="<?php echo scrub_out($song->f_album); ?>" href="<?php echo $web_path; ?>/albums.php?action=show&amp;album=<?php echo $song->album; ?>">
				<?php echo $song->f_album; ?>
				</a> /
				<a title="<?php echo scrub_out($song->f_artist); ?>" href="<?php echo $web_path; ?>/artists.php?action=show&amp;artist=<?php echo $song->artist; ?>">
				<?php echo $song->f_artist; ?>
				</a>
				</div>
			</td>
			<?php if (conf('play_album_art')) { ?>
			<td width="20%">
				<div id="np_img_<?php echo $c; ?>">
				<a target="_blank" href="<?php echo $web_path; ?>/albumart.php?id=<?php echo $song->album; ?>&amp;type=popup" onclick="popup_art('<?php echo $web_path; ?>/albumart.php?id=<?php echo $song->album; ?>&amp;type=popup'); return false;">
				<img align="middle" border="0" src="<?php echo $web_path; ?>/albumart.php?id=<?php echo $song->album; ?>&amp;fast=1&amp;thumb=1" alt="Album Art" height="75" />
				</a>
				</div>
			</td>
			<?php } ?>
		</tr>
		</table>
		</div>
<?php
	$c++;
	} // end if (is_object($song))
} // end foreach($results as $item)
} // end if (count($results))
for ($x=$c; $x<5; $x++) {
?>
	<div id='np_container_<?php echo $x; ?>' style="display: none; z-index: -10000;">
	<?php
	if ($x==0) { ?>
		<table class="border" cellspacing="1" cellpadding="3" border="0" width="100%">
		<tr class="table-header">
			<td><?php echo _('Now Playing'); ?></td>
		</tr>
		</table>
	<?php } // end if ($x==0)?>
		<table class="border" cellspacing="1" cellpadding="3" border="0" width="100%">
		<tr class="even">
			<td width="20%">
				<div id="np_songid_<?php echo $x; ?>_holder" style="visibility: hidden; position:absolute; z-index: 10000;"></div>
				<div id="np_fullname_<?php echo $x; ?>"></div>
			</td>
			<td width="30%"><div id="np_song_<?php echo $x; ?>"></div></td>
			<td width="30%"><div id="np_albumartist_<?php echo $x; ?>"></div></td>
			<td width="20%"><div id="np_img_<?php echo $x; ?>"></div></td>
		</tr>
		</table>
	</div>
<?php
} //end  for ($x=$c; $x<5; $x++)
?>
<br />

