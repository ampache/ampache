<?php
/*

 Copyright (c) 2004 Ampache.org
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
	@header Show mpd form
*/
?>
<div align="center">
<table border="0" cellpadding="0" cellspacing="0">
<tr>
	<td>
	<table border="0" cellpadding="0" cellspacing="0" bgcolor="<?php conf('base_color1'); ?>" width="100%">
        <TR><TD CLASS="content">
State: <?php 
        switch ($myMpd->state) {
                case MPD_STATE_PLAYING: echo "<B>Playing</B> [<A HREF='".$_SERVER[PHP_SELF]."?m=pause'>Pause</A>] [<A HREF='".$_SERVER[PHP_SELF]."?m=stop'>Stop</A>]"; break;
                case MPD_STATE_PAUSED:  echo "<B>Paused</B> [<A HREF='".$_SERVER[PHP_SELF]."?m=pause'>Unpause</A>]"; break;
                case MPD_STATE_STOPPED: echo "<B>Idle</B> [<A HREF='".$_SERVER[PHP_SELF]."?m=play'>Play</A>]"; break;
                default:                echo "(Unknown State!)"; break;
        } ?>
      </TD></TR>
      <TR><TD CLASS="content">
       Loop: <?php 
        switch ($myMpd->repeat) {
                case 0:  echo "<B>Loop is off</B> [<A HREF='".$_SERVER[PHP_SELF]."?m=loop&val=1'>On</A>]"; break;
                case 1:  echo "<B>Loop is on</B> [<A HREF='".$_SERVER[PHP_SELF]."?m=loop&val=0'>Off</A>]"; break;
                default: echo "(Unknown State!)"; break;
        } ?>
      </TD></TR>
      <TR><TD CLASS="content">
                &nbsp;
          </TD></TR>
    </TABLE>
  </TR></TD>
<?php   if ( $myMpd->state == MPD_STATE_PLAYING or $myMpd->state == MPD_STATE_PAUSED ) {  ?>
  <TR><TD>
    <TABLE BORDER=0 CELLPADDING=0 CELLSPACING=0 BGCOLOR="WHITE" WIDTH="100%">
      <TR><TD CLASS="content">Now Playing: (<?php  echo (round(($myMpd->current_track_position/$myMpd->current_track_length),2)*100) ?>% cmpl.)</TD></TR>
      <TR><TD CLASS="content" ALIGN=CENTER><SPAN CLASS=SongPlaying><?php  echo $myMpd->playlist[$myMpd->current_track_id]['Artist']." - ".$myMpd->playlist[$myMpd->current_track_id]['Title'] ?> </SPAN></TD></TR>
    </TABLE>
  </TD></TR>
<?php  } ?>
  <TR><TD>
    <TABLE BORDER=0 CELLPADDING=0 CELLSPACING=0 BGCOLOR="WHITE" WIDTH="100%">
      <TR><TD CLASS="content" ALIGN=CENTER>[ <A TITLE="Refresh the Playlist Window" HREF="<?php  echo $_SERVER[PHP_SELF] ?>">refresh now</A> ]</TD></TR>
    </TABLE>
  </TD></TR>
</TABLE>
<BR>
<TABLE BORDER=0 CELLPADDING=0 CELLSPACING=0 WIDTH=<?php  echo $PG_WIDTH ?>>
  <TR><TD ALIGN="CENTER"><B>Server Playlist</B></TD></TR>
  <TR><TD>
    <TABLE BORDER=0 CELLPADDING=0 CELLSPACING=0 BGCOLOR="WHITE" WIDTH="100%">
<?php 
                $pl = $myMpd->GetPlaylist();
                if ( is_null($pl) ) echo "ERROR: " .$myMpd->errStr."\n";
                else {
                        foreach ($pl as $id => $entry) {
                                $tblClass = ( $id == $myMpd->current_track_id ? "SongPlaying" : "Song" );
                                echo "<TR HEIGHT=35><TD CLASS=\"content\"><SPAN CLASS=\"".$tblClass."\">";
	                        echo "<A TITLE=\"Click to remove '".$entry['Title']."' from playlist\" HREF='".$_SERVER[PHP_SELF]."?m=rem&val=".$id."'>".($id+1)."</A>" . ". <A TITLE=\"Click to jump to '".$entry['Title']."' from playlist\" HREF='".$_SERVER[PHP_SELF]."?m=skipto&id=".$id."'>".$entry['Artist']." - ".$entry['Title']."</A>";	
                                echo "</SPAN></TD></TR>";
                                echo "\n"; // make it perty
                        }
                }
                if ( $myMpd->playlist_count == 0 ) {
                        echo "<TR><TD CLASS=\"content\" ALIGN=CENTER><SPAN CLASS=\"Song\"><I>(Playlist is empty)</I></SPAN></TD></TR>";
                }
?>
        </TABLE>
  </TD></TR>
<?php       if ( $myMpd->playlist_count > 0 ) { ?>
  <TR HEIGHT=20><TD ALIGN=CENTER>[<A TITLE="Click to shuffle (randomize) the playlist" HREF="<?php  echo $_SERVER[PHP_SELF] ?>?m=shuffle">shuffle</A>] [<A TITLE="Click the clear the playlist" HREF="<?php  echo $_SERVER[PHP_SELF] ?>?m=clear">clear</A>]</TD></TR>
<?php   }  ?>
</TABLE>
</DIV>
<!-- MPD-Class version: <?php  echo $myMpd->mpd_class_version ?> -->
</BODY></HTML>
