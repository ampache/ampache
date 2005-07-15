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
Instructions on incorporating this code:
1) You need to add a row with a name of 'condPL' into the table preferences. Initial value is 1 (condensed playlist by default)
or 0 (not condensed by default).
2) Insert the following function at the end of /mpd.php and change show_mpd_control() above it to "require" (instead of require_once):

TTD:
- It would be nice if flagged songs showed up in a flagged color (e.g. red).
- Accept one or more parameters by which  the caller specify which columns are displayed.
- What is the best way to deal with the play pointer when stopped.  MPD doesn't report a "position" but stop restarts at the song
  that was playing before the stop.

*/

/*  The following mpd init code is from amp-mpd.php and should probably go into a mpdinit file that gets executed by initmpd
since other pages or items on index.php that come before this (like an MPD control in the header!) might need myMpd.
*/


if (!class_exists('mpd')) { require_once(conf('prefix')."/modules/mpd/mpd.class.php"); }
if (!is_object($myMpd)) { $myMpd = new mpd(conf('mpd_host'),conf('mpd_port')); }
if (!$myMpd->connected)
   {
     echo "<font class=\"error\">" . _("Error Connecting") . ": " . $myMpd->errStr . "</font><br />\n";
     log_event ($_SESSION['userdata']['username'],' connection_failed ',"Error: unable to connect to MPD, ".$myMpd->errStr);
     return; 
   }

$nopad  = "style='padding: 0px 0px 0px 0px'";
$minpad = "style='padding: 0px 2px 0px 2px'";

?>
<td colspan="2" valign="top">

<form action="<?php echo conf('web_path')."/amp-mpd.php"; ?>" method="post" enctype="multipart/form-data">

<table border="0" cellpadding="0" cellspacing="1" width="100%" class="tabledata">
<tr><td align="center" class="table-header"><b>MPD Server Playlist</b><br />
     <i>[<a title="<?php echo _("Refresh the Playlist Window"); ?>" href="<?php echo conf('web_path'); ?>">refresh</a>]
<?php   if ( $myMpd->playlist_count > 0 ) {
	      $un = (conf('condPL')) ? "un" : ""; ?>
              [<a title="<?php echo _("Click to shuffle (randomize) the playlist");?>" href="<?php echo conf('web_path'); ?>/amp-mpd.php?action=shuffle"><?php echo _("shuffle")?></a>]
              [<a title="<?php echo _("Click to the clear the playlist");?>" href="<?php echo conf('web_path'); ?>/amp-mpd.php?action=clear">clear</a>]
              [<a title="<?php echo 'Click to '.$un.'condense playlist';?>" href="<?php echo conf('web_path'); ?>/amp-mpd.php?action=condPL"><?php echo $un?>condense</a>]
              [<a title="<?php echo _("Click to the remove all except the Now Playing");?>" href="<?php echo conf('web_path'); ?>/amp-mpd.php?action=crop">crop</a>]
<?php   } ?> </i></td></tr>
<tr><td <?php echo $nopad ?>>
<table cellspacing="0" cellpadding="0" border="0" width="100%"><tr class="table-header">
            <th <?php echo $minpad ?>><a href="#" onclick="check_songs(); return false;">Flip</a></th>
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
        if ($condPL)
           {
           echo "<tr class=\"".flip_class()."\"><td colspan=\"8\" align=\"center\" style=\"padding: 3px 0px 3px 0px\">... Condensed Playlist ...</td></tr>";
           }
	else
	   {
	   echo "<tr><td>&nbsp;</td></tr>";
	   }
        foreach ($pl as $id=>$entry) {

            if ( ($condPL) and (($id < $myMpd->current_track_id-1) or ($id > $myMpd->current_track_id + 10)) )
                { continue; }

	    unset($text_class);

            $track = $id+1;
            $len=strlen($track);
            while ($len < $maxlen)
              {
              $track = "0".$track;
              $len++;
              }

            // Still needed crap
            $totalsize += $song->size;
            $totaltime += $entry['Time']; 
    ?>

            <tr class="<?php echo flip_class(); ?>">
	    <?php if ($id==$myMpd->current_track_id) 
               {$tdstyle = "style='padding: 0px 2px 0px 2px; font-weight: bold'";}
               else {$tdstyle = $minpad;} ?>
<?php
$mpddir = conf('mpd_dir')."/";
if ($entry['Title']) {
	$sql = "SELECT genre.name, song.genre, song.id, song.album, song.artist FROM song, genre WHERE file = \"".$mpddir.$entry['file']."\" AND song.genre=genre.id";
	$db_results = @mysql_query($sql,dbh());
	$r = @mysql_fetch_object ($db_results);
} else {
	list($tmp, $id, $tmp) = preg_split("/(song=|&)/", $entry['file']);
	$r = new Song($id);
	$entry['Title'] = $r->title;
	$entry['Artist'] = $r->get_artist_name();
	$entry['Album'] = $r->get_album_name();
}
$count=0; // Didn't move this because I wasn't sure what it was for...
?>
          <td align="center" <?php echo $minpad  ?>> <input type="checkbox" name="song[<?php echo $entry['Pos']?>]" value="1" id="song_<?php echo $entry['Pos']; ?>"></input></td>
          <td align="left"   <?php echo $tdstyle ?>> <?php echo $track.". ";?><a href="<?php echo $web_path; ?>/amp-mpd.php?action=skipto&amp;val=<?php echo $entry['Pos']; ?>" title=" <?php echo htmlspecialchars($entry['Title']); ?>"<?php echo $text_class; ?>><?php echo htmlspecialchars($entry['Title']); ?> </a></td>
          <td align="left"   <?php echo $tdstyle ?>> <a href="<?php echo $web_path; ?>/artists.php?action=show&amp;artist=<?php echo htmlspecialchars($r->artist); ?>" title="More from <?php echo htmlspecialchars($entry['Artist']);?>"<?php echo $text_class; ?>><?php echo htmlspecialchars($entry['Artist']);?> </a></td>
          <td align="left"   <?php echo $tdstyle ?>> <a href="<?php echo $web_path; ?>/albums.php?action=show&amp;album=<?php echo htmlspecialchars($r->album);    ?>" title="More on <?php   echo htmlspecialchars($entry['Album']); ?>"<?php echo $text_class; ?>><?php echo htmlspecialchars($entry['Album']); ?> </a></td>
          <td align="right"  <?php echo $tdstyle ?>> <?php echo $entry['Track']; ?></td>
          <td align="right"  <?php echo $tdstyle ?>> <?php echo sprintf ("%d:%02d",$entry['Time']/60,$entry['Time']%60) ?></td>
	  <td align="center" <?php echo $tdstyle ?>> <?php echo $r->name ?></td>

          <td <?php echo $tdstyle ?>>
		<a href="<?php echo $web_path;?>/flag.php?song=<?php echo $r->id;?>&amp;action=flag" title="Flag '<?php echo htmlspecialchars($entry['file']);?>' by <?php echo htmlspecialchars($entry['Artist']);?>"<?php echo $text_class;?>>f</a>&nbsp;
		<a href="<?php echo $web_path;?>/amp-mpd.php?action=movenext&amp;val=<?php echo $entry['Pos'];?>" title="Move '<?php echo htmlspecialchars($entry['Title']);?>' to play next  "<?php echo $text_class;?>>n</a>&nbsp;
		<a href="<?php echo $web_path;?>/amp-mpd.php?action=rem&amp;id=<?php echo $entry['Pos'];?>" title="Remove '<?php echo htmlspecialchars($entry['Title']);?>' from playlist "<?php echo $text_class;?>>x</a>
          </td>
       </tr>
<?php
         }// foreach loop
        if (($condPL) && ($myMpd->current_track_id+10 <= $myMpd->playlistcount))
           {
           echo "<tr class=\"".flip_class()."\"><td colspan=\"8\" align=\"center\" style=\"padding: 3px 0px 3px 0px\">... Condensed Playlist ...</td></tr>";
           }
     } //else

    $time = floor($totaltime/60) . ":" . sprintf("%02d", ($totaltime%60) );
    $num = count($pl);
?>
    <tr><td>&nbsp;</td></tr>
    <tr class="table-header" valign="middle">
      <td><input type="hidden" name="action" value="plact" /> <button name="submit" value="submit" type="submit" style = "font-family: <?php echo conf('font')?>; font-size: <?php echo conf('font_size')?>px" title="Take Action on the checked songs">Do</button></td>
      <td valign="middle">
        <select name="todo"  style = "font-family: <?php echo conf('font')?>; font-size: <?php echo conf('font_size')?>px" size="1">
		<option>Delete</option>
		<!-- <option>move Next   This isn't working yet -->
		<option>Crop</option>
	</select>
     </td>
      <td valign="middle"><?php echo _("Total"); ?>:</td>
      <td valign="middle"><?php echo $num; ?> song(s)</td>
      <td></td>
      <td valign="middle" align="right" <?php echo $minpad; ?> nowrap="nowrap"><?php echo $time; ?></td>
      <td>&nbsp;</td>
      <td>&nbsp;</td>
    </tr>
    </table></td></tr>
</table>
<br />
</form>
</td>

