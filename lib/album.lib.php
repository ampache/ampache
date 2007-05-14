<?php
/*
 This library handles album related functions.... wooo!
*/

/*!
	@function get_albums
	@discussion pass a sql statement, and it gets full album info and returns
		an array of the goods.. can be set to format them as well
*/
function get_albums($sql, $action=0) {

	$db_results = mysql_query($sql, dbh());
	while ($r = mysql_fetch_array($db_results)) {
		$album = new Album($r[0]);
		$album->format_album();
		$albums[] = $album;
	}

	return $albums;


} // get_albums

/**
 * get_image_from_source
 * This gets an image for the album art from a source as 
 * defined in the passed array. Because we don't know where
 * its comming from we are a passed an array that can look like
 * ['url']	= URL *** OPTIONAL ***
 * ['file']	= FILENAME *** OPTIONAL ***
 * ['raw']	= Actual Image data, already captured
 */
function get_image_from_source($data) { 

	// Already have the data, this often comes from id3tags
	if (isset($data['raw'])) { 
		return $data['raw'];
	}

	// Check to see if it's a URL
	if (isset($data['url'])) { 
		$snoopy = new Snoopy(); 
		$snoopy->fetch($data['url']); 
		return $snoopy->results; 
	} 

	// Check to see if it's a FILE
	if (isset($data['file'])) { 
		$handle = fopen($data['file'],'rb'); 
		$image_data = fread($handle,filesize($data['file'])); 		
		fclose($handle); 
		return $image_data; 
	} 

	return false; 

} // get_image_from_source

/**
 * get_random_albums
 * This returns a random number of albums from the catalogs
 * this is used by the index to return some 'potential' albums to play
 */
function get_random_albums($count='') { 

	if (!$count) { $count = 5; } 

	$count = Dba::escape($count); 

	// We avoid a table scan by using the id index and then using a rand to pick a row #
	$sql = "SELECT `id` FROM `album`";
	$db_results = Dba::query($sql); 

	while ($r = Dba::fetch_assoc($db_results)) { 
		$albums[] = $r['id'];
	} 

	$total = count($albums); 

	if ($total == '0') { return array(); } 

	for ($i=0; $i <= $count; $i++) { 
		$record = rand(0,$total); 
		if (isset($results[$record]) || !$albums[$record]) { $i--; } 
		else { 
			$results[$record] = $albums[$record]; 
		} 
	} // end for 
	
	return $results; 

} // get_random_albums

?>
