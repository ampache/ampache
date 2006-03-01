<script type="text/javascript" language="javascript">
<!-- Begin
// when a page/song is loaded, we get starttime and mpd_elapsed.
// mpd_elapsed is # of seconds elapsed in the now playing song when the page/song was refreshed
// secondssinceloaded is the calculated number of seconds since mpd_elapsed was set (less some load & execute lag).

// Set refresh interval (in seconds)
var refreshinterval=<?php echo conf('refresh_limit'); ?>;
var web_path = "<?php echo conf('web_path');?>";

<?php 
if ($user->prefs['play_type'] == 'mpd') {
	echo 'var player = "mpd"' .
		'; var mpd_elapsed = '. $myMpd->current_track_position .
		'; var mpd_song_length = '. $myMpd->current_track_length .
		'; var mpd_songid = '.$myMpd->current_track_id.
		'; var mpdpl_first = 0' .
// $myMpd->current_track_id-1 .
		'; var player_state = "'. $myMpd->state .'";';
} else {
	echo 'var player = "'. $user->prefs['play_type'] .
		'"; var player_state = "";';
}
?>


// Display the countdown inside the status bar?
// Set "1" for yes or "0" for no
var displaycountdown=1

// main-code
var starttime
var nowtime
var reloadseconds=0
var secondssinceloaded=0
//var mpd_notstoppause=1

function starttime() {
   starttime=new Date()
   starttime=starttime.getTime()
//   if (player_state == "stop" || player_state == "pause") { mpd_notstoppause = 0; }
   countdown()
}

function fmt_time (timenum) {
var sec = Math.floor(timenum % 60);
return  Math.floor((1/60) * timenum) + ':' + ((sec < 10) ? '0' : '') + sec; 

}

function countdown() {
    nowtime= new Date()
    nowtime=nowtime.getTime()
    secondssinceloaded=(nowtime-starttime)/1000

    if (player_state == 'play') {
        reloadseconds = Math.round(mpd_song_length - mpd_elapsed - secondssinceloaded)
        }
    else
        { reloadseconds = Math.round(refreshinterval - secondssinceloaded) }

    if (displaycountdown==1) {
        window.status="Refreshing in "+reloadseconds+" seconds";
        if ((player == 'mpd') && (player_state == 'play')) {
            if (NodeList = document.getElementById ('mpd_cur_track_pos'))
		{ NodeList.firstChild.data = fmt_time((mpd_elapsed) + (secondssinceloaded)); }
            if (NodeList = document.getElementById ('mpd_on_deck_in'))
		{ NodeList.firstChild.data = fmt_time (mpd_song_length - mpd_elapsed - secondssinceloaded); }
            if (NodeList = document.getElementById ('mpd_pctplayed'))
		{ NodeList.firstChild.data = Math.floor (100*(mpd_elapsed + secondssinceloaded)/mpd_song_length); }
	    }
	if (reloadseconds > 0) {
            var timer=setTimeout("countdown()",1000)
        }
	else { 
            clearTimeout(timer)
            if (true) { /* rig it for AJAX for now; later replace with conf('AJAX') */
               startRequest('action=now_playing');
               starttime = new Date();
               starttime=starttime.getTime()
               var timer=setTimeout("countdown()",1000)
            }
            else {
               window.location.reload(true)
            }
        } //if reloadseconds > 0
    } // if displaycountdown
}
 
// start with page-load
window.onload=starttime
// End -->
</script>

