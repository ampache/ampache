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
$web_path = conf('web_path');
/*  

TTD:
- It would be nice if flagged songs showed up in a flagged color (e.g. red).
- Accept one or more parameters by which  the caller specify which columns are displayed.
- What is the best way to deal with the play pointer when stopped.  MPD doesn't report a "position" but stop restarts at the song
  that was playing before the stop.

*/


$nopad  = "style='padding: 0px 0px 0px 0px'";
$minpad = "style='padding: 0px 2px 0px 2px'";

$myMpd = init_mpd();
?>

<td colspan="2" valign="top">
<form name="songs" action="<?php echo conf('web_path')."/amp-mpd.php"; ?>" method="post" enctype="multipart/form-data">
<table border="0" cellpadding="0" cellspacing="1" width="100%" class="tabledata">
<tr>
	<td align="center" class="table-header">
		<b><?php echo _("MPD Server Playlist"); ?></b><br />
		<i>
			[<a title="<?php echo _("Refresh the Playlist Window"); ?>" href="<?php echo $_SERVER['PHP_SELF']; ?>">refresh</a>]
			<?php if( $myMpd->playlist_count > 0  ) { $un = (conf('condPL')) ? "un" : ""; ?>
				[<a title="<?php echo _("Click to shuffle (randomize) the playlist");?>" href="<?php echo conf('web_path'); ?>/amp-mpd.php?action=shuffle"><?php echo _("shuffle")?></a>]
				[<a title="<?php echo _("Click to the clear the playlist");?>" href="<?php echo conf('web_path'); ?>/amp-mpd.php?action=clear">clear</a>]
			<?php if( $myMpd->playlist_count > 10 ) { ?>
				[<a title="<?php echo 'Click to '.$un.'condense playlist';?>" href="<?php echo conf('web_path'); ?>/amp-mpd.php?action=condPL"><?php echo $un?>condense</a>]
			<?php } ?>
				[<a title="<?php echo _("Click to the remove all except the Now Playing");?>" href="<?php echo conf('web_path'); ?>/amp-mpd.php?action=crop">crop</a>]
			<?php } ?> 
		</i>
	</td>
</tr>
<tr>
	<td <?php echo $nopad ?>>
		<table cellspacing="0" cellpadding="0" border="0" width="100%">
			<tr class="table-header">
				<th <?php echo $minpad ?>><a href="#" onclick="check_songs(); return false;">Select</a> - <a href="#" onclick="invert_songs(); return false;">Invert</a></th>
				<th align="left"><?php echo _("Song title"); ?></th>
				<th align="left"><?php echo _("Artist"); ?></th>
				<th align="left"><?php echo _("Album"); ?></th>
				<th align="right" <?php echo $minpad ?>><?php echo _("Track"); ?></th>
				<th align="right" <?php echo $minpad ?>><?php echo _("Time"); ?></th>
				<th <?php echo $minpad ?>><?php echo _("Genre"); ?></th>
				<th><?php echo _("Action"); ?></th>
			</tr>
			<?php
				$pl = $myMpd->playlist;
				if (is_null($pl)) echo "ERROR: ".$myMpd->errStr."\n";
				else {
					$maxlen = strlen (count($pl));
					$condPL = conf('condPL');
					if ($condPL) {
						echo "<tr class=\"".flip_class()."\"><td colspan=\"8\" align=\"center\" style=\"padding: 3px 0px 3px 0px\">... Condensed Playlist ...</td></tr>";
					}
					else {
						echo "<tr><td>&nbsp;</td></tr>";
					}

					foreach ($pl as $id=>$entry) {
						if ( ($condPL) and (($id < $myMpd->current_track_id-1) or ($id > $myMpd->current_track_id + 10)) ) {
							continue; 
						}
												
						unset($text_class);
						$track = $id+1;
						$len=strlen($track);
												
						while ($len < $maxlen) {
							$track = "0".$track;
							$len++;
						}
							
						echo "<tr class=\"".flip_class()."\">";
												
						if ($id==$myMpd->current_track_id) {
							$tdstyle = "style='padding: 0px 2px 0px 2px; font-weight: bold;' class='npsong'";
		        			        $linkstyle = "class='npsong'";
						}
						else { 
							$tdstyle = $minpad;
							$linkstyle = ""; 
						}
							 
						$mpddir = conf('mpd_dir')."/";
						
						if (strtolower(conf('mpd_method')) == 'file') { 
							$sql = "SELECT genre.name, song.genre, song.id, song.album, song.artist FROM song, genre WHERE file = \"".$mpddir.$entry['file']."\" AND song.genre=genre.id";
							$db_results = @mysql_query($sql,dbh());
							$r = @mysql_fetch_object ($db_results);
							$entry['genre'] = $r->name;
						} 
						else {	
							list($tmp, $id, $tmp) = preg_split("/(song=|&)/", $entry['file']);
							$r = new Song($id);
							$entry['Title'] = $r->title;
							$entry['Artist'] = $r->get_artist_name();
							$entry['Album'] = $r->get_album_name();
							$entry['genre'] = $r->get_genre_name();
							$entry['Time'] = $r->time;
							$entry['Track'] = $r->track;
						}
							
						$totaltime += $entry['Time']; 
						$count=0; // Didn't move this because I wasn't sure what it was for...
			?>
							<td align="center" <?php echo $minpad  ?>> <input type="checkbox" name="song[]" value="<?php echo $entry['Pos']?>" id="song_<?php echo $entry['Pos']; ?>"></input></td>
							<td align="left"   <?php echo $tdstyle ?>> <?php echo $track.". ";?><a <?php echo $linkstyle ?> href="<?php echo $web_path; ?>/amp-mpd.php?action=skipto&amp;val=<?php echo $entry['Pos']; ?>" title=" <?php echo htmlspecialchars($entry['Title']); ?>"<?php echo $text_class; ?>><?php echo htmlspecialchars($entry['Title']); ?> </a></td>
							<td align="left"   <?php echo $tdstyle ?>> <a <?php echo $linkstyle ?> href="<?php echo $web_path; ?>/artists.php?action=show&amp;artist=<?php echo htmlspecialchars($r->artist); ?>" title="More from <?php echo htmlspecialchars($entry['Artist']);?>"<?php echo $text_class; ?>><?php echo htmlspecialchars($entry['Artist']);?> </a></td>
							<td align="left"   <?php echo $tdstyle ?>> <a <?php echo $linkstyle ?> href="<?php echo $web_path; ?>/albums.php?action=show&amp;album=<?php echo htmlspecialchars($r->album);    ?>" title="More on <?php   echo htmlspecialchars($entry['Album']); ?>"<?php echo $text_class; ?>><?php echo htmlspecialchars($entry['Album']); ?> </a></td>
							<td align="right"  <?php echo $tdstyle ?>> <?php echo $entry['Track']; ?></td>
							<td align="right"  <?php echo $tdstyle ?>> <?php echo sprintf ("%d:%02d",$entry['Time']/60,$entry['Time']%60) ?>&nbsp;</td>
							<td align="left" <?php echo $tdstyle ?>> &nbsp;<?php echo $entry['genre'] ?></td>
							<td <?php echo $tdstyle ?>>
								<a href="<?php echo $web_path;?>/flag.php?song=<?php echo $r->id;?>&amp;action=flag" title="Flag '<?php echo htmlspecialchars($entry['file']);?>' by <?php echo htmlspecialchars($entry['Artist']);?>"<?php echo $text_class;?>>f</a>&nbsp;
								<a href="<?php echo $web_path;?>/amp-mpd.php?action=movenext&amp;val=<?php echo $entry['Pos'];?>" title="Move '<?php echo htmlspecialchars($entry['Title']);?>' to play next  "<?php echo $text_class;?>>n</a>&nbsp;
								<a href="<?php echo $web_path;?>/amp-mpd.php?action=rem&amp;id=<?php echo $entry['Pos'];?>" title="Remove '<?php echo htmlspecialchars($entry['Title']);?>' from playlist "<?php echo $text_class;?>>x</a>
							</td>
						</tr>
			<?php
					}// foreach loop
					if (($condPL) && ($myMpd->current_track_id+10 <= $myMpd->playlistcount)) {
						echo "<tr class=\"".flip_class()."\"><td colspan=\"8\" align=\"center\" style=\"padding: 3px 0px 3px 0px\">... Condensed Playlist ...</td></tr>";
					}
				} //else

				$time = floor($totaltime/60) . ":" . sprintf("%02d", ($totaltime%60) );
				$num = count($pl);
			        if ($condPL) {
					echo "<tr class=\"".flip_class()."\"><td colspan=\"8\" align=\"center\" style=\"padding: 3px 0px 3px 0px\">... Condensed Playlist ...</td></tr>";
			        }
				else {
					echo "<tr><td>&nbsp;</td></tr>";
				}
			?>
			<tr class="table-header" valign="middle">
				<td>
					<input type="hidden" name="action" value="plact" /> <button name="submit" value="submit" type="submit" style = "font-family: <?php echo conf('font')?>; font-size: <?php echo conf('font_size')?>px" title="Take Action on the checked songs">Do</button>
				</td>
				<td valign="middle">
					<select name="todo"  style = "font-family: <?php echo conf('font')?>; font-size: <?php echo conf('font_size')?>px" size="1">
						<option>Add to Playlist</option>
						<option>Delete</option>
					    <!--<option>Move Next</option>  This isn't working yet -->
					</select>
				</td>
				<td valign="middle"><?php show_playlist_dropdown($GLOBALS['playlist_id']);  ?></td>
				<td valign="middle"><?php echo $num; ?> song(s)</td>
				<td>&nbsp;</td>
				<td valign="middle" align="right" <?php echo $minpad; ?> nowrap="nowrap"><?php echo $time; ?></td>
				<td>&nbsp;</td>
				<td>&nbsp;</td>
			</tr>
		</table>
	</td>
</tr>
<tr>
	<td align="center" valign="bottom" class="table-header">
		<i>
			[<a title="<?php echo _("Refresh the Playlist Window"); ?>" href="<?php echo $_SERVER['PHP_SELF']; ?>">refresh</a>]
			<?php if($myMpd->playlist_count > 0 ) {$un = (conf('condPL')) ? "un" : ""; ?>
					[<a title="<?php echo _("Click to shuffle (randomize) the playlist");?>" href="<?php echo conf('web_path'); ?>/amp-mpd.php?action=shuffle"><?php echo _("shuffle")?></a>]
					[<a title="<?php echo _("Click to the clear the playlist");?>" href="<?php echo conf('web_path'); ?>/amp-mpd.php?action=clear">clear</a>]
			<?php if($myMpd->playlist_count > 10) { ?>
					[<a title="<?php echo 'Click to '.$un.'condense playlist';?>" href="<?php echo conf('web_path'); ?>/amp-mpd.php?action=condPL"><?php echo $un?>condense</a>]
			<?php } ?>
					[<a title="<?php echo _("Click to the remove all except the Now Playing");?>" href="<?php echo conf('web_path'); ?>/amp-mpd.php?action=crop">crop</a>]
			<?php } ?>
		</i>
	</td>
</tr>
</table>
<br />
</form>
</td>

