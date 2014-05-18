<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 * Adapted for Ampache by Chris Slamar
 * FIXME: Adapted from what?  We shouldn't claim code that isn't ours
 *
 * LICENSE: GNU General Public License, version 2 (GPLv2)
 * Copyright 2001 - 2014 Ampache.org
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
require_once( AmpConfig::get('prefix') . "/modules/twitter/twitteroauth/twitteroauth.php");
session_start();

if( !isset($_SESSION['twitterCount'] )) {
    $_SESSION['twitterCount'] = 0;
}
if( isset($_SESSION['twitterusername']) ) {
    debug_event("Twitter", "User has logged in this session.", "5");
    header('Location: twitter_update.php');
} else {
    // The TwitterOAuth instance
    $twitteroauth = new TwitterOAuth( AmpConfig::get('twitter_consumer_key') , AmpConfig::get('twitter_consumer_secret') );

    // Requesting authentication tokens, the parameter is the URL we will be redirected to
    $request_token = $twitteroauth->getRequestToken( AmpConfig::get('web_path') . '/modules/twitter/twitter_works.php');

    // Saving them into the session
    $_SESSION['oauth_token'] = $request_token['oauth_token'];
    $_SESSION['oauth_token_secret'] = $request_token['oauth_token_secret'];

    // If everything goes well..
    if( $twitteroauth->http_code == 200 ) {
        // Let's generate the URL and redirect
        $url = $twitteroauth->getAuthorizeURL($request_token['oauth_token']);
        header('Location: '. $url);
    } else {
        debug_event("Twitter", "Could not generate the URL to continue.  Going back.", "5");
        header('Location: ' . AmpConfig::get('web_path') );
    }
}
?>
