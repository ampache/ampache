<?php
	require_once '../../lib/init.php';
	require_once( Config::get('prefix') . "/modules/twitter/twitteroauth/twitteroauth.php");
	session_start();

	if( isset($_SESSION['twitterusername']) ) {
		header('Location: twitter_update.php');
	} else {
		// The TwitterOAuth instance
		$twitteroauth = new TwitterOAuth( Config::get('twitter_consumer_key') , Config::get('twitter_consumer_secret') );

		// Requesting authentication tokens, the parameter is the URL we will be redirected to
		$request_token = $twitteroauth->getRequestToken( Config::get('web_path') . '/modules/twitter/twitter_works.php');

		// Saving them into the session
		$_SESSION['oauth_token'] = $request_token['oauth_token'];
		$_SESSION['oauth_token_secret'] = $request_token['oauth_token_secret'];

		// If everything goes well..
		if( $twitteroauth->http_code == 200 ) {
			// Let's generate the URL and redirect
			$url = $twitteroauth->getAuthorizeURL($request_token['oauth_token']);
			header('Location: '. $url);
			echo "ok";
		} else {
			die('Something wrong happened.');
		}
	}
?>
