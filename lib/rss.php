<?php
/*

 Copyright 2001 - 2006 Ampache.org
 All Rights Reserved

 This program is free software; you can redistribute it and/or
 modify it under the terms of the GNU General Public License v2
 as published by the Free Software Foundation.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with this program; if not, write to the Free Software
 Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.

*/
/*!
        @function show_now_playingRSS
        @discussion creates a RSS fead for the now
                playing information
*/


function show_RSS ($type = 'artist',$username = 0) {
	header ("Content-Type: application/xml");
        $web_path = Config::get('web_path');
        $rss_main_title = "Ampache :: Pour l'Amour de la Musique - RSS";

        $rss_latestartist_title = "Ampache Latest Artists";
        $rss_latestalbum_title = "Ampache Latest Albums";
        $rss_popularartist_title = "Ampache Most Popular Artists";
        $rss_popularalbum_title = "Ampache Most Popular Albums";
	$rss_popularsong_title = "Ampache Most Popular Songs";
	$rss_recentlyplayed_title = "Ampache Recently Played";

        $today = date("d-m-Y");

        echo "<rss version=\"2.0\">\n";


switch ($type) {
    case "popularalbum":

        $date   = time() - (86400*7);

        /* Select Top objects counting by # of rows */
        $sql = "SELECT object_id,COUNT(id) AS `count` FROM object_count" .
		" WHERE object_type='album' AND date >= '$date'" .
		" GROUP BY object_id ORDER BY `count` DESC LIMIT 10";

	$db_result = Dba::query($sql);

        echo " <channel>\n  <title>$rss_popularalbum_title</title>\n";
        echo "  <link>$web_path</link>\n  <description>$rss_main_description</description>\n";
        echo "  <copyright>$rss_main_copyright</copyright>\n";
        echo "  <pubDate>$today</pubDate>\n";

        while ( $r = @mysql_fetch_object($db_result) ) {
		echo "<item>\n";
                $album   = new Album($r->object_id);
 		echo " <title><![CDATA[$album->name ($r->count)]]></title>\n";
 		echo " <link>$web_path/albums.php?action=show&amp;album=$r->object_id</link>\n";
 		echo " <description><![CDATA[$album->name - $album->artist ($r->count)]]></description>\n";
 		echo "</item>\n";
        }
        echo "</channel>\n</rss>";
	break;
	
    case "popularartist";

        $date   = time() - (86400*7);

        /* Select Top objects counting by # of rows */
        $sql = "SELECT object_id,COUNT(id) AS `count` FROM object_count" .
		" WHERE object_type='artist' AND date >= '$date'" .
		" GROUP BY object_id ORDER BY `count` DESC LIMIT 10";

        $db_result = mysql_query($sql, $dbh);

        echo " <channel>\n  <title>$rss_popularartist_title</title>\n";
        echo "  <link>$web_path</link>\n  <description>$rss_main_description</description>\n";
        echo "  <copyright>$rss_main_copyright</copyright>\n";
        echo "  <pubDate>$today</pubDate>\n";

        while ( $r = @mysql_fetch_object($db_result) ) {
		echo "<item>\n";
                $artist   = new Artist($r->object_id);
 		echo " <title><![CDATA[$artist->name ($r->count)]]></title>\n";
 		echo " <link>$web_path/artists.php?action=show&amp;artist=$r->object_id</link>\n";
 		echo " <description><![CDATA[$artist->name - $artist->albums ($r->count)]]></description>\n";
 		echo "</item>\n";
        }
        echo "</channel>\n</rss>";
	break;

    case "popularsong";

        $date   = time() - (86400*7);

        /* Select Top objects counting by # of rows */
        $sql = "SELECT object_id,COUNT(id) AS `count` FROM object_count" .
		" WHERE object_type='song' AND date >= '$date'" .
		" GROUP BY object_id ORDER BY `count` DESC LIMIT 10";

        $db_result = mysql_query($sql, $dbh);

        echo " <channel>\n  <title>$rss_popularsong_title</title>\n";
        echo "  <link>$web_path</link>\n  <description>$rss_main_description</description>\n";
        echo "  <copyright>$rss_main_copyright</copyright>\n";
        echo "  <pubDate>$today</pubDate>\n";

        while ( $r = @mysql_fetch_object($db_result) ) {
		echo "<item>\n";
                $song   = new Song($r->object_id);
		$artist = $song->get_artist_name();
 		echo " <title><![CDATA[$artist - $song->title ($r->count)]]></title>\n";
 		echo " <link>$web_path/stream.php?action=single_song&amp;song_id=$r->object_id</link>\n";
 		echo " <description><![CDATA[$artist - $song->title ($r->count)]]></description>\n";
 		echo "</item>\n";
        }
        echo "</channel>\n</rss>";
	break;
	
    case "latestartist":

        $sql = "SELECT DISTINCT artist,album FROM song ORDER BY addition_time DESC LIMIT 10";
        $db_result = mysql_query($sql, $dbh);

        $items = array();

        echo " <channel>\n  <title>$rss_latestartist_title</title>\n";
        echo "  <link>$web_path</link>\n  <description>$rss_main_description</description>\n";
        echo "  <copyright>$rss_main_copyright</copyright>\n";
        echo "  <pubDate>$today</pubDate>\n";


        while ( $item = mysql_fetch_row($db_result) ) {
		echo "   <item>\n";
                $artist = new Artist($item[0]);
		$album = new Album($item[1]);
                $album->format_album();
                $artist->format_artist();
		echo "    <title><![CDATA[$artist->full_name]]></title>\n";
 		echo "    <link>$web_path/artists.php?action=show&amp;artist=$item[0]</link>\n";
 		echo "    <description><![CDATA[$artist->full_name - $album->name]]></description>\n";
 		echo "   </item>\n";
        }
        echo " </channel>\n</rss>";
	break;

    case "latestalbum":

        $sql = "SELECT DISTINCT album FROM song ORDER BY addition_time DESC LIMIT 10";
        $db_result = mysql_query($sql, $dbh);

        echo " <channel>\n  <title>$rss_latestalbum_title</title>\n";
        echo "  <link>$web_path</link>\n  <description>$rss_main_description</description>\n";
        echo "  <copyright>$rss_main_copyright</copyright>\n";
        echo "  <pubDate>$today</pubDate>\n";
	

        while ( $item = mysql_fetch_row($db_result) ) {
		echo "<item>\n";
		$album = new Album($item[0]);
                $album->format_album();
 		echo " <title><![CDATA[$album->name]]></title>\n";
 		echo " <link>$web_path/albums.php?action=show&amp;album=$item[0]</link>\n";
 		echo " <description><![CDATA[$album->name - $album->artist]]></description>\n";
 		echo "</item>\n";
        }
        echo "</channel>\n</rss>";
	break;

    case "recentlyplayed":

	$time_unit = array('',_('seconds ago'),_('minutes ago'),_('hours ago'),_('days ago'),_('weeks ago'),_('months ago'),_('years ago'));
	$recent = get_recently_played();

        echo " <channel>\n  <title>$rss_recentlyplayed_title</title>\n";
        echo "  <link>$web_path</link>\n  <description>$rss_main_description</description>\n";
        echo "  <copyright>$rss_main_copyright</copyright>\n";
        echo "  <pubDate>$today</pubDate>\n";

	foreach ( $recent as $item ){
    	    $amount = intval(time() - $item['date']+2);
            $time_place = '0';

	    while ($amount >= 1) {
        	    $final = $amount;
    	            $time_place++;
            	    $amount = floor($amount/60);
            }

	    $time_string = $final . ' ' . $time_unit[$time_place];

		echo "<item>\n";
		$song = new Song($item['object_id']);
                $song->format();
		$user = new User($item['user']);
		$user->format_user();
 		echo " <title><![CDATA[$song->title]]></title>\n";
 		echo " <link>$web_path/stream.php?action=single_song&amp;song_id=".$item['object_id']."</link>\n";
 		echo " <description><![CDATA[$user->fullname played $song->title - $song->f_artist $time_string]]></description>\n";
 		echo "</item>\n";
	}
        echo "</channel>\n</rss>";
	break;



    default:
	$now_playing = get_now_playing();
        $rss_song_description = $rss_description;

        echo " <channel>\n  <title>$rss_main_title</title>\n";
        echo "  <link>$web_path</link>\n  <description>$rss_main_description</description>\n";
        echo "  <copyright>$rss_main_copyright</copyright>\n";
        echo "  <pubDate>$today</pubDate>\n";

	foreach ( $now_playing as $r ){

		$song = $r['song'];
		$user = $r['user'];
                $song->format();
                        $artist = $song->f_artist;
                        $album = $song->get_album_name();
                        $text = "$artist - $song->f_title played by $user->fullname";
                        echo "<item> \n";
                        echo " <title><![CDATA[$text]]></title> \n";
			echo " <image>$web_path/image.php?id=$song->album</image>\n";
                        echo " <link>$web_path/albums.php?action=show&amp;album=$song->album</link>\n";
                        echo " <description><![CDATA[$song->f_title @ $album is played by $user->fullname]]></description>\n";
                        echo " <pubDate>$today</pubDate>\n";
                        echo "</item>\n";
        }

        echo "</channel>\n</rss>";
    	break;
}
}
?>
