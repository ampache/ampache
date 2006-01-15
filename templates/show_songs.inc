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
$web_path = conf('web_path');

/* If it's a playlist and they've got rights */
if (is_object($playlist) && ($GLOBALS['user']->username == $playlist->user || $GLOBALS['user']->has_access('100'))) { 
	$tab = 1;
	$playlist_owner = true;
}

?>
<form name="songs" method="post" enctype="multipart/form-data" action="#" style="Display:inline;">
<table class="border" cellspacing="0" cellpadding="0" border="0">
<tr class="table-header">
	<th>&nbsp;&nbsp;<a href="#" onclick="check_songs(); return false;">Select</a></th>
	<?php if ($playlist_owner) { ?> 
		<th><?php echo _("Track"); ?></th> 
	<?php } ?>
	<th><?php echo _("Song title"); ?></th>
	<th><?php echo _("Artist"); ?></th>
	<th><?php echo _("Album"); ?></th>
	<th><?php echo _("Track"); ?></th>
	<th><?php echo _("Time"); ?></th>
	<th><?php echo _("Size"); ?></th>
	<th><?php echo _("Bitrate"); ?></th>
	<th><?php echo _("Genre"); ?></th>
	<th><?php echo _("Action"); ?></th>
	<?php if (conf('ratings') || conf('ratings')=="false") { ?>
		<th><?php echo _("Rating"); ?></th>
	<? } ?>            
</tr>
<?php
/* FIXME: don't even get me started with how many things are wrong with this code */ 
foreach ($song_ids as $song_id) {
	
	/* Arr matey crapy code abounds! */
	if (is_object($playlist)) { 
		if ($song_id['song']) { 
			$song = new Song($song_id['song']);
			$track_id = $song_id['id'];
		}
		else { 
			$song = new Song();
			$song->title = 'Dynamic Song';
			$track_id = $song_id['id'];
		}
	} // end if playlist
	elseif (!is_object($song_id)) { 
		unset($text_class);
		$song = new Song($song_id);
		$track_id = $song->id;
	}
	else { 
		$song = $song_id;
		$track_id = $song->id;
	}
	$song->format_song();
	// Still needed crap
	$totalsize += $song->size;
	$totaltime += $song->time;
	/* If it's disabled */
	if ($song->status == "disabled") { $text_class = "class=\"disabled\""; }
?>
<tr class="<?php echo flip_class(); ?>">
	<td align="center">
		<input type="checkbox" name="song[]" value="<?php echo $track_id; ?>" id="song_<?php echo $track_id; ?>" />
	</td>
		<?php
		if ($playlist_owner) {
			$tracknum = $playlist->get_track($track_id); ?>
			<td>
			<input type="text" tabindex="<?php echo $tab; ?>" size="3" name="<?php echo "tr_" . $track_id; ?>" value="<?php echo $tracknum; ?>" onchange="<?php echo "document.getElementById('song_" . $track_id . "').checked='checked';"; ?>" />
			</td>
			<?php $tab++;
		} ?>
	<td>
		<?php if ($song->has_flag()) { echo "<strong>**</strong>"; } ?>
		<a href="<?php echo $web_path; ?>/song.php?action=m3u&amp;song=<?php echo $song->id; ?>" title="<?php echo scrub_out($song->title); ?>" <?php echo $text_class; ?>><?php echo scrub_out($song->f_title); ?></a>
	</td>
	<td>
		<a href="<?php echo $web_path; ?>/artists.php?action=show&amp;artist=<?php echo htmlspecialchars($song->artist); ?>" title="<?php echo htmlspecialchars($song->f_artist_full); ?>" <?php echo $text_class; ?>><?php echo htmlspecialchars($song->f_artist); ?></a>
	</td>
	<td>
		<a href="<?php echo $web_path; ?>/albums.php?album=<?php echo htmlspecialchars($song->album); ?>" title="<?php echo htmlspecialchars($song->f_album_full); ?>" <?php echo $text_class; ?>><?php echo htmlspecialchars($song->f_album); ?></a>
	</td>
	<td align="right">
		<?php echo $song->track; ?>
	</td>
	<td align="right">
		<?php echo $song->f_time; ?>
	</td>
	<td align="right" nowrap="nowrap">
		<?php echo $song->f_size; ?> MB
	</td>
	<td align="right">
		<?php echo $song->f_bitrate; ?>
	</td>
	<td>
		<?php echo $song->f_genre; ?>
	</td>
	<td>
		<a href="<?php echo $web_path; ?>/flag.php?action=show_flag&amp;type=song&amp;id=<?php echo $song->id; ?>">Flag</a>
		<?php if ($GLOBALS['user']->has_access('100')) { ?>
				| <a href="<?php echo $web_path; ?>/admin/song.php?action=edit&amp;song=<?php echo $song->id; ?>">Edit</a> | 
				<?php if ($song->enabled) { ?>
				<a href="<?php echo $web_path; ?>/admin/song.php?action=disable&amp;song_ids=<?php echo $song->id; ?>">Disable</a>
				<?php } else { ?>
				<a href="<?php echo $web_path; ?>/admin/song.php?action=enabled&amp;song_ids=<?php echo $song->id; ?>">Enable</a>
				<?php } //status ?>
		<?php } //access ?>
		<?php if ($GLOBALS['user']->prefs['download']) { ?>
				| <a href="<?php echo $web_path; ?>/download/index.php?action=download&amp;song_id=<?php echo $song->id; ?>&amp;fn=<?php echo rawurlencode($song->f_artist_full . " - " . $song->title . "." . $song->type); ?>"><?php echo _("Download"); ?></a>
		<?php } ?>
		<?php if ($GLOBALS['user']->prefs['direct_link']) { ?>
			| <a href="<?php echo $web_path; ?>/play/index.php?song=<?php echo $song->id; ?>&amp;uid=<?php echo $GLOBALS['user']->username . "&amp;sid=" . session_id(); ?>&amp;fn=<?php echo rawurlencode($song->f_artist_full . " - " . $song->title . "." . $song->type); ?>"><?php echo _("Direct Link"); ?></a>
		<?php } ?>
	</td>
	<?php if(conf('ratings')) { ?>
		<td>
			<?php show_rating($song->id,'song'); ?>
		</td>
	<?php } ?>
</tr>
<?php
}// foreach loop

$time = floor($totaltime/60) . ":" . sprintf("%02d", ($totaltime%60) );
$megs = sprintf("%.2f", ($totalsize/1048576));
$num = count($song_ids);
?>
<tr class="table-header">
	<td></td>
	<?php if (is_object($playlist)) { ?> <td></td> <?php } ?>
	<td><?php echo _("Total"); ?>:</td>
	<td nowrap="nowrap"><?php echo $num; ?> song(s)</td>
	<td></td>
	<td></td>
	<td align="right" nowrap="nowrap"><?php echo $time; ?></td>
	<td align="right" nowrap="nowrap"><?php echo $megs; ?> MB</td>
	<td></td>
	<td></td>
	<td colspan="2"></td>
</tr>
</table>
<p class="header2"><?php echo _('** Indicates flagged songs'); ?></p>
<br />
<?php show_play_selected(); ?>
</form>

