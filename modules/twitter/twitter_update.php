<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * Adapted for Ampache by Chris Slamar
 * FIXME: Adapted? Have we stolen this code?
 *
 * LICENSE: GNU General Public License, version 2 (GPLv2)
 * Copyright 2001 - 2013 Ampache.org
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License v2
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 *
 */

require_once '../../lib/init.php';
require_once( Config::get('prefix') . "/modules/twitter/twitteroauth/twitteroauth.php");
    session_start();


if(!empty($_SESSION['twitterusername'])) {

    $nowplayingQuery = "SELECT song.title,artist.name FROM song,now_playing,artist WHERE song.id = now_playing.object_id AND artist.id = song.artist";
    debug_event("Twitter", "Now Playing query: " . $nowplayingQuery, "6");
    
    $nowplayingRun = Dba::read($nowplayingQuery);
    $nowplayingResults = Dba::fetch_assoc($nowplayingRun);

    $return = $nowplayingResults['title'] . " by " . $nowplayingResults['name'];
    debug_event("Twitter", "Song from DB is: " . $return, "5");

    $selectquery = "SELECT * FROM twitter_users WHERE username = '" . $_SESSION['twitterusername'] . "' AND ampache_id = " . $_SESSION['userdata']['uid'];
    debug_event("Twitter", "Select query: " . $selectquery, "6");

    $selectrun = Dba::read($selectquery);
    $result = Dba::fetch_assoc($selectrun);

    $twitteroauth = new TwitterOAuth( Config::get('twitter_consumer_key'), Config::get('twitter_consumer_secret'), $result['oauth_token'], $result['oauth_secret']);
    $user_info = $twitteroauth->get('account/verify');
    if( $user_info->error == 'Not found' ) {
        debug_event("Twitter", "Auth Successful! Posting Status", "5");
        $twitteroauth->post('statuses/update', array('status' => 'is rocking out to ' . $return));
        header('Location: ' . Config::get('web_path') );
    }
    
} else {
        debug_event("Twitter", "Auth Error going back to home.", "5");
        header('Location: ' . Config::get('web_path') );
}
?>
