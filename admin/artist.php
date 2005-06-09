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
	@header Admin Artist page
 Update the artist information for the site. 

*/

require('../modules/init.php');


if (!$user->has_access(100)) { 
	header("Location:". conf('web_path') . "/index.php?access=denied");
	exit();
}

$dbh = dbh();

if ( $action == 'Change Name' ) {
	if ( $settings[demo_mode] == 'false' && $username != $settings[demo_user] ) {
	$old_artist_name = get_artist_name($artist);
	update_artist_name($artist, $new_name);

	if ( $update_tag ) {
		// get songs associated with this
		$song_ids = get_song_ids_from_artist($artist);

		// run update_local_mp3
		$total_updated = update_local_mp3($new_name, 'artist', $song_ids);
		$update_text = "Updated $old_artist_name to $new_name and $total_updated local files.";
	}
	else {
		$update_text = "Updated $old_artist_name to $new_name.";
	}

	// set the action to view so everybody can see the changes
	$action = 'View';
	}
}

show_template('header');

show_menu_items("..");
show_admin_menu('Catalog');

?>

<p>Use this form to change the name(s) of artists in the database.  In order to update your
local MP3's your Apache user must have write-permission to your MP3's.</p>

<form name="artist" method="post" action="artist.php">
<table>
  <tr>
    <td>Select Artist:</td>
    <td> <?php show_artist_pulldown($artist) ?> </td>
    <td> <input type=submit name=action value=View> </td>
  </tr>
</table>
</form>

<hr>

<?php 

// if artist exists then show some info 
if ( $artist and $action == 'View' ) {
  $sql = "SELECT name FROM artist WHERE id='$artist'";
  $db_result = mysql_query($sql, $dbh);

  $r = mysql_fetch_row($db_result);
  $artist_name = $r[0];

?>

<p style="color: red;"><?= $update_text ?></p>

<form name="artist_change" method=post action="artist.php">
	<table>
		<tr>
			<td>Artist Name:</td> <td><input type=text name="new_name" value="<?= $artist_name ?>" size="50"></td>
			<td> &nbsp; </td> <td><input type=submit name=action value="Change Name"></td>
		</tr>
		<tr>
			<td> &nbsp; </td> <td><input type="checkbox" name="update_tag"> 
				Update MP3 tag <b>Note: this will only modify your local MP3's</b>
			</td>
		</tr>
	</table>
	<input type="hidden" name="artist" value="<?= $artist ?>">
</form>

<?php

  show_albums_for_artist($artist);
}

?>

</body>
</html>
