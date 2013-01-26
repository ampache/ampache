<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * Adapted for Ampache by Chris Slamar
 * FIXME: V. suspicious about this whole "Adapted" thing
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
    header('Location: ' . Config::Get('web_path') . '/modules/twitter/twitter_update.php');
    debug_event("Twitter", "Twitter user has logged in this session.", "5");
}

if(!empty($_GET['oauth_verifier']) && !empty($_SESSION['oauth_token']) && !empty($_SESSION['oauth_token_secret'])){
    debug_event("Twitter", "Got all 3 pieces for auth", "5");
} else {
    if( $_SESSION['twitterCount'] < 4 ) {
        debug_event("Twitter", "Didn't get all 3 auth pieces, going to try again.  Try #" . $_SESSION['twitterCount'], "5");
        $_SESSION['twitterCount']++;
        header('Location: ' . Config::Get('web_path') . '/modules/twitter/twitter_login.php');
    } else {
        debug_event("Twitter", "Failed to auth too many times.  Giving up.", "5");
        header('Location: ' . Config::Get('web_path') );
    }
}

// TwitterOAuth instance, with two new parameters we got in twitter_login.php
$twitteroauth = new TwitterOAuth( Config::get('twitter_consumer_key'), Config::get('twitter_consumer_secret'), $_SESSION['oauth_token'], $_SESSION['oauth_token_secret']);
if( !isset($twitteroauth) ) {
    debug_event("Twitter", "Couldn't create OAuth object.", "5");
    header('Location: ' . Config::get('web_path'));
}
// Let's request the access token
$access_token = $twitteroauth->getAccessToken($_GET['oauth_verifier']);
if( !isset($access_token) ) {
    debug_event("Twitter", "Couldn't get access token", "5");
    header('Location: ' . Config::get('web_path'));
}
// Save it in a session var
$_SESSION['access_token'] = $access_token;


// Let's get the user's info
$user_info = $twitteroauth->get('account/verify_credentials');

debug_event("Twttier", "User ID:{$user_info->id}. ScreenName:{$user_info->screen_name}.", "5");
debug_event("Twitter", "access token:" . $access_token['oauth_token'], "5");
debug_event("Twitter", "access token secret:" .  $access_token['oauth_token_secret'], "5");

if( isset($user_info->error)) {
    debug_event("Twitter", "Error verifying credentials", "5");
    session_destroy();
    header('Location: ' . Config::get('web_path'));
}
else {
    
    // Let's find the user by its twitterid and ampacheid
    $idselectquery = "SELECT * FROM twitter_users WHERE oauth_provider = 'twitter' AND oauth_uid = ". $user_info->id . " AND ampache_id = " . $_SESSION['userdata']['uid'];
    debug_event("Twitter", "Id query: " . $idselectquery, "6");

    $idselectrun = Dba::read($idselectquery);
    $result = Dba::fetch_assoc($idselectrun);

    debug_event("Twitter", "ampache_id: {$_SESSION['userdata']['uid']}", "5");
    debug_event("Twitter", "oauth_uid: {$user_info->id}", "5");
    debug_event("Twitter", "oauth_token: {$access_token['oauth_token']}", "5");
    debug_event("Twitter", "oauth_secret: {$access_token['oauth_token_secret']}", "5");
    debug_event("Twitter", "username: {$user_info->screen_name}", "5");

    // If not, let's add it to the database
    if(empty($result)){
        debug_event("Twitter", "First time user.  Add them to the DB.", "5");
        $insert_query ="INSERT INTO twitter_users (ampache_id, oauth_provider, oauth_uid, oauth_token, oauth_secret, username) VALUES ( '{$_SESSION['userdata']['uid']}', 'twitter', '{$user_info->id}', '{$access_token['oauth_token']}', '{$access_token['oauth_token_secret']}', '{$user_info->screen_name}')";

        debug_event("Twitter", "Insert query: " . $insert_query, "6");
        $insert_run = Dba::write($insert_query);

        $select_query = "SELECT * FROM twitter_users WHERE username = '" . $user_info->screen_name . "' AND ampache_id = " . $_SESSION['userdata']['uid']; 
        debug_event("Twitter", "Select query: {$query}", "6");
                    $select_run = Dba::read( $select_query );
        $result = Dba::fetch_assoc($select_run);
    }
    else {
        debug_event("Twitter", "Update the DB to hold current tokens", "5");

        $update_query = "UPDATE twitter_users SET oauth_token = '{$access_token['oauth_token']}', oauth_secret = '{$access_token['oauth_token_secret']}' WHERE oauth_provider = 'twitter' AND oauth_uid = {$user_info->id} AND ampache_id = {$_SESSION['userdata']['uid']}";
        debug_event("Twitter", "update query: " . $update_query, "6");

        $update_run = Dba::write($update_query);

        $select_query = "SELECT * FROM twitter_users WHERE username = '" . $user_info->screen_name . "'";
        debug_event("Twitter", "select query: " . $select_query, "6");

        $select_run = Dba::read($select_query);
                    $result = Dba::fetch_assoc($select_run);
            }

        $_SESSION['id'] = $result['id'];
        $_SESSION['twitterusername'] = $result['username'];
        $_SESSION['oauth_uid'] = $result['oauth_uid'];
        $_SESSION['oauth_provider'] = $result['oauth_provider'];
        $_SESSION['oauth_token'] = $result['oauth_token'];
        $_SESSION['oauth_secret'] = $result['oauth_secret'];

    header('Location: ' . Config::get('web_path') . '/modules/twitter/twitter_update.php');
    }
?>
