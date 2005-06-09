<script language="JavaScript">
// Set refresh interval (in seconds)
var refreshinterval=<?= conf('refresh_limit'); ?>

// Display the countdown inside the status bar?
// Set "1" for yes or "0" for no
var displaycountdown=0

// main-code
var starttime
var nowtime
var reloadseconds=0
var secondssinceloaded=0

function starttime() {
        starttime=new Date()
        starttime=starttime.getTime()
        countdown()
}

function countdown() {
        nowtime= new Date()
        nowtime=nowtime.getTime()
        secondssinceloaded=(nowtime-starttime)/1000

reloadseconds=Math.round(refreshinterval-secondssinceloaded)
        if (refreshinterval>=secondssinceloaded) {
                var timer=setTimeout("countdown()",1000)
                if (displaycountdown=="1") {
                        window.status="Page refreshing in "+reloadseconds+
" seconds"
                }
        } else { 
                clearTimeout(timer)
                window.location.reload(true)
        }                                   
}
 
// start with page-load
window.onload=starttime
</script>
