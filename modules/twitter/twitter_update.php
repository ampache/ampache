<?php
	require_once '../../lib/init.php';
	require_once( Config::get('prefix') . "/modules/twitter/twitteroauth/twitteroauth.php");
        session_start();
	

	if(!empty($_SESSION['twitterusername'])) {
		$link = mysql_connect( Config::get('database_hostname'), Config::get('database_username'), Config::get('database_password') );
		mysql_select_db( Config::get('database_name') , $link) or die("Couldnt connect " . mysql_error() );

		$nowplayingQuery = mysql_query("SELECT song.title,artist.name FROM song,now_playing,artist WHERE song.id = now_playing.object_id AND artist.id = song.artist");
		$nowplayingResults = mysql_fetch_array($nowplayingQuery) or die( mysql_error() );

		$return = $nowplayingResults['title'] . " by " . $nowplayingResults['name'];

		mysql_select_db('test', $link) or die("Couldnt connect " . mysql_error() );

		$query = mysql_query("SELECT * FROM users WHERE username = '" . $_SESSION['twitterusername'] . "'");
		$result = mysql_fetch_array($query) or die( mysql_error() );

		mysql_close($link);

		$twitteroauth = new TwitterOAuth( Config::get('twitter_consumer_key'), Config::get('twitter_consumer_secret'), $result['oauth_token'], $result['oauth_secret']);
		$user_info = $twitteroauth->get('account/verify');
		if( $user_info->error == 'Not found' ) {
			$twitteroauth->post('statuses/update', array('status' => 'is rocking out to ' . $return));
			echo "updated";
			header('Location: ' . Config::get('web_path') );
		}
		
		echo "Hello " . $result['username'];
		echo "<br> You are listening to " . $return;
		echo "<br>";
		print_r($user_info);
	} else {
		echo "sessionusername: " . $_SESSION['twitterusername'] . "<br>";
		echo 'borked';
	}
?>
