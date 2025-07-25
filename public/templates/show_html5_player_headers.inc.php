<?php

use Ampache\Config\AmpConfig;
use Ampache\Module\Broadcast\Broadcast_Server;
use Ampache\Module\Playback\Stream;
use Ampache\Module\Util\AjaxUriRetrieverInterface;
use Ampache\Module\Util\Ui;

global $dic;

$web_path = AmpConfig::get_web_path();

$ajaxUriRetriever = $dic->get(AjaxUriRetrieverInterface::class);
$webplayer_debug  = (AmpConfig::get('webplayer_debug'))
    ? 'js'
    : 'min.js';
$cookie_string = (make_bool(AmpConfig::get('cookie_secure')))
    ? "path: '/', secure: true, samesite: 'Strict'"
    : "path: '/', samesite: 'Strict'";
$iframed   = $iframed ?? false;
$isShare   = $isShare ?? false;
$isLight   = (AmpConfig::get('theme_color', 'dark') == 'light');
$highlight = ($isLight)
    ? 'blue'
    : 'orange';
$jpinterface = ($isLight)
    ? '#f8f8f8'
    : '#191919';
$jpplaylist = ($isLight)
    ? '#d4d4d4'
    : '#202020';

if ($iframed || $isShare) { ?>
    <link rel="stylesheet" href="<?php echo $web_path . Ui::find_template('jplayer.midnight.black-iframed.css', true); ?>" type="text/css">
<?php } else { ?>
    <link rel="stylesheet" href="<?php echo $web_path . Ui::find_template('jplayer.midnight.black.css', true); ?>" type="text/css">
<?php } ?>
<?php if (!$iframed) { ?>
    <link rel="stylesheet" href="<?php echo $web_path . Ui::find_template('jquery-editdialog.css', true); ?>" type="text/css" media="screen">
    <link rel="stylesheet" href="<?php echo $web_path; ?>/lib/modules/jquery-ui-ampache/jquery-ui.min.css" type="text/css" media="screen">
    <script src="<?php echo $web_path; ?>/lib/components/jquery/jquery.min.js"></script>
    <script src="<?php echo $web_path; ?>/lib/components/jquery-ui/jquery-ui.min.js"></script>
    <script src="<?php echo $web_path; ?>/lib/components/js-cookie/js.cookie.js"></script>
    <script>
        var jsAjaxServer = "<?php echo $ajaxUriRetriever->getAjaxServerUri(); ?>";
        var jsAjaxUrl = "<?php echo $ajaxUriRetriever->getAjaxUri(); ?>";

        function update_action()
        {
            // Stub
        }
    </script>
    <?php  require_once Ui::find_template('stylesheets.inc.php');
} ?>
<link rel="stylesheet" href="<?php echo $web_path; ?>/lib/modules/UberViz/style.css" type="text/css">
<?php if (AmpConfig::get('webplayer_aurora')) { ?>
    <script src="<?php echo $web_path; ?>/lib/modules/aurora.js/aurora.js"></script>
<?php } ?>
<script src="<?php echo $web_path; ?>/lib/modules/jplayer/jquery.jplayer.<?php echo $webplayer_debug; ?>"></script>
<script src="<?php echo $web_path; ?>/lib/modules/jplayer/jplayer.playlist.<?php echo $webplayer_debug; ?>"></script>

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
        jpmedia['albumdisk_id'] = media['albumdisk_id'];
        jpmedia['album_name'] = media['album_name'];
        jpmedia['media_id'] = media['media_id'];
        jpmedia['media_type'] = media['media_type'];
        jpmedia['replaygain_track_gain'] = media['replaygain_track_gain'];
        jpmedia['replaygain_track_peak'] = media['replaygain_track_peak'];
        jpmedia['replaygain_album_gain'] = media['replaygain_album_gain'];
        jpmedia['replaygain_album_peak'] = media['replaygain_album_peak'];
        jpmedia['r128_track_gain'] = media['r128_track_gain'];
        jpmedia['r128_album_gain'] = media['r128_album_gain'];
        jpmedia['duration'] = media['duration'];

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

    function playlistLoop(bool)
    {
        jplaylist.toggleLoop(bool);
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
                icon: icon,
                silent: true
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
                $('#vizfullbtn').css('visibility', 'hidden');
                $('#equalizerbtn').css('visibility', 'hidden');
                $('#equalizer').css('visibility', 'hidden');
                $('#header').css('background-color', vizPrevHeaderColor);
                $('#webplayer').css('background-color', vizPrevPlayerColor);
                $('.jp-interface').css('background-color', '<?php echo $jpinterface; ?>');
                $('.jp-playlist').css('background-color', '<?php echo $jpplaylist; ?>');
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
                    $('#vizfullbtn').css('visibility', 'visible');
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
                    alert("<?php echo addslashes(T_("Your browser doesn't support this feature.")); ?>");
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
                alert("<?php echo addslashes(T_('Full-Screen not supported by your browser')); ?>");
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
                var url = "<?php echo $ajaxUriRetriever->getAjaxUri(); ?>?page=playlist&action=append_item&item_type=" + jplaylist['playlist'][0]['media_type'] + "&item_id=";
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
                    if (jplaylist['playlist'][0]['media_type'] === "song") {
                        if (item_ids === "") {
                            item_ids = jplaylist['playlist'][i]["media_id"];
                        } else {
                            item_ids += "," + jplaylist['playlist'][i]["media_id"];
                        }
                    }
                }
                if (item_ids !== "") {
                    showPlaylistDialog(event, 'song', item_ids);
                }
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
                Cookies.set('replaygain', replaygainEnabled, {<?php echo $cookie_string; ?>});
                ApplyReplayGain();

                if (replaygainEnabled) {
                    $('#replaygainbtn').css('box-shadow', '0px 1px 0px 0px <?php echo $highlight; ?>');
                } else {
                    $('#replaygainbtn').css('box-shadow', '');
                }
            }
        }

        var loopEnabled = false;

        function TogglePlaylistLoop()
        {
            if (loopEnabled === false) {
                playlistLoop(true);
                loopEnabled = true;
            } else {
                playlistLoop(false);
                loopEnabled = false;
            }

            if (loopEnabled) {
                $('#playlistloopbtn').css('box-shadow', '0px 1px 0px 0px <?php echo $highlight; ?>');
            } else {
                $('#playlistloopbtn').css('box-shadow', '');
            }
        }

        function ApplyReplayGain()
        {
            if (replaygainNode != null) {
                var gainlevel = 1;
                var replaygain = 0;
                var peakamplitude = 1;
                if (replaygainEnabled && currentjpitem != null) {
                    var replaygain_track_gain = currentjpitem.attr("data-replaygain_track_gain");
                    var r128_track_gain = currentjpitem.attr("data-r128_track_gain");

                    if (typeof r128_track_gain !== 'undefined' && r128_track_gain !== 'null') {
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

                        if (typeof replaygain_track_gain !== 'undefined' && replaygain != null) {
                            var track_peak = currentjpitem.attr("data-replaygain_track_peak");
                            if (typeof track_peak !== 'undefined' && track_peak !== 'null') {
                                peakamplitude = parseFloat(track_peak);
                            }
                            gainlevel = Math.min(Math.pow(10, ((replaygain /* + Gpre-amp */) / 20)), (1 / peakamplitude));
                        }
                    }
                }
                if (Number.isFinite(gainlevel)) {
                    replaygainNode.gain.value = gainlevel;
                }
            }
        }
    </script>
<?php } ?>
<script>
    <?php if (AmpConfig::get('waveform') && !$isShare) { ?>
    var wavclicktimer = null;
    var shouts = {};
    function WaveformClick(songid, time)
    {
        // Double click
        if (wavclicktimer != null) {
            clearTimeout(wavclicktimer);
            wavclicktimer = null;
            NavigateTo('<?php echo $web_path; ?>/shout.php?action=show_add_shout&type=song&id=' + songid + '&offset=' + time);
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

    <?php if ($iframed && AmpConfig::get('webplayer_confirmclose') && !$isShare) { ?>
    window.parent.onbeforeunload = function (evt) {
        if (typeof $("#jquery_jplayer_1") !== 'undefined' && typeof $("#jquery_jplayer_1").data("jPlayer") !== 'undefined' && !$("#jquery_jplayer_1").data("jPlayer").status.paused &&
            (typeof document.activeElement === 'undefined' || (typeof document.activeElement.href !== 'undefined' && document.activeElement.href.indexOf('/batch.php') < 0 && document.activeElement.href.indexOf('/stream.php') < 0))) {
            var message = '<?php echo addslashes(T_('Media is currently playing, are you sure you want to close?')) . ' ' . AmpConfig::get('site_title') . '?'; ?>';
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
    <?php } ?>
    <?php if ($iframed && AmpConfig::get('webplayer_pausetabs') && !$isShare) { ?>
    window.addEventListener('storage', function (event) {
        if (event.key == 'ampache-current-webplayer') {
            // The latest used webplayer is not this player, pause song if playing
            if (typeof jpuqid === 'undefined' || (typeof jpuqid !== 'undefined' && event.newValue != jpuqid)) {
                if (typeof $("#jquery_jplayer_1").data("jPlayer") !== 'undefined' && !$("#jquery_jplayer_1").data("jPlayer").status.paused) {
                    $("#jquery_jplayer_1").data("jPlayer").pause();
                }
            }
        }
    });
    <?php } ?>
</script>
