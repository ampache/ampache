<?php
/*!
	@header Contains the functions for handling duplicate songs 
*/


/*!
	@function get_duplicate_songs
	@discussion
*/
function get_duplicate_songs($search_type) {
 $sql = "SELECT song.id as song,artist.name,album.name,title,count(title) as ctitle".
  	" FROM song,artist,album ".
	" WHERE song.artist=artist.id AND song.album=album.id AND song.title<>'' ".
	" GROUP BY title";
	if ($search_type=="artist_title"||$search_type=="artist_album_title")
		$sql = $sql.",artist";
	if ($search_type=="artist_album_title")
		$sql = $sql.",album";
	$sql = $sql." HAVING count(title) > 1";
	$sql = $sql." ORDER BY ctitle";

	//echo $sql."<BR>";

    $result = mysql_query($sql, dbh());

    $arr = array();

    while ($flag = mysql_fetch_array($result)) {
        $arr[] = $flag;
    } // end while
    return $arr;
} // get_duplicate_songs

/*!
	@function get_duplicate_info
	@discussion
*/
function get_duplicate_info($song,$search_type) {
 $artist = get_artist_name($song->artist);
 $sql = "SELECT song.id as songid,song.title as song,file,bitrate,size,time,album.name AS album,album.id as albumid, artist.name AS artist,artist.id as artistid".
	" FROM song,artist,album ".
	" WHERE song.artist=artist.id AND song.album=album.id ".
	"  AND song.title= '".str_replace("'","''",$song->title)."'";

	if ($search_type=="artist_title"||$search_type=="artist_album_title")
		$sql = $sql."  AND artist.id = '".$song->artist."'";
	if ($search_type=="artist_album_title")
		$sql = $sql."  AND album.id = '".$song->album."'";

    $result = mysql_query($sql, dbh());

    $arr = array();

    while ($flag = mysql_fetch_array($result)) {
        $arr[] = $flag;
    } // end while
    return $arr;

} // get_duplicate_info

/*!
	@function show_duplicate_songs
	@discussion
*/
function show_duplicate_songs($flags,$search_type) {
	require_once(conf('prefix').'/templates/list_duplicates.inc');
} // show_duplicate_songs

/*!
	@function show_duplicate_searchbox
	@discussion
*/
function show_duplicate_searchbox($search_type) {
?>
<br />
<form name="songs" action="<?php echo conf('web_path'); ?>/admin/duplicates.php" method="post" enctype="multipart/form-data" >
<table class="border" cellspacing="0" cellpadding="3" border="0" width="450px">
	<tr class="table-header">
		<td colspan="2"><b><?php echo _("Find Duplicates"); ?></b></td>
	</tr>
	<tr class="even">
		<td><?php echo _("Search Type"); ?>:</td>
		<td>
			<?

			if ($search_type=="title")
				$checked = "checked=\"checked\"";
			else
				$checked = "";
			echo "<input type=\"radio\" name=\"search_type\" value=\"title\" ".$checked." />" . _("Title") . "<br />";

			if ($search_type=="artist_title")
						$checked = "checked=\"checked\"";
			else
				$checked = "";
			echo "<input type=\"radio\" name=\"search_type\" value=\"artist_title\" ".$checked." />" . _("Artist and Title") . "<br />";
			if ($search_type=="artist_album_title"OR $search_type=="")
						$checked = "checked=\"checked\"";
			else
				$checked = "";
			echo "<input type=\"radio\" name=\"search_type\" value=\"artist_album_title\"".$checked." />" . _("Artist, Album and Title") . "<br />";
			?>
		</td>
	</tr>
	<tr class="odd">
		<td></td>
		<td>
			<input type="hidden" name="action" value="search" />
			<input type="submit" value="<?php echo _("Search"); ?>" />
		</td>
	</tr>
</table>
</form>
<br />
<?
} // show_duplicate_searchbox
?>
