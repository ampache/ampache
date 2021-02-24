<?php
if ($iframed || $is_share) { ?>
<link rel="stylesheet" href="<?php echo AmpConfig::get('web_path') . UI::find_template('jplayer.midnight.black-iframed.css') ?>" type="text/css" />
<?php
} else { ?>
<link rel="stylesheet" href="<?php echo AmpConfig::get('web_path') . UI::find_template('jplayer.midnight.black.css') ?>" type="text/css" />
<?php
    }

if (!$iframed) {
    require_once AmpConfig::get('prefix') . UI::find_template('stylesheets.inc.php'); ?>
<link rel="stylesheet" href="<?php echo AmpConfig::get('web_path') . UI::find_template('jquery-editdialog.css'); ?>" type="text/css" media="screen" />
<link rel="stylesheet" href="<?php echo AmpConfig::get('web_path'); ?>/modules/jquery-ui-ampache/jquery-ui.min.css" type="text/css" media="screen" />
<script src="<?php echo AmpConfig::get('web_path'); ?>/lib/components/jquery/jquery.min.js"></script>
<script src="<?php echo AmpConfig::get('web_path'); ?>/lib/components/jquery-ui/jquery-ui.min.js"></script>
<script src="<?php echo AmpConfig::get('web_path'); ?>/lib/vendor/needim/noty/js/noty/packaged/jquery.noty.packaged.min.js"></script>
<script src="<?php echo AmpConfig::get('web_path'); ?>/lib/components/jquery-cookie/jquery.cookie.js"></script>
<script src="<?php echo AmpConfig::get('web_path'); ?>/lib/javascript/base.js"></script>
<script src="<?php echo AmpConfig::get('web_path'); ?>/lib/javascript/ajax.js"></script>
<script src="<?php echo AmpConfig::get('web_path'); ?>/lib/javascript/tools.js"></script>
<script>
var jsAjaxServer = "<?php echo AmpConfig::get('ajax_server') ?>";
var jsAjaxUrl = "<?php echo AmpConfig::get('ajax_url') ?>";

function update_action()
{
    // Stub
}
</script>
<?php
} ?>
<link href="<?php echo AmpConfig::get('web_path'); ?>/modules/UberViz/style.css" rel="stylesheet" type="text/css">
<?php if (AmpConfig::get('webplayer_aurora')) { ?>
    <script src="<?php echo AmpConfig::get('web_path'); ?>/modules/aurora.js/aurora.js"></script>
<?php
} ?>
<script src="<?php echo AmpConfig::get('web_path'); ?>/lib/vendor/happyworm/jplayer/dist/jplayer/jquery.jplayer.min.js"></script>
<script src="<?php echo AmpConfig::get('web_path'); ?>/lib/javascript/jplayer.ext.js"></script>
<script src="<?php echo AmpConfig::get('web_path'); ?>/lib/vendor/happyworm/jplayer/dist/add-on/jplayer.playlist.min.js"></script>
<script src="<?php echo AmpConfig::get('web_path'); ?>/lib/javascript/jplayer.playlist.ext.js"></script>

<script>
var jplaylist = new Array();
var jtypes = new Array();

function convertMediaToJPMedia(media)
{
    var jpmedia = {};
    jpmedia['title'] = media['title'];
    jpmedia['artist'] = media['artist'];
    jpmedia[media['filetype']] = media['url'];
    jpmedia['poster'] = media['poster'];
    jpmedia['artist_id'] = media['artist_id'];
    jpmedia['album_id'] = media['album_id'];
    jpmedia['media_id'] = media['media_id'];
    jpmedia['media_type'] = media['media_type'];
    jpmedia['replaygain_track_gain'] = media['replaygain_track_gain'];
    jpmedia['replaygain_track_peak'] = media['replaygain_track_peak'];
    jpmedia['replaygain_album_gain'] = media['replaygain_album_gain'];
    jpmedia['replaygain_album_peak'] = media['replaygain_album_peak'];
    jpmedia['r128_track_gain'] = media['r128_track_gain'];
    jpmedia['r128_album_gain'] = media['r128_album_gain'];

    return jpmedia;
}

function addMedia(media)
{
    var jpmedia = convertMediaToJPMedia(media);
    jplaylist.add(jpmedia);
}

function playNext(media)
{
    var jpmedia = convertMediaToJPMedia(media);
    jplaylist.addAfter(jpmedia, jplaylist.current);
}
</script>
<script>
function ExitPlayer()
{
    $("#webplayer").text('');
    $("#webplayer").hide();

<?php
if (AmpConfig::get('song_page_title')) {
        echo "window.parent.document.title = '" . addslashes(AmpConfig::get('site_title')) . "';";
    } ?>
    document.onbeforeunload = null;
}

function TogglePlayerVisibility()
{
    if ($("#webplayer").is(":visible")) {
        $("#webplayer").slideUp();
    } else {
        $("#webplayer").slideDown();
    }
}

function TogglePlaylistExpand()
{
    if ($(".jp-playlist").css("opacity") !== '1') {
        $(".jp-playlist").css('top', '-255%');
        $(".jp-playlist").css('opacity', '1');
        $(".jp-playlist").css('height', '350%');
    } else {
        $(".jp-playlist").css('top', '0px');
        $(".jp-playlist").css('opacity', '0.9');
        $(".jp-playlist").css('height', '95%');
    }
}
</script>
<?php
if ($iframed) { ?>
<script>
function NotifyOfNewSong(title, artist, icon)
{
    if (artist === null) {
        artist = '';
    }
    if (!("Notification" in window)) {
        console.error("This browser does not support desktop notification");
    } else {
        if (Notification.permission !== 'denied') {
            if (Notification.permission === 'granted') {
                NotifyBrowser(title, artist, icon);
            } else {
                Notification.requestPermission(function (permission) {
                    if (!('permission' in Notification)) {
                        Notification.permission = permission;
                    }
                    NotifyBrowser(title, artist, icon);
                });
            }
        } else {
            console.error("Desktop notification denied.");
        }
    }
}

function NotifyBrowser(title, artist, icon)
{
    var notyTimeout = <?php echo AmpConfig::get('browser_notify_timeout'); ?>;
    var notification = new Notification(title, {
        body: artist,
        icon: icon
    });

    if (notyTimeout > 0) {
        setTimeout(function(){
            notification.close()
        }, notyTimeout * 1000);
    }
}

function NotifyOfNewArtist()
{
    refresh_slideshow();
}

function SwapSlideshow()
{
    swap_slideshow();
}

function initAudioContext()
{
    if (typeof AudioContext !== 'undefined') {
        audioContext = new AudioContext();
    } else if (typeof webkitAudioContext !== 'undefined') {
        audioContext = new webkitAudioContext();
    } else {
        audioContext = null;
    }
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
        $('#equalizerbtn').css('visibility', 'hidden');
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
                AudioHandler.loadMediaSource($('.jp-jplayer').find('audio').get(0));
            }
        }

        if (vizInitialized) {
            $('#uberviz').css('visibility', 'visible');
            $('#equalizerbtn').css('visibility', 'visible');
            vizPrevHeaderColor = $('#header').css('background-color');
            $('#header').css('background-color', 'transparent');
            vizPrevPlayerColor = $('#webplayer').css('background-color');
            $('#webplayer').css('cssText', 'background-color: #000 !important;');
            $('#webplayer').show();
            $("#webplayer-minimize").show();
            $('.jp-interface').css('background-color', '#000');
            $('.jp-playlist').css('background-color', '#000');
        } else {
            alert("<?php echo T_("Your browser doesn't support this feature."); ?>");
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
        alert('Full-Screen not supported by your browser');
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
    if (jplaylist['playlist'].length > 0) {
        var url = "<?php echo AmpConfig::get('ajax_url') ?>?page=playlist&action=append_item&item_type=" + jplaylist['playlist'][0]["media_type"] + "&item_id=";
        for (var i = 0; i < jplaylist['playlist'].length; i++) {
            url += "," + jplaylist['playlist'][i]["media_id"];
        }
        handlePlaylistAction(url, 'rb_append_dplaylist_new');
    }
}

function SaveToExistingPlaylist(event)
{
    if (jplaylist['playlist'].length > 0) {
        var item_ids = "";
        for (var i = 0; i < jplaylist['playlist'].length; i++) {
            item_ids += "," + jplaylist['playlist'][i]["media_id"];
        }
        showPlaylistDialog(event, jplaylist['playlist'][0]["media_type"], item_ids);
    }
}

var audioContext = null;
var mediaSource = null;
var replaygainEnabled = false;
var replaygainNode = null;
initAudioContext();

function ToggleReplayGain()
{
    if (replaygainNode === null) {
        var mediaElement = $('.jp-jplayer').find('audio').get(0);
        if (mediaElement) {
            if (audioContext !== null) {
                mediaSource = audioContext.createMediaElementSource(mediaElement);
                replaygainNode = audioContext.createGain();
                replaygainNode.gain.value = 1;
                mediaSource.connect(replaygainNode);
                replaygainNode.connect(audioContext.destination);
            }
        }
    }

    if (replaygainNode != null) {
        replaygainEnabled = !replaygainEnabled;
        document.cookie = 'replaygain=' + replaygainEnabled + ';samesite=lax';
        ApplyReplayGain();

        if (replaygainEnabled) {
            $('#replaygainbtn').css('box-shadow', '0px 1px 0px 0px orange');
        } else {
            $('#replaygainbtn').css('box-shadow', '');
        }
    }
}

function ApplyReplayGain()
{
    if (replaygainNode != null) {
        var gainlevel = 1;
        var replaygain = 0;
        var peakamplitude = 1;
        if (replaygainEnabled && currentjpitem != null) {
            var replaygain_track_gain   = currentjpitem.attr("data-replaygain_track_gain");
            var r128_track_gain = currentjpitem.attr("data-r128_track_gain"); 

            if (r128_track_gain !== 'null') {
                // R128 PREFERRED
                replaygain = parseInt(r128_track_gain / 256); // LU/dB away from baseline of -23 LUFS/dB, stored as Q7.8 (2 ^ 8) https://tools.ietf.org/html/rfc7845.html#page-25
                var referenceLevel = parseInt(-23); // LUFS https://en.wikipedia.org/wiki/EBU_R_128#Specification
                var targetLevel = parseInt(-18); // LUFS/dB;
                var masteredVolume = referenceLevel - replaygain;
                var difference = targetLevel - masteredVolume;

                gainlevel = (Math.pow(10, ((difference /* + Gpre-amp */) / 20)));
            } else if (replaygain_track_gain !== 'null') {
                // REPLAYGAIN FALLBACK
                replaygain = parseFloat(replaygain_track_gain);

                if (replaygain != null) {
                    var track_peak = currentjpitem.attr("data-replaygain_track_peak");
                    if (track_peak !== 'null') {
                        peakamplitude = parseFloat(track_peak);
                    }
                    gainlevel = Math.min(Math.pow(10, ((replaygain /* + Gpre-amp */) / 20)), (1 / peakamplitude));
                }
            }
        }

        replaygainNode.gain.value = gainlevel;
    }
}
</script>
<?php
} ?>
<script>
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
        if (brconn === null) {
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
<?php
    } ?>

var brkey = '';
var brconn = null;

function startBroadcast(key)
{
    brkey = key;

    listenBroadcast();
    brconn.onopen = function(e) {
        sendBroadcastMessage('AUTH_SID', '<?php echo session_id(); ?>');
        sendBroadcastMessage('REGISTER_BROADCAST', brkey);
        sendBroadcastMessage('SONG', currentjpitem.attr("data-media_id"));
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
        sendBroadcastMessage('AUTH_SID', '<?php echo Stream::get_session(); ?>');
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
                    alert('Unknown message code');
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
    if ($("#jquery_jplayer_1") !== undefined && $("#jquery_jplayer_1").data("jPlayer") !== undefined && !$("#jquery_jplayer_1").data("jPlayer").status.paused &&
            (document.activeElement === undefined || (document.activeElement.href.indexOf('/batch.php') < 0 && document.activeElement.href.indexOf('/stream.php') < 0))) {
        var message = '<?php echo T_('Media is currently playing, are you sure you want to close?') . ' ' . AmpConfig::get('site_title') . '?'; ?>';
        if (typeof evt == "undefined") {
            evt = window.event;
        }
        if (evt) {
            evt.returnValue = message;
        }
        return message;
    }

    return null;
}
<?php
    } ?>
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
<?php
    } ?>
</script>
