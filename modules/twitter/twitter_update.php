<?php
	require_once '../../lib/init.php';
	require_once( Config::get('prefix') . "/modules/twitter/twitteroauth/twitteroauth.php");
        session_start();
	

	if(!empty($_SESSION['twitterusername'])) {
		$link = mysql_connect( Config::get('database_hostname'), Config::get('database_username'), Config::get('database_password') ) or die( mysql_error() );;
		mysql_select_db( Config::get('database_name') , $link) or die("Couldnt connect " . mysql_error() );

		$nowplayingQuery = "SELECT song.title,artist.name FROM song,now_playing,artist WHERE song.id = now_playing.object_id AND artist.id = song.artist";
		debug_event("Twitter", "Now Playing query: " . $nowplayingQuery, "6");
		$nowplayingRun = mysql_query($nowplayingQuery) or die( mysql_error);
		$nowplayingResults = mysql_fetch_array($nowplayingRun) or die( mysql_error() );

		$return = $nowplayingResults['title'] . " by " . $nowplayingResults['name'];
		debug_event("Twitter", "Song from DB is: " . $return, "5");

		$selectquery = "SELECT * FROM twitter_users WHERE username = '" . $_SESSION['twitterusername'] . "' AND ampache_id = " . $_SESSION['userdata']['uid'];
		debug_event("Twitter", "Select query: " . $selectquery, "6");
		$selectrun = mysql_query($selectquery) or die( mysql_error() );

		$result = mysql_fetch_array($selectrun) or die( mysql_error() );

		mysql_close($link);

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
