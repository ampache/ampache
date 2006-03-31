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

//
//  

function add_to_edit_queue($flags=0)
{
    $oldflags = 0;
    if(empty($flags)) $flags = 0;
    if($_SESSION['edit_queue'])
    {
        $oldflags = $_SESSION['edit_queue'];
        if(!is_array($oldflags)) $oldflags = array($oldflags);
    }

    if(!is_array($flags))
    {
        if($flags !== 0) $flags = array($flags);
    }

    if(is_array($flags))
    {
        if(is_array($oldflags)) $new_array = array_merge($flags, $oldflags);
        else $new_array = $flags;
    }
    elseif (is_array($oldflags)) $new_array = $oldflags;

    if(count($new_array))
    {
        $_SESSION['edit_queue'] = $new_array;
        return count($new_array);
    }
    else
    {
        unset($_SESSION['edit_queue']);
        return 0;
    }
}

function show_edit_flagged($flag=0)
{
    if(empty($flag)||$flag === 0)
    {
        $flag = array_pop($_SESSION['edit_queue']);
    }
    $flaginfo = get_flag($flag);
    if($flaginfo['type'] === 'badid3')
    {
        show_edit_badid3($flaginfo['song'],$flag);
    }
    else
    {
        echo "I don't know how to edit already edited songs yet: $flag.<br />";
    }
}

function show_edit_badid3($songid,$flagid)
{
    $song = get_song_info($songid);
    require(conf('prefix')."/templates/song_edit.inc");
}

function get_flag($id)
{
    if(!is_array($id)) $id = array($id);
    $results = array();
    $newid = array_pop($id);
    $sql = "SELECT flagged.id,user.username,type,song,date,comment" .
           " FROM flagged,user WHERE flagged.user = user.username AND (flagged.song = '$newid'";
    foreach($id as $num)
    {
        $sql .= " OR flagged.song = '$num'";
    }
    $sql .= ")";
    $result = mysql_query($sql, dbh());
    while ($row = mysql_fetch_array($result))
    {
        $results[] = $row;
    }
    if(count($results) == 1) return $results[0];
    else return $results;
}

   
function get_flagged_songs($user = 0)
{
    $sql = "SELECT flagged.id,user.username,type,song,date,comment" .
           " FROM flagged,user WHERE flagged.user = user.username AND flagged.type <> 'notify' AND flagged.type <> 'done'";

    // If the user is not an admin, they can only see songs they've flagged
    if($user)
    {
        if($_SESSION['userdata']['access'] === 'admin')
        {
            $sql .= " AND user.username = '$user'";
        }
        else
        {
            $sql .= " AND user.username = '".$_SESSION['userdata']['username']."'";
        }
    }
    
    $sql .= " ORDER BY date";
    $result = mysql_query($sql, dbh());

    $arr = array();

    while ($flag = mysql_fetch_array($result))
    {
        $arr[] = $flag;
    }
    return $arr;
}

function accept_new_tags($flags)
{
    if(!is_array($flags)) $flags = array($flags);
    foreach($flags as $flag)
    {
        copy_updated_tag($flag);
    }
    set_flag_value($flags, 'setid3');
}


function reject_new_tags($flags)
{
    if(!is_array($flags)) $flags = array($flags);
    $oldflags = $flags;
    $flag = array_pop($flags);
    $sql = "DELETE FROM flagged_songs WHERE song = '$flag'"; 

    foreach($flags as $flag)
    {
        $sql .= " OR song = '$flag'";
    }
    $result = mysql_query($sql, dbh());
    $user = $_SESSION['userdata']['username'];
    set_flag_value($oldflags, 'notify', "Tag changes rejected by $user");
} 

function set_flag_value($flags, $val, $comment = '')
{
    if(!is_array($flags)) $flags = array($flags);
    $user = $_SESSION['userdata']['id'];
/*    $flagid = array_pop($flags);*/
    $dbh = dbh();
    foreach($flags as $flagid)
    {
        $sql = "REPLACE INTO flagged (type,song,comment,user,date)".
               " VALUES ('$val','$flagid','$comment','$user','".time()."')";
        $result = mysql_query($sql, $dbh);
    }
    return $result;
}

function copy_updated_tag($flag)
{
    $flagdata = get_flag($flag);
    $sql = "SELECT * FROM flagged_song WHERE song = '".$flagdata['song']."'";
    $result = mysql_query($sql, dbh());
    $newtag = mysql_fetch_array($result);

    if($newtag['new_artist']) 
    {
        $newtag['artist'] = insert_artist($newtag['new_artist']);
    }
    if($newtag['new_album'])
    {
        $newtag['album'] = insert_album($newtag['new_album']);
    }

    $sql = "UPDATE song SET ".
           "title = '".$newtag['title']."',".
           "artist = '".$newtag['artist']."',".
           "album = '".$newtag['album']."',".
           "track = '".$newtag['track']."',".
           "genre = '".$newtag['genre']."',".
           "year = '".$newtag['year']."' ".
           "WHERE song.id = '".$newtag['song']."'";
    $result = mysql_query($sql, dbh());
    if($result)
    {
        $sql2 = "DELETE FROM flagged_song WHERE song='".$flagdata['song']."'";
        $result2 = mysql_query($sql2, dbh());
    }
    return ($result && $result2);

}

function update_flags($songs)
{
    $accepted = array();
    $rejected = array();
    $newflags = array();
    foreach($songs as $song)
    {
        $accept = scrub_in($_REQUEST[$song.'_accept']);
        if($accept === 'accept') $accepted[] = $song; 
        elseif ($accept === 'reject') $rejected[] = $song; 
        else
        {
            $newflag = scrub_in($_REQUEST[$song.'_newflag']);
            $newflags[$song] = $newflag;
        }
    }

    if(count($accepted)) 
    {
        accept_new_tags($accepted);
    }
    if(count($rejected)) 
    {
        reject_new_tags($rejected);
    }
    if(count($newflags)) 
    {
        foreach($newflags as $flag=>$type)
        {
            set_flag_value($flag, $type);
        }
    }

}


function update_song_info($song)
{
    $user = $_SESSION['userdata'];

    $title = scrub_in($_REQUEST['title']);
    $track = scrub_in($_REQUEST['track']);
    $genre = scrub_in($_REQUEST['genre']);
    $year = scrub_in($_REQUEST['year']);

    if(isset($_REQUEST['update_id3']))
        $update_id3 = 1;

    if(isset($_REQUEST['new_artist']) && $_REQUEST['new_artist'] !== '')
    {
        $create_artist = 1;
        $artist = scrub_in($_REQUEST['new_artist']);
    }
    else
        $artist = scrub_in($_REQUEST['artist']);

    if(isset($_REQUEST['new_album']) && $_REQUEST['new_album'] !== '') 
    {
        $create_album = 1;
        $album = scrub_in($_REQUEST['new_album']);
    }
    else 
        $album = scrub_in($_REQUEST['album']);

    if(is_array($_REQUEST['genre'])) {
  	$genre = $genre[0];
    }

    if($user['access'] == 'admin')
    // Update the file directly
    {
        if($create_artist)
        {
            $artist = insert_artist($artist);
        }
        if($create_album)
        {
            $album = insert_album($album);
        }
	// Escape data (prevent " or ' snafu's)
	$title 	= sql_escape($title);
	$artist = sql_escape($artist);
	$album 	= sql_escape($album);
	$genre 	= sql_escape($genre);
	$year 	= sql_escape($year);
	
        $sql = "UPDATE song SET" . 
               " title = '$title'," .
               " track = '$track'," .
               " genre = '$genre'," .
               " year  = '$year',"  .
               " artist = '$artist',".
               " album = '$album'," .
               " update_time = '".time()."'" .
               " WHERE id = '$song' LIMIT 1";
        $result = mysql_query($sql, dbh() );
        if($result && $update_id3 )
        {
            //Add to flagged table so we can fix the id3 tags
            $date = time();
            $sql = "REPLACE INTO flagged SET " .
                   " type = 'setid3', song = '$song', date = '$date', user = '".$user['id']."'";
            $result = mysql_query($sql, dbh());                    
        }
    }

    else
    // Stick in the flagged_songs table to be updated by an admin
    {
        if($create_artist) $artist_field = 'new_artist';
        else $artist_field = 'artist';

        if($create_album) $album_field = 'new_album';
        else $album_field = 'album';

        $sql = "INSERT INTO flagged_song(song,title,track,genre,year,$artist_field,$album_field,update_time) " . 
               "VALUES ('$song','$title','$track','$genre','$year','$artist','$album','".time()."')";
        $result = mysql_query($sql, dbh() );

        if($result && $update_id3 )
        {
            //Add to flagged table so we can fix the id3 tags
            $date = time();
            $sql = "REPLACE INTO flagged SET " .
                   " type = 'newid3', song = '$song', date = '$date', user = '".$user['id']."'";
            $result = mysql_query($sql, dbh());                    
        }
        echo "Thanks for helping to keep the catalog up to date.  Someone will review your changes, and you will be notified on the main page when they're approved.";

    }
}

