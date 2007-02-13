<?php
/*

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


/* Name of the filename who's tags you want to read */
$search['album_name'] 	= "Ariels";
$search['artist_name'] 	= "Bent";



define('NO_SESSION','1');
require ("../lib/init.php");
echo "<pre>\n";
$amazon = new AmazonSearch(conf('amazon_developer_key'));
// Prevent the script from timing out
set_time_limit(0);
$search_term = $search['artist_name'] . " " . $search['album_name'];
$amazon->search(array('artist' => $search['artist_name'], 'album' => $search['album_name'], 'keywords' => $serch_term));
$amazon->lookup($amazon->results);

echo "Search Term: $search_term\n";
echo "Artist: " . $search['artist_name'] . " AND Album: " . $search['album_name'] . "\n";
print_r($amazon->results);
echo "\n</pre>";
?>

