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

require('../lib/init.php');

if (!$GLOBALS['user']->has_access(100)) {
	access_denied();
	exit();
}


$action = scrub_in($_POST['action']);
$to = scrub_in($_REQUEST['to']);
if (isset ($_POST['cat_stats'])){$cat_stats = scrub_in($_POST['cat_stats']);}
if (isset ($_POST['pop_albums'])){$pop_albums = scrub_in($_POST['pop_albums']);}
if (isset ($_POST['pop_artists'])){$pop_artists = scrub_in($_POST['pop_artists']);}
if (isset ($_POST['pop_songs'])){$pop_songs = scrub_in($_POST['pop_songs']);}
if (isset ($_POST['threshold'])){$threshold = scrub_in($_POST['threshold']);} else { $threshold = conf($stats_threshold); };
if (isset ($_POST['new_artists'])){$new_artists = scrub_in($_POST['new_artists']);}
if (isset ($_POST['new_albums'])){$new_albums = scrub_in($_POST['new_albums']);}
if (isset ($_POST['flagged'])){$flagged = scrub_in($_POST['flagged']);}
$subject = stripslashes(scrub_in($_POST['subject']));
$message = stripslashes(scrub_in($_POST['message']));

/* Always show the header */
show_template('header');

switch ($action) { 
	case 'send_mail':
		if (conf('demo_mode')) { break; } 

		// do the mail mojo here
		if ( $to == 'all' ) {
			$sql = "SELECT * FROM user WHERE email IS NOT NULL";
		}
		elseif ( $to == 'users' ) {
			$sql = "SELECT * FROM user WHERE access='user' OR access='25' AND email IS NOT NULL";
		}
		elseif ( $to == 'admins' ) {
			$sql = "SELECT * FROM user WHERE access='admin' OR access='100' AND email IS NOT NULL";
		}
		elseif ( $to == 'inactive' ) {
			if (isset ($_POST['inactive'])){
			 $days = $_POST['inactive'];
			} else {
			    $days = "30";
			}
			$inactive = time() - ($days * 24 * 60 *60);
			$sql = "SELECT * FROM user WHERE last_seen <= '$inactive' AND email IS NOT NULL";
		}

  
		$db_result = mysql_query($sql, dbh());
  
		$recipient = '';

		while ( $u = mysql_fetch_object($db_result) ) {
			$recipient .= "$u->fullname <$u->email>, ";
		}
		
		// Remove the last , from the recipient
		$recipient = rtrim($recipient,",");

		$from    = $user->fullname."<".$user->email.">";
        
        if (isset ($cat_stats)){
        /* Before we display anything make sure that they have a catalog */
        $query = "SELECT * FROM catalog";
	$dbh = dbh();
        $db_results = mysql_query($query, dbh());
        if (!mysql_num_rows($db_results)) {
                $items[] = "<span align=\"center\" class=\"error\">" . _("No Catalogs Found!") . "</span><br />";
                $items[] = "<a href=\"" . conf('web_path') . "/admin/catalog.php?action=show_add_catalog\">" ._("Add a Catalog") . "</a>";
                show_info_box(_('Catalog Statistics'),'catalog',$items);
                return false;
		break;
        }

        $query = "SELECT count(*) AS songs, SUM(size) AS size, SUM(time) as time FROM song";
        $db_result = mysql_query($query, $dbh);
        $songs = mysql_fetch_assoc($db_result);

        $query = "SELECT count(*) FROM album";
        $db_result = mysql_query($query, $dbh);
        $albums = mysql_fetch_row($db_result);

        $query = "SELECT count(*) FROM artist";
        $db_result = mysql_query($query, $dbh);
        $artists = mysql_fetch_row($db_result);

        $sql = "SELECT count(*) FROM user";
        $db_result = mysql_query($sql, $dbh);
        $users = mysql_fetch_row($db_result);

        $time = time();
        $last_seen_time = $time - 1200;
        $sql =  "SELECT count(DISTINCT s.username) FROM session AS s " .
                "INNER JOIN user AS u ON s.username = u.username " .
                "WHERE s.expire > " . $time . " " .
                "AND u.last_seen > " . $last_seen_time;
        $db_result = mysql_query($sql, $dbh);
        $connected_users = mysql_fetch_row($db_result);

        $hours = floor($songs['time']/3600);
        $size = $songs['size']/1048576;

        $days = floor($hours/24);
        $hours = $hours%24;

        $time_text = "$days ";
        $time_text .= ($days == 1) ? _("day") : _("days");
        $time_text .= ", $hours ";
        $time_text .= ($hours == 1) ? _("hour") : _("hours");

        if ( $size > 1024 ) {
                $total_size = sprintf("%.2f", ($size/1024));
                $size_unit = "GB";
        }
        else {
                $total_size = sprintf("%.2f", $size);
                $size_unit = "MB";
        }
		$stats 	= _('Total Users')."		".$users[0]."\n";
		$stats .= _('Connected Users')."	".$connected_users[0]."\n";
		$stats .= _('Albums')."		".$albums[0]."\n";
		$stats .= _('Artists')."		".$artists[0]."\n";
		$stats .= _('Songs')."			".$songs['songs']."\n";
		$stats .= _('Catalog Size')."	".$total_size." ".$size_unit."\n";
		$stats .= _('Catalog Time')."	".$time_text."\n"; 

                $message .= "\n\nAmpache Catalog Statistics\n\n";
		$message .= "$stats";
		}
	
	if (isset ($pop_albums)){
		$message .= "\n\nMost Popular Albums\n\n";
		$stats = new Stats();
		$stats = $stats->get_top('10','album',$threshold);

		foreach( $stats as $r){
		$album   = new Album($r[object_id]);
                $palbums .= $album->name." (". $r[count].")\n";
		}
                $message .= "$palbums";
	}

       if (isset ($pop_artists)){
                $message .= "\n\nMost Popular Artists\n\n";
		$stats = new Stats();
		$stats = $stats->get_top('10','artist',$threshold);

		foreach( $stats as $r){
                        $artist   = new Artist($r[object_id]);
                        $partists .= $artist->name." (". $r[count].")\n";
	        }
                $message .= "$partists";
        }

       if (isset ($pop_songs)){

                $message .= "\n\nMost Popular Songs\n\n";
		$stats = new Stats();
		$stats = $stats->get_top('10','song',$threshold);

		foreach( $stats as $r){
		$song = new Song($r[object_id]);
		$artist = $song->get_artist_name();
                $text = "$artist - $song->title";
                $psongs .= $text." (". $r[count].")\n";
		}    
                $message .= "$psongs";
        }

        if (isset ($new_artists)){

        $sql = "SELECT DISTINCT artist FROM song ORDER BY addition_time " .
                "DESC LIMIT " . conf('popular_threshold');
        $db_result = mysql_query($sql, dbh());

        while ( $item = mysql_fetch_row($db_result) ) {
                $artist = new Artist($item[0]);
		$nartists .= $artist->name."\n";
                }
                $message .= "\n\nLatest Artist Additions\n\n";
                $message .= "$nartists";
        }

       if (isset ($new_albums)){

        $sql = "SELECT DISTINCT album FROM song ORDER BY addition_time " .
                "DESC LIMIT " . conf('popular_threshold');
        $db_result = mysql_query($sql, dbh());

	
        while ( $item = mysql_fetch_row($db_result) ) {
                        $album = new Album($item[0]);
			$nalbums .= $album->name."\n";
		}
                $message .= "\n\nLatest Album Additions\n\n";
                $message .= "$nalbums";

	}

       if (isset ($flagged)){

	    $flag = new Flag();
	    $flagged = $flag->get_flagged();
            $message .= "\n\nFlagged Songs\n\n";
	    $message .= "Name\t\t\t\tFlag\t\tFlagged by\tStatus\n";
	    foreach ($flagged as $data){ 

		$flag = new Flag($data);
		$flag->format_name();
		$name = $flag->name;
		$user = $flag->user;
		$flag = $flag->flag;
		if($flag->approved){ $status = "Approved"; } else { $status = "Pending"; }
		$message .= "*) $name\t$flag\t\t$user\t\t$status\n";
	    }
}

       if (isset ($disabled)){

	    $catalog = new Catalog();
	    $songs = $catalog->get_disabled();
            $message .= "\n\nDisabled Songs\n\n";

	    foreach ($songs as $song){ 

    		$name = "*) ". $song->title ." | ". $song->get_album_name($song->album) ." | ". $song->get_artist_name($song->album) ." | ". $song->file ;
		$message .= "$name";
	    }
}

		// woohoo!!
		mail ($from, $subject, $message,
			"From: $from\r\n".
			"Bcc: $recipient\r\n");

		/* Confirmation Send */
		$url 	= conf('web_path') . '/admin/mail.php';
		$title 	= _('E-mail Sent'); 
		$body 	= _('Your E-mail was successfully sent.');
		show_confirmation($title,$body,$url);
	break;
	default: 
		if ( empty($to) ) {
			$to = 'all';
		}

		if ( empty($subject) ) {
			$subject = "[" . conf('site_title') . "] ";
		}
		require (conf('prefix') . '/templates/show_mail_users.inc.php');
	break;
} // end switch

show_footer(); 


?>
