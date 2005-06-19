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


/*!
	@header
  A template file for listing duplicate songs

*/
?>

<?php show_duplicate_searchbox($search_type) ?>
<?php if ( $flags ) { ?>

<form method="post" enctype="multipart/form-data" action="<?php echo  conf('web_path') . "/admin/song.php?action=disable"; ?>">
<p style="font-size: 10pt; font-weight: bold;">Duplicate Songs</p>
<table class="tabledata" cellspacing="0" cellpadding="0" >
	<tr class="table-header">
		<td>Disable</td>
		<td>Song</td>
		<td>Artist</td>
		<td>Album</td>
		<td>Length</td>
		<td>Bitrate</td>
		<td>Size</td>
		<td>Filename</td>
	</tr>
<?php 
	$class="odd";
	foreach ($flags as $flag) {
		$song = new Song($flag['song']);
		$song->format_song();
		$class = (++$i%2)?'odd':'even';
		$alt_title = $song->title;
	        $formated_title = $song->f_title;

		$artist = $song->f_artist;
	        $alt_artist = $song->f_full_artist;

		$dinfolist = get_duplicate_info($song,$search_type);
	foreach ($dinfolist as $dinfo)
	{
		echo "<tr class=\"".$class."\">".
		"<td><input type=\"checkbox\" name=\"song_ids[]\" value=\"" . $dinfo['songid'] . "\" />".
		"<td><a href=\"".conf('web_path')."/song.php?action=m3u&amp;song=$song->id\">$formated_title</td>".
		"<td><a href=\"".conf('web_path')."/artists.php?action=show&amp;artist=".$dinfo['artistid']."\" title=\"".$dinfo['artist']."\">".$dinfo['artist']."</a> </td>".
		"<td><a href=\"".conf('web_path')."/albums.php?action=show&amp;album=".$dinfo['albumid']."\" title=\"".$dinfo['album']."\">".$dinfo['album']."</a> </td>".
		"<td>".floor($dinfo['time']/60).":".sprintf("%02d", ($dinfo['time']%60) )."</td>".
		"<td>".intval($dinfo['bitrate']/1000)."</td>".
		"<td>".sprintf("%.2f", ($dinfo['size']/1000000))."Mb</td>".
		"<td>".$dinfo['file']."</td>";
		echo "</tr>\n";

	}


	}
?>
<tr>
<td colspan="8" class="<?php echo $class; ?>"><input height="15px" type="submit" value="Disable Songs" /></td>
</tr>
</table>
<?php  } else { ?>

<p> You don't have any duplicate songs. </p>
</form>
<?php  } ?>

