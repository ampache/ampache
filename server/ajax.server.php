<?php
header('Content-Type: text/xml'); 
header('Cache-control: no-cache'); 
header('Pragma: no-cache'); 
echo '<?xml version="1.0" encoding="UTF-8"?>';

$no_session = true;
include ('../modules/init.php'); 
$myMpd = init_mpd();

$action = $_GET['action'];
$player = $_GET['player'];
$result = '';


function mpderr() { global $result, $myMpd; 
   if ($GLOBALS['player'] == 'mpd')
 { $result = $result . '<error>'.$myMpd->errStr.'</error>'; } }
function volume() { global $result, $myMpd; 
   if ($GLOBALS['player'] == 'mpd')
 { $result = $result . '<volume>'. $myMpd->volume. '</volume>'; } }
function state() { global $result, $myMpd; 
   if ($GLOBALS['player'] == 'mpd')
 { $result = $result. '<state>'. $myMpd->state. '</state>'; } }

function mpd_cur_track_pos () {
   global $result, $myMpd;
   if ($GLOBALS['player'] == 'mpd') {
   $result = $result . '<mpd_cur_track_pos>'.$myMpd->current_track_position.'</mpd_cur_track_pos>';
   }
}

function now_playing() {
global $result, $myMpd;
   if ($GLOBALS['player'] == 'mpd') {
      if (!$myMpd->playlist[($myMpd->current_track_id)]['Title']) {
         list($tmp, $id, $tmp) = preg_split("/(song=|&)/", $myMpd->playlist[($myMpd->current_track_id)]['file']);
         $r = new Song($id);
         $myMpd->playlist[($myMpd->current_track_id)]['Title'] = $r->title;
         $myMpd->playlist[($myMpd->current_track_id)]['Artist'] = $r->get_artist_name();
         $myMpd->playlist[($myMpd->current_track_id)]['Album'] = $r->get_album_name();
      }
      $result = $result.'<now_playing>'.
                '<songid>'.$myMpd->current_track_id.'</songid>'.
                '<songtitle>'.$myMpd->playlist[$myMpd->current_track_id]['Title'].'</songtitle>'.
                '<songartist>'.$myMpd->playlist[$myMpd->current_track_id]['Artist'].'</songartist>'.
                '<songalbum>'.$myMpd->playlist[$myMpd->current_track_id]['Album'].'</songalbum>'.
                '<songlength>'.$myMpd->playlist[($myMpd->current_track_id)]['Time'].'</songlength>'.
                '</now_playing>';
   } //end if player == mpd
now_playing_display();
}


function now_playing_display() {

  global $result;
  $dbh = dbh();
  $results = get_now_playing();
	    $result = $result.'<now_playing_display>';
  
  if (count($results)) {
  
    foreach($results as $item) { 
  
	  $song = $item['song'];
	  $np_user = $item['user'];
	
	  if (is_object($song)) {
		
		$result = $result.'<song>';
	  
	    if (!$np_user->fullname) { $np_user->fullname = "Unknown User"; }
	  
	    if (conf('use_auth')) { 
		  $result = $result.'<fullname>'.$np_user->fullname.'</fullname>';
	    } else {
		  $result = $result.'<fullname></fullname>';
	    }
		
		$result = $result.'<songid>'.$song->id.'</songid>';
		$result = $result.'<albumid>'.$song->album.'</albumid>';
		$result = $result.'<artistid>'.$song->artist.'</artistid>';
		$result = $result.'<songtitle>'.htmlspecialchars($song->f_title).'</songtitle>';
		$result = $result.'<songartist>'.htmlspecialchars($song->f_artist).'</songartist>';
		$result = $result.'<songalbum>'.htmlspecialchars($song->f_album).'</songalbum>';
		 
		$result = $result.'</song>';
	  
      } // if it's a song
    } // foreach song
	
  } // if now playing
	    $result = $result.'</now_playing_display>';
  
}


/**********************
The below handles a request for action on the mpd player and/or the return of mpd
player state information.

It is grossly inefficient because everytime there is a request it loads init.php and does a full 
instantiation of myMpd.  Would be much faster if it only loaded limited info to start, then 
just grabbed what it needed.  (Prolly tougher to maintain abstraction.)
**********************/

/*if (!$user->has_access(25)) { echo '<error>Inadequate access privileges!</error>'; return; }*/

switch ($action) {
case 'getvol' : 
	$result = '<volume>'. $myMpd->volume. '</volume>';
	break;
case 'setvol' : 
	if ( is_null($myMpd->SetVolume($_GET['param1'])) ) $result = '<error>'.$myMpd->errStr.'</error>';
	$result = $result.'<volume>'. $myMpd->volume. '</volume>';
	break;
case 'adjvol' :
        if ( is_null($myMpd->AdjustVolume($_GET['param1'])) ) $result = '<error>'.$myMpd->errStr.'</error>';
        volume();
        break;
    case ' > ':
    case "play":
      if ( is_null($myMpd->Play()) ) $result = '<error>'.$myMpd->errStr.'</error>\n';
        mpd_cur_track_pos();
        state();
        now_playing();
        break;
    case "stop":
    case ' X ':
      if ( is_null($myMpd->Stop()) ) $result = '<error>'.$myMpd->errStr.'</error>\n';
        $result = $result.'<state>'. $myMpd->state. '</state>';
        now_playing();
        break;
    case ' | | ':
    case ' = ':
    case "pause":
      if ( is_null($myMpd->Pause()) ) echo "ERROR: " .$myMpd->errStr."\n";
        mpd_cur_track_pos();
        state();
        break;
    case '|< ':
    case "Prev":
      if ( is_null($myMpd->Previous()) ) echo "ERROR: " . $myMpd->errStr."\n";
        mpd_cur_track_pos();
        state();
        now_playing();
        break;
    case ' >|';
    case "Next":
      if ( is_null($myMpd->Next()) ) echo "ERROR: " . $myMpd->errStr."\n";
        mpd_cur_track_pos();
        state();
        now_playing();
        break;
    case 'now_playing' :
        mpd_cur_track_pos();
        state();
        now_playing();
//	    now_playing_display();
        break;


} //end switch


echo '<properties>' .
	'<action>' . $action .$player.'</action>' .
	$result .
'</properties>';
?>
