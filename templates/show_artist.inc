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

//FIXME: I don't like having to re-create this friggin object.. :(
global $user;
$artist_id = $artist->id;
?>
<br />
<table class="text-box">
<tr>
	<td>
	<span class="header1"><?php print _("Albums by") . " " . $artist->full_name; ?></span>
<ul>
        <li><a href="<?php print $web_path; ?>/artists.php?action=show_all_songs&amp;artist=<?php print $artist_id; ?>"><?php print _("Show All Songs By") . " " . $artist->full_name; ?></a></li>
        <li><a href="<?php print $web_path; ?>/song.php?action=m3u&amp;artist=<?php print $artist_id; ?>"><?php print _("Play All Songs By") . " " . $artist->full_name; ?></a></li>
        <li><a href="<?php print $web_path; ?>/song.php?action=m3u&amp;artist_random=<?php print $artist_id; ?>"><?php print _("Play Random Songs By") . " " . $artist->full_name; ?></a></li>
	<?php  if ($user->has_access('100')) { ?> 
		<li><a href="<?php print $web_path; ?>/artists.php?action=update_from_tags&amp;artist=<?php print $artist_id; ?>"><?php print _("Update from tags"); ?></a></li>
	<?php } ?>
</ul>
	</td>
</tr>
</table>
<!-- *** Multi-Album Art Display Thx MrBlahh Updated by clader *** -->
<br />
<form name="songs" method="post" enctype="multipart/form-data">

<table class="border" cellspacing="0" cellpadding="0" border="0">
        <tr class="table-header">
        <th align="center">
	&nbsp;&nbsp;<a href="#" onclick="check_songs(); return false;"><?php echo _("Select"); ?></a>
	</th>
          <th><?php echo _("Cover"); ?></th>
          <th><?php echo _("Album Name"); ?></th>
	  <th><?php echo _("Album Year"); ?></th>
          <th><?php echo  _("Total Tracks"); ?></th>
          <th><?php echo _("Action"); ?></th>
        </tr>

<?php
           foreach ($albums as $album) {  
		$id = $album->id;
                $album_name = $album->name;
                $count = $album->songs;
?>

        <tr class="<?php echo flip_class(); ?>">
        <td align="center">
	  	<input name="song[]" value="<?php print $id; ?>" type="checkbox" />
	</td>
          <td height="87">
                <a href="<?php print $web_path; ?>/albums.php?album=<?php echo $id; ?>&amp;artist=<?php echo $artist->id; ?>">
                <img border="0" src="<?php print $web_path; ?>/albumart.php?id=<?php print $id; ?>&amp;fast=1" alt="<?php echo $album_name; ?>" title="<?php print $album_name; ?>" height="75" width="75" />
                </a>
          </td>
          <td><a href="<?php print $web_path; ?>/albums.php?album=<?php print $id; ?>&amp;artist=<?php echo $artist->id; ?>"><?php print $album_name; ?></a></td>
	  <td><?php print $album->year; ?></td>
          <td><?php print $count; ?></td>
          <td>
		<a href="<?php print $web_path; ?>/song.php?action=m3u&amp;album=<?php print $id; ?>"><?php print _("Play"); ?></a>
               	<?php if( batch_ok() ) { ?>
			| <a href="<?php echo  conf('web_path'); ; ?>/batch.php?action=alb&amp;id=<?php echo $album->id; ?>"><?php echo _("Download"); ?></a>
               	<?php } ?> 
       </td>
                                                              
        
       </td>      
       </tr>
<?php  } ?>
</table>
      <br /><br />
     
<?php show_play_selected(); ?>
<input type="hidden" name="type" value="album" />
<input type="hidden" name="artist_id" value="<?php print $artist_id; ?>" />
</form>

