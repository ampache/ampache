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
preg_match("/^.*\/(.*?)$/",$song->file, $short);
$filename = scrub_out($short[1]);
$target = conf('web_path').'/admin/flags.php';
?>

<form name="update_song" method="post" action="<?php echo $target; ?>">

<table class="tabledata" cellspacing="0" cellpadding="0" border="0">
  <tr>
    <td>File:</td>
    <td colspan="2"><?php echo $filename; ?></td>
  </tr>

  <tr>
    <td>Title:</td>
    <td> <input type="text" name="title" size="60" value="<?php echo $song->title; ?>"></input></td>
  </tr>

  <tr>
    <td>Artist:</td>
    <td> <?php show_artist_pulldown($song->artist); ?> </td>
    <td>or <input type="text" name="new_artist" size="30" value=""></input></td>
  </tr>

  <tr>
    <td>Album:</td>
    <td> <?php show_album_pulldown($song->album); ?> </td>
    <td>or <input type="text" name="new_album" size="30" value=""></input></td>
  </tr>

  <tr>
    <td>Track:</td>
    <td> <input type="text" size="4" maxlength="4" name="track" value="<?php echo $song->track;?>"></input> </td>
  </tr>

  <tr>
    <td>Genre:</td>
    <td> <?php show_genre_pulldown('genre',$song-genre); ?> </td>
  </tr>

  <tr>
    <td> <input type="text" size="4" maxlength="4" name="year" value="<?php echo $song->year;?>"></input> </td>
  </tr>

  <tr>
    <td>&nbsp;</td>
    <td> <input type="checkbox" name="update_id3" value="yes" checked="checked">&nbsp;Update id3 tags</input> </td>
    <td>&nbsp;</td>
  </tr>

  <tr>
    <td> &nbsp; </td>
    <td> <input type="hidden" name="song" value="<?php echo $song->id;?>"></input>
         <input type="hidden" name="flag" value="<?php echo $flagid;?>"></input>
         <input type="hidden" name="current_artist_id" value="<?php echo $song->artist;?>"></input>

<?php if(count($_SESSION['edit_queue'])){ ?>
         <input type="submit" name="action" value="Next"></input>
         <input type="submit" name="action" value="Skip"></input>
         <input type="submit" name="action" value="Clear Edit List"></input></td>
<?php } else { ?>
         <input type="submit" name="action" value="Done"></input></td>
<?php } ?>
  </tr>
</table>
</form>

