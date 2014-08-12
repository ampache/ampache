<?php
if ($iframed) {
?>
<link rel="stylesheet" href="<?php echo AmpConfig::get('web_path'); ?>/templates/jplayer.midnight.black-iframed.css" type="text/css" />
<?php
} else {
?>
<link rel="stylesheet" href="<?php echo AmpConfig::get('web_path'); ?>/templates/jplayer.midnight.black.css" type="text/css" />
<?php
require_once AmpConfig::get('prefix') . '/templates/stylesheets.inc.php';
?>
<link rel="stylesheet" href="<?php echo AmpConfig::get('web_path'); ?>/templates/jquery-editdialog.css" type="text/css" media="screen" />
<link rel="stylesheet" href="<?php echo AmpConfig::get('web_path'); ?>/modules/jquery-ui/jquery-ui.min.css" type="text/css" media="screen" />
<script src="<?php echo AmpConfig::get('web_path'); ?>/modules/jquery/jquery.min.js" language="javascript" type="text/javascript"></script>
<script src="<?php echo AmpConfig::get('web_path'); ?>/modules/jquery-ui/jquery-ui.min.js" language="javascript" type="text/javascript"></script>
<script src="<?php echo AmpConfig::get('web_path'); ?>/modules/noty/packaged/jquery.noty.packaged.min.js" language="javascript" type="text/javascript"></script>
<script src="<?php echo AmpConfig::get('web_path'); ?>/modules/jquery/jquery.cookie.js" language="javascript" type="text/javascript"></script>
<script src="<?php echo AmpConfig::get('web_path'); ?>/lib/javascript/base.js" language="javascript" type="text/javascript"></script>
<script src="<?php echo AmpConfig::get('web_path'); ?>/lib/javascript/ajax.js" language="javascript" type="text/javascript"></script>
<script src="<?php echo AmpConfig::get('web_path'); ?>/lib/javascript/tools.js" language="javascript" type="text/javascript"></script>
<script type="text/javascript" charset="utf-8">
var jsAjaxServer = "<?php echo AmpConfig::get('ajax_server') ?>";
var jsAjaxUrl = "<?php echo AmpConfig::get('ajax_url') ?>";

function update_action()
{
    // Stub
}
</script>
<?php
}
?>
<link href="<?php echo AmpConfig::get('web_path'); ?>/modules/UberViz/style.css" rel="stylesheet" type="text/css">
<script src="<?php echo AmpConfig::get('web_path'); ?>/modules/jquery-jplayer/jquery.jplayer.min.js" language="javascript" type="text/javascript"></script>
<script src="<?php echo AmpConfig::get('web_path'); ?>/modules/jquery-jplayer/jplayer.playlist.min.js" language="javascript" type="text/javascript"></script>
<script src="<?php echo AmpConfig::get('web_path'); ?>/modules/jquery-jplayer/jplayer.playlist.ext.js" language="javascript" type="text/javascript"></script>

<script language="javascript" type="text/javascript">
var jplaylist = new Array();
var jtypes = new Array();

function addMedia(media)
{
    var jpmedia = {};
    jpmedia['title'] = media['title'];
    jpmedia['artist'] = media['artist'];
    jpmedia[media['filetype']] = media['url'];
    jpmedia['poster'] = media['poster'];
    jpmedia['artist_id'] = media['artist_id'];
    jpmedia['album_id'] = media['album_id'];
    jpmedia['song_id'] = media['song_id'];

    jplaylist.add(jpmedia);
}
</script>
<script language="javascript" type="text/javascript">
function ExitPlayer()
{
    $("#webplayer").text('');
    $("#webplayer").hide();

<?php
if (AmpConfig::get('song_page_title')) {
    echo "window.parent.document.title = '" . addslashes(AmpConfig::get('site_title')) . "';";
}
?>
    document.onbeforeunload = null;
}
</script>
<?php
if ($iframed) {
?>
<script type="text/javascript">
function NavigateTo(url)
{
    window.location.hash = url.substring(jsWebPath.length + 1);
}

function NotifyOfNewSong()
{
    refresh_slideshow();
}

function SwapSlideshow()
{
    swap_slideshow();
}

function isVisualizerEnabled()
{
    return ($('#uberviz').css('visibility') == 'visible');
}

var vizInitialized = false;
var vizPrevHeaderColor = "#000";
var vizPrevPlayerColor = "#000";
function ShowVisualizer()
{
    if (isVisualizerEnabled()) {
        $('#uberviz').css('visibility', 'hidden');
        $('#equalizer').css('visibility', 'hidden');
        $('#header').css('background-color', vizPrevHeaderColor);
        $('#webplayer').css('background-color', vizPrevPlayerColor);
        $('.jp-interface').css('background-color', 'rgb(25, 25, 25)');
        $('.jp-playlist').css('background-color', 'rgb(20, 20, 20)');
    } else {
        // Resource not yet initialized? Do it.
        if (!vizInitialized) {
            if ((typeof AudioContext !== 'undefined') || (typeof webkitAudioContext !== 'undefined')) {
                UberVizMain.init();
                vizInitialized = true;
                AudioHandler.loadMediaSource(document.getElementById("jp_audio_0"));
            }
        }

        if (vizInitialized) {
            $('#uberviz').css('visibility', 'visible');
            vizPrevHeaderColor = $('#header').css('background-color');
            $('#header').css('background-color', 'transparent');
            vizPrevPlayerColor = $('#webplayer').css('background-color');
            $('#webplayer').css('cssText', 'background-color: #000 !important;');
            $('#webplayer').show();
            $('.jp-interface').css('background-color', '#000');
            $('.jp-playlist').css('background-color', '#000');
        } else {
            alert("<?php echo T_('Your browser doesn\'t support this feature.'); ?>");
        }
    }
}

function ShowVisualizerFullScreen()
{
    if (!isVisualizerEnabled()) {
        ShowVisualizer();
    }

    var element = document.getElementById("viz");
    if (element.requestFullScreen) {
        element.requestFullScreen();
    } else if (element.webkitRequestFullScreen) {
        element.webkitRequestFullScreen(Element.ALLOW_KEYBOARD_INPUT);
    } else if (element.mozRequestFullScreen) {
        element.mozRequestFullScreen();
    } else {
        alert('Full-Screen not supported by your browser.');
    }
}

function ShowEqualizer()
{
    if (isVisualizerEnabled()) {
        if ($('#equalizer').css('visibility') == 'visible') {
            $('#equalizer').css('visibility', 'hidden');
        } else {
            $('#equalizer').css('visibility', 'visible');
        }
    }
}

function SavePlaylist()
{
    var url = "<?php echo AmpConfig::get('ajax_url'); ?>?page=playlist&action=append_item&item_type=song&item_id=";
    for (var i = 0; i < jplaylist['playlist'].length; i++) {
        url += "," + jplaylist['playlist'][i]["song_id"];
    }
    handlePlaylistAction(url, 'rb_append_dplaylist_new');
}
</script>
<?php
}
?>
<script type="text/javascript">
<?php if (AmpConfig::get('waveform') && !$is_share) { ?>
var wavclicktimer = null;
var shouts = {};
function WaveformClick(songid, time)
{
    // Double click
    if (wavclicktimer != null) {
        clearTimeout(wavclicktimer);
        wavclicktimer = null;
        NavigateTo('<?php echo AmpConfig::get('web_path') ?>/shout.php?action=show_add_shout&type=song&id=' + songid + '&offset=' + time);
    } else {
        // Single click
        if (brconn == null) {
            wavclicktimer = setTimeout(function() {
                wavclicktimer = null;
                $("#jquery_jplayer_1").data("jPlayer").play(time);
            }, 250);
        }
    }
}

function ClickTimeOffset(e)
{
    var parrentOffset = $(".waveform").offset().left;
    var offset = e.pageX - parrentOffset;
    var duration = $("#jquery_jplayer_1").data("jPlayer").status.duration;
    var time = duration * (offset / 400);

    return time;
}

function ShowWaveform()
{
    $('.waveform').css('visibility', 'visible');
}

function HideWaveform()
{
    $('.waveform').css('visibility', 'hidden');
}
<?php } ?>

var brkey = '';
var brconn = null;

function startBroadcast(key)
{
    brkey = key;

    listenBroadcast();
    brconn.onopen = function(e) {
        sendBroadcastMessage('AUTH_SID', '<?php echo session_id(); ?>');
        sendBroadcastMessage('REGISTER_BROADCAST', brkey);
        sendBroadcastMessage('SONG', currentjpitem.attr("data-song_id"));
    };
}

function startBroadcastListening(broadcast_id)
{
    listenBroadcast();

    // Hide few UI information on listening mode
    $('.jp-previous').css('visibility', 'hidden');
    $('.jp-play').css('visibility', 'hidden');
    $('.jp-pause').css('visibility', 'hidden');
    $('.jp-next').css('visibility', 'hidden');
    $('.jp-stop').css('visibility', 'hidden');
    $('.jp-toggles').css('visibility', 'hidden');
    $('.jp-playlist').css('visibility', 'hidden');
    $('#broadcast').css('visibility', 'hidden');

    $('.jp-seek-bar').css('pointer-events', 'none');

    brconn.onopen = function(e) {
        sendBroadcastMessage('AUTH_SID', '<?php echo Stream::$session; ?>');
        sendBroadcastMessage('REGISTER_LISTENER', broadcast_id);
    };
}

function listenBroadcast()
{
    if (brconn != null) {
        stopBroadcast();
    }

    brconn = new WebSocket('<?php echo Broadcast_Server::get_address(); ?>');
    brconn.onmessage = receiveBroadcastMessage;
}

var brLoadingSong = false;
var brBufferingSongPos = -1;

function receiveBroadcastMessage(e)
{
    var jp = $("#jquery_jplayer_1").data("jPlayer");
    var msgs = e.data.split(';');

    for (var i = 0; i < msgs.length; ++i) {
        var msg = msgs[i].split(':');
        if (msg.length == 2) {
            switch (msg[0]) {
                case 'PLAYER_PLAY':
                    if (msg[1] == '1') {
                        if (jp.status.paused) {
                            jp.play();
                        }
                    } else {
                        if (!jp.status.paused) {
                            jp.pause();
                        }
                    }
                break;

                case 'SONG':
                    addMedia($.parseJSON(atob(msg[1])));
                    brLoadingSong = true;
                    // Buffering song position in case it is asked in the next sec.
                    // Otherwise we will move forward on the previous song instead of the new currently loading one
                    setTimeout(function() {
                        if (brBufferingSongPos > -1) {
                            jp.play(brBufferingSongPos);
                            brBufferingSongPos = -1;
                        }
                        brLoadingSong = false;
                    }, 1000);
                    jplaylist.next();
                break;

                case 'SONG_POSITION':
                    if (brLoadingSong) {
                        brBufferingSongPos = parseFloat(msg[1]);
                    } else {
                        jp.play(parseFloat(msg[1]));
                    }
                break;

                case 'NB_LISTENERS':
                    $('#broadcast_listeners').html(msg[1]);
                break;

                case 'INFO':
                    // Display information notification to user here
                break;

                case 'ENDED':
                    jp.stop();
                break;

                default:
                    alert('Unknown message code.');
                break;
            }
        }
    }
}

function sendBroadcastMessage(cmd, value)
{
    if (brconn != null && brconn.readyState == 1) {
        var msg = cmd + ':' + value + ';';
        brconn.send(msg);
    }
}

function stopBroadcast()
{
    brkey = '';
    if (brconn != null && brconn.readyState == 1) {
        brconn.close();
    }
    brconn = null;
}

<?php if ($iframed && AmpConfig::get('webplayer_confirmclose') && !$is_share) { ?>
window.parent.onbeforeunload = function (evt) {
    if (!$("#jquery_jplayer_1").data("jPlayer").status.paused) {
        var message = '<?php echo T_('Media is currently playing. Are you sure you want to close') . ' ' . AmpConfig::get('site_title') . '?'; ?>';
        if (typeof evt == 'undefined') {
            evt = window.event;
        }
        if (evt) {
            evt.returnValue = message;
        }
        return message;
    }

    return null;
}
<?php } ?>
<?php if ($iframed && AmpConfig::get('webplayer_confirmclose') && !$is_share) { ?>
window.addEventListener('storage', function (event) {
  if (event.key == 'ampache-current-webplayer') {
    // The latest used webplayer is not this player, pause song if playing
    if (event.newValue != jpuqid) {
        if (!$("#jquery_jplayer_1").data("jPlayer").status.paused) {
            $("#jquery_jplayer_1").data("jPlayer").pause();
        }
    }
  }
});
<?php } ?>
</script>
