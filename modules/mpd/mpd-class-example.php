<?php 
/*
 *  mpd-class-example.php - Example interface using mpd.class.php
 *  Version 1.2, released 05/05/2004
 *  Copyright (C) 2003-2004  Benjamin Carlisle (bcarlisle@24oz.com)
 *  http://mpd.24oz.com/ | http://www.musicpd.org/
 *
 *  This program illustrates the basic commands and usage of the MPD class. 
 *  
 *  *** PLEASE NOTE *** My intention in including this file is not to provide you with an 
 *    out-of-the-box MPD jukebox, but instead to provide a general understanding of how I saw 
 *    the class as being utilized. If you'd like to see more examples, please let me know. But 
 *    this should provide you with a good starting point for your own program development.
 *
 *  This program is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program; if not, write to the Free Software
 *  Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/
?>
<HTML>
<style type="text/css"><!-- .defaultText {  font-family: Arial, Helvetica, sans-serif; font-size: 9pt; font-style: normal; font-weight: normal; color: #111111} .err { color: #DD3333 } --></style>
<BODY class="defaultText">
<?php 
	include('mpd.class.php');
	$myMpd = new mpd('localhost',2100);

    if ( $myMpd->connected == FALSE ) {
    	echo "Error Connecting: " . $myMpd->errStr;
	} else {
		switch ($_REQUEST[m]) {
			case "add":
				if ( is_null($myMpd->PLAdd($_REQUEST[filename])) ) echo "<SPAN CLASS=err>ERROR: " .$myMpd->errStr."</SPAN>";
				break;
			case "rem":
				if ( is_null($myMpd->PLRemove($_REQUEST[id])) ) echo "<SPAN CLASS=err>ERROR: " .$myMpd->errStr."</SPAN>";
				break;
            case "setvol":
                if ( is_null($myMpd->SetVolume($_REQUEST[vol])) ) echo "<SPAN CLASS=err>ERROR: " .$myMpd->errStr."</SPAN>";
                break;
			case "play":
				if ( is_null($myMpd->Play()) ) echo "<SPAN CLASS=err>ERROR: " .$myMpd->errStr."</SPAN>";
				break;
			case "stop":
				if ( is_null($myMpd->Stop()) ) echo "<SPAN CLASS=err>ERROR: " .$myMpd->errStr."</SPAN>";
				break;
			case "pause":
				if ( is_null($myMpd->Pause()) ) echo "<SPAN CLASS=err>ERROR: " .$myMpd->errStr."</SPAN>";
				break;
			default:
				break;
		}
?>
<DIV ALIGN=CENTER>[ <A HREF="<?php  echo $_SERVER[PHP_SELF] ?>">Refresh Page</A> ]</DIV>
<HR>
<B>Connected to MPD Version <?php  echo $myMpd->mpd_version ?> at <?php  echo $myMpd->host ?>:<?php  echo $myMpd->port ?></B><BR>
State: 
<?php 
	switch ($myMpd->state) {
		case MPD_STATE_PLAYING: echo "MPD is Playing [<A HREF='".$_SERVER[PHP_SELF]."?m=pause'>Pause</A>] [<A HREF='".$_SERVER[PHP_SELF]."?m=stop'>Stop</A>]"; break;
		case MPD_STATE_PAUSED:  echo "MPD is Paused [<A HREF='".$_SERVER[PHP_SELF]."?m=pause'>Unpause</A>]"; break;
		case MPD_STATE_STOPPED: echo "MPD is Stopped [<A HREF='".$_SERVER[PHP_SELF]."?m=play'>Play</A>]"; break;
		default:                echo "(Unknown State!)"; break;
	} 
?>
<BR>
Volume:   <?php  echo $myMpd->volume ?> [ <A HREF='<?php  echo $_SERVER[PHP_SELF] ?>?m=setvol&vol=0'>0</A> | <A HREF='<?php  echo $_SERVER[PHP_SELF] ?>?m=setvol&vol=25'>25</A> | <A HREF='<?php  echo $_SERVER[PHP_SELF] ?>?m=setvol&vol=75'>75</A> | <A HREF='<?php  echo $_SERVER[PHP_SELF] ?>?m=setvol&vol=100'>100</A> ]<BR>
Uptime:   <?php  echo secToTimeStr($myMpd->uptime) ?><BR>
Playtime: <?php  echo secToTimeStr($myMpd->playtime) ?><BR>

<?php   if ( $myMpd->state == MPD_STATE_PLAYING or $myMpd->state == MPD_STATE_PAUSED ) {  ?>
   Currently Playing: <?php  echo $myMpd->playlist[$myMpd->current_track_id]['Artist']." - ".$myMpd->playlist[$myMpd->current_track_id]['Title'] ?><BR>
   Track Position:  <?php  echo $myMpd->current_track_position."/".$myMpd->current_track_length." (".(round(($myMpd->current_track_position/$myMpd->current_track_length),2)*100)."%)" ?><BR>
   Playlist Position:  <?php  echo ($myMpd->current_track_id+1)."/".$myMpd->playlist_count." (".(round((($myMpd->current_track_id+1)/$myMpd->playlist_count),2)*100)."%)" ?><BR>
<?php   }  ?>
<HR>

<B>Playlist - Total: <?php  echo $myMpd->playlist_count ?> tracks (Click to Remove)</B><BR>
<?php 
		if ( is_null($myMpd->playlist) ) echo "ERROR: " .$myMpd->errStr."\n";
		else {
			foreach ($myMpd->playlist as $id => $entry) {
				echo ( $id == $myMpd->current_track_id ? "<B>" : "" ) . ($id+1) . ". <A HREF='".$_SERVER[PHP_SELF]."?m=rem&id=".$id."'>".$entry['Artist']." - ".$entry['Title']."</A>".( $id == $myMpd->current_track_id ? "</B>" : "" )."<BR>\n";
			}
		}
?>
<HR>
<B>Sample Search for the String 'U2' (Click to Add to Playlist)</B><BR>
<?php 
		$sl = $myMpd->Search(MPD_SEARCH_ARTIST,'U2');
		if ( is_null($sl) ) echo "ERROR: " .$myMpd->errStr."\n";
		else {
			foreach ($sl as $id => $entry) {
				echo ($id+1) . ": <A HREF='".$_SERVER[PHP_SELF]."?m=add&filename=".urlencode($entry['file'])."'>".$entry['Artist']." - ".$entry['Title']."</A><BR>\n";
			}
		}
        if ( count($sl) == 0 ) echo "<I>No results returned from search.</I>";


    //  Example of how you would use Bulk Add features of MPD
    // $myarray = array();
    // $myarray[0] = "ACDC - Thunderstruck.mp3";
    // $myarray[1] = "ACDC - Back In Black.mp3";
    // $myarray[2] = "ACDC - Hells Bells.mp3";
    
    // if ( is_null($myMpd->PLAddBulk($myarray)) ) echo "ERROR: ".$myMpd->errStr."\n";
?>
<HR>
<B>Artist List</B><BR>
<?php 
    if ( is_null($ar = $myMpd->GetArtists()) ) echo "ERROR: " .$myMpd->errStr."\n";
    else {
        while(list($key, $value) = each($ar) ) {
            echo ($key+1) . ". " . $value . "<BR>";
        }
    }

		$myMpd->Disconnect();
	}

    // ---------------------------------------------------------------------------------
    // Used to make number of seconds perty.
	function secToTimeStr($secs) {
		$days    =    ($secs%604800)/86400; 
		$hours   =   (($secs%604800)%86400)/3600; 
		$minutes =  ((($secs%604800)%86400)%3600)/60; 
		$seconds = (((($secs%604800)%86400)%3600)%60);
		if (round($days))    $timestring .= round($days)."d "; 
		if (round($hours))   $timestring .= round($hours)."h "; 
		if (round($minutes)) $timestring .= round($minutes)."m"; 
		if (!round($minutes)&&!round($hours)&&!round($days)) $timestring.=" ".round($seconds)."s"; 
		return $timestring;
	} // --------------------------------------------------------------------------------
?>
</BODY></HTML>
