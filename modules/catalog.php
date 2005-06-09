<?php
/*

 Contains all of the catalog (local & remote) functions.

 DEAD FILE (Old Crap)

*/

/*
 * get_catalogs()
 *
 * return an array of catalog objects
 *
 */

function get_catalogs () {
	global $dbh, $settings;

    $sql       = "SELECT * FROM catalog";
	$db_result = mysql_query($sql, $dbh);

	$catalogs  = array();
	while ( $catalog = mysql_fetch_object($db_result) ) {
		$catalogs[] = $catalog;
	}

	return ($catalogs);
} // get_catalogs()


/*
 * update_artist_info()
 *
 * this will update the song and album counters for the artist
 */

function update_artist_info($artist_id) {
  GLOBAL $dbh, $settings;

  // get the count of songs
  $query = "SELECT count(id) FROM song WHERE artist='$artist_id'";
  $db_result = mysql_query($query, $dbh);

  $r = mysql_fetch_row($db_result);
  $artist->songs = $r[0];

  // get the count of albums
  $query = "SELECT count(DISTINCT album) FROM song WHERE artist='$artist_id'";
  $db_result = mysql_query($query, $dbh);

  $r = mysql_fetch_row($db_result);
  $artist->albums = $r[0];

  // now update the artist table
  $query = "UPDATE artist SET songs='$artist->songs',albums='$artist->albums' WHERE id='$artist_id'";
  $db_result = mysql_query($query, $dbh);
} // update_artist_info()


/*
 * select_artist()
 *
 * given an artist name (string) it will return:
 *  false: if the artist name doesn't exist
 *  true : if the artist name does exist
 * in the database
 *
 */

function select_artist($artist) {
  GLOBAL $dbh, $settings;

  $artist = sql_escape($artist);

  $sql       = "SELECT id FROM artist WHERE name = '$artist'";
  $db_result = mysql_query( $sql, $dbh );
  $r         = mysql_fetch_row( $db_result );

  if ( $r[0] ) {
    return ($r[0]);
  }
  else {
    return 0;
  }
} // select_artist()


/*
 * insert_artist()
 *
 * given an artist name (string) it will insert an entry
 * into the database, defaulting the catalog to 0
 *
 */

/*
function insert_artist($artist, $catalog = 0) {
  GLOBAL $dbh, $settings;

  $artist = sql_escape($artist);

  $sql = "INSERT INTO artist (name,catalog) VALUES ('$artist', $catalog)";
  $db_result = mysql_query($sql, $dbh);

  return (mysql_insert_id($dbh));
} // insert_artist()
*/

/*
 * update_artist_name()
 *
 * let's change the album name
 *
 */

function update_artist_name ($artist, $new_name) {
        global $dbh, $settings;
  
        $query = "UPDATE artist SET name='$new_name' WHERE id='$artist'";
        $db_result = mysql_query($query, $dbh);
} // update_artist_name()


/*
 * delete_artist()
 *
 * given an artist id (int) this will delete the associated
 * entry from the database
 *
 */

function delete_artist($artist) {
  GLOBAL $dbh, $settings;

  $sql       = "DELETE FROM artist WHERE id = $artist";
  $db_result = mysql_query($sql, $dbh);
} // delete_artist()


/*
 * select_album()
 *
 * given an album name and artist id, this will return:
 *  false: if the album name and artist id don't match
 *  id   : of the album name and artist id match 
 */

function select_album($album, $artist) {
  GLOBAL $dbh, $settings;

  $album = sql_escape($album);

  $sql       = "SELECT id FROM album 
                WHERE name = '$album' AND artist = $artist";
  $db_result = mysql_query($sql, $dbh);

  $r = mysql_fetch_row($db_result);

  if ( $r[0] ) {
    return ($r[0]);
  }
  else {
    return 0;
  }
} // select_album()


/*
 * update_album_name()
 *
 * let's change the album name
 *
 */

function update_album_name ($album, $new_name) {
	global $dbh, $settings;

	$sql       = "UPDATE album SET name='$new_name' WHERE id='$album'";
	$db_result = mysql_query($sql, $dbh);
} // update_album_name()


/*
 * update_local_mp3($name, $type, $songs)
 *
 * This will update all of the $songs with a new name of type $type.  Used
 *   mostly for updating artist/album names for your _local_ mp3s.  This
 *   will write out new ID3 tags.
 */

function update_local_mp3($name, $type, $songs) {
	// THIS IS DEAD!!!
	//FIXME: I'm dead Jim!
} // update_local_mp3



/*
 * get_check_array()
 *
 * returns a single dimension array of the md5 hashes
 * for all songs in local catalogs
 *
 */

function get_check_array ( ) {
	global $settings, $dbh;
    
	$check_array = array();

	$sql       = "SELECT md5 FROM song";
	$db_result = mysql_query($sql, $dbh );

	while ( $md5 = mysql_fetch_object( $db_result ) )
	{
		$check_array[] = $md5->md5;
	}

	return $check_array;
} // get_check_array()

?>
