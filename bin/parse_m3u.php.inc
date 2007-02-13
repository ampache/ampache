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
$filename = "/data/music/Live/L/Life'll Kill Ya/Warren Zevon.m3u";


define('NO_SESSION','1');
require ("../lib/init.php");

$handle = fopen($filename,'r');

$data = fread($handle,filesize($filename));

$results = explode("\n",$data);

foreach ($results as $value) { 
	$value = trim($value);
	if (preg_match("/\.[A-Za-z0-9]{3}$/",$value)) {
		echo "$value \n";
		$sql = "SELECT id FROM song WHERE file LIKE '%" . sql_escape($value) . "'";
		$db_results = mysql_query($sql, dbh());
		$foo = mysql_result($db_results,'id');
		echo "\t Results: " . $foo . "\n";
		print_r($foo);
	}
	
} // end foreach

?>
