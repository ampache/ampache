<?php

/*

 Copyright (c) 2004 Ampache.org
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
	@header Admin Album Mojo
	Update the album information for the site. 

*/

require('../modules/init.php');


if (!$user->has_access(100)) { 
	header("Location:" . conf('web_path') . "/index.php?access=denied");
	exit();
}


if ( $action == 'Change Name' ) {

		update_album_name($album, $new_name);

		if ( $update_tag ) {
			// get songs associated with this
			$songs = get_songs_from_album($album);

			// run update_local_mp3
			$total_updated = update_local_mp3($new_name, 'album', $songs);
			$update_text = "Updated the database and $total_updated local files.";
		}

		// set the action to view so everybody can see the changes
		$action = 'View';
}

show_template('header');

show_menu_items('Admin');
show_admin_menu('Catalog');

?>

<p>Use this form to change the name(s) of albums in the database.  In order to update your
local MP3's your Apache user must have write-permission to your MP3's.</p>

<form name="album" method="post" action="album.php">
<table>
  <tr>
    <td>Select Album:</td>
    <td> <?php show_album_pulldown($album) ?> </td>
    <td> <input type=submit name=action value=View> </td>
  </tr>
</table>
</form>

<hr>

<?php 

// if album exists then show some info 
if ( $album and $action == 'View' ) {
  $album_name = get_album_name($album);

?>

<p style="color: red;"><?php echo $update_text; ?></p>

<form name="album_change" method=post action="album.php">
	<table>
		<tr>
			<td>Album Name:</td> 
			<td><input type=text name="new_name" value="<?php echo $album_name; ?>" size="50"></td>
			<td> &nbsp; </td> 
			<td><input type=submit name=action value="Change Name"></td>
		<tr>
                        <td> &nbsp; </td> <td><input type="checkbox" name="update_tag"> 
                               Update MP3 tag <b>Note: this will only modify your local MP3's</b>
                        </td>
		</tr>
	</table>
	<input type=hidden name=album value="<?php echo $album; ?>">
</form>

<?php

  $song_ids = get_song_ids_from_album($album);
  show_songs($song_ids, 0);
}

show_footer();
?>

</body>
</html>
