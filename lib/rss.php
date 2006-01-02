<?php
/*

 Copyright 2001 - 2006 Ampache.org
 All Rights Reserved

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
        @function show_now_playingRSS
        @discussion creates a RSS fead for the now
                playing information
*/
function show_now_playingRSS () {

        $dbh = dbh();
        $web_path = conf('web_path');
        $rss_main_title = conf('rss_main_title');
        $rss_main_description = conf('rss_main_description');
        $rss_main_copyright = conf('rss_main_copyright');
        $rss_main_language = conf('rss_main_language');
        $rss_song_description = conf('rss_song_description');

        $sql = "SELECT * FROM now_playing ORDER BY start_time DESC";

        $db_result = mysql_query($sql, $dbh);
        $today = date("d-m-Y");

        echo "<rss version=\"0.91\">";
        echo "<channel>\n<title>$rss_main_title</title>\n";
        echo "<link>$web_path</link>\n<description>$rss_main_description</description>\n";
        echo "<copyright>$rss_main_copyright</copyright>";
        echo "<pubDate>$today</pubDate>\n<language>$rss_main_language</language>\n";

        while ($r = mysql_fetch_object($db_result)) {
                $song = new Song($r->song_id);
                $song->format_song();
                $user = get_user_byid($r->user_id);
                if (is_object($song)) {
                        $artist = $song->f_artist;
                        $album = $song->get_album_name();
                        $text = "$artist - $song->f_title";
                        echo "<item> ";
                        echo " <title><![CDATA[$text]]></title> ";
                        echo " <link>$web_path/albums.php?action=show&amp;album=$song->album</link>";
                        echo " <description>$rss_song_description</description>";
                        echo " <pubDate>$today</pubDate>";
                        echo "</item>";
                }
        }
 
        echo "</channel>\n</rss>";
} // show_now_playingRSS
?>
