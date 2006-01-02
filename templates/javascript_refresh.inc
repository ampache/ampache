<script type="text/javascript" language="javascript">
<!-- Begin
// JS variables mpd_elapsed, mpd_song_length and mpd_state set in show_mpdplay.inc
// when a page/song is loaded, we get starttime and mpd_elapsed.
// mpd_elapsed is # of seconds elapsed in the now playing song when the page/song was refreshed

// Set refresh interval (in seconds)
var refreshinterval=<?php echo conf('refresh_limit'); ?>

// Display the countdown inside the status bar?
// Set "1" for yes or "0" for no
var displaycountdown=1

// main-code
var starttime
var nowtime
var reloadseconds=0
var secondssinceloaded=0
var mpd_notstoppause=1

function starttime() {
   starttime=new Date()
   starttime=starttime.getTime()
   if (mpd_state == "stop" || mpd_state == "pause") { mpd_notstoppause = 0; }
   countdown()
}

function fmt_time (timenum) {
var sec = Math.floor(timenum % 60);
/*var min = Math.floor(timenum / 60);
var fmted = min + ':' + ((sec < 10) ? "0" : "") + sec ;
return fmted;*/
return  Math.floor((1/60) * timenum) + ':' + ((sec < 10) ? '0' : '') + sec; 

}

function countdown() {
    nowtime= new Date()
    nowtime=nowtime.getTime()
    secondssinceloaded=(nowtime-starttime)/1000

    if (mpd_notstoppause) {
        reloadseconds = Math.round(mpd_song_length - mpd_elapsed - secondssinceloaded)
        }
    else
        { reloadseconds = Math.round(refreshinterval - secondssinceloaded) }

    if (displaycountdown==1) {
        window.status="Refreshing in "+reloadseconds+" seconds";
        if (mpd_notstoppause) {
            NodeList = document.getElementById ('mpd_cur_track_pos');
		{ NodeList.firstChild.data = fmt_time((mpd_elapsed) + (secondssinceloaded)); }
            NodeList = document.getElementById ('mpd_on_deck_in');
		{ NodeList.firstChild.data = fmt_time (mpd_song_length - mpd_elapsed - secondssinceloaded); }
            NodeList = document.getElementById ('mpd_pctplayed');
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

