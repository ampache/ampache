<?php

// show_html5_player_headers.inc.php

use Ampache\Config\AmpConfig;
use Ampache\Module\Broadcast\Broadcast_Server;
use Ampache\Module\Playback\Stream;
use Ampache\Module\Util\AjaxUriRetrieverInterface;
use Ampache\Module\Util\Ui;

global $dic;

$web_path        = AmpConfig::get_web_path();
$ampache_version = AmpConfig::get('version');
// Cache-bust the skin CSS by file mtime so edits load immediately (falls back to app version).
$cssBust = static fn(string $file): string => is_file(__DIR__ . '/' . $file) ? (string) filemtime(__DIR__ . '/' . $file) : $ampache_version;

$ajaxUriRetriever = $dic->get(AjaxUriRetrieverInterface::class);
$cookie_string    = (make_bool(AmpConfig::get('cookie_secure')))
    ? "path: '/', secure: true, samesite: 'Strict'"
    : "path: '/', samesite: 'Strict'";
$iframed   = $iframed ?? false;
$isShare   = $isShare ?? false;
$isLight   = (AmpConfig::get('theme_color', 'dark') == 'light');
$highlight = ($isLight)
    ? 'blue'
    : 'orange';

if ($iframed) { ?>
    <link rel="stylesheet" href="<?php echo $web_path . Ui::find_template('jplayer.midnight.black-iframed.css', true) . '?v=' . $cssBust('jplayer.midnight.black-iframed.css'); ?>" type="text/css">
<?php } else { ?>
    <link rel="stylesheet" href="<?php echo $web_path . Ui::find_template('jplayer.midnight.black.css', true) . '?v=' . $cssBust('jplayer.midnight.black.css'); ?>" type="text/css">
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
<script>
    window.jpDebug = <?php echo (AmpConfig::get('webplayer_debug')) ? 'true' : 'false'; ?>;
</script>
<script src="<?php echo $web_path; ?>/lib/modules/jplayer/jquery.jplayer.min.js"></script>
<script src="<?php echo $web_path; ?>/lib/modules/jplayer/jplayer.playlist.min.js"></script>

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
        jpmedia['remote'] = media['remote'];

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
        $('body').addClass('webplayer-hidden');

        <?php
        if (AmpConfig::get('song_page_title')) {
            echo "window.parent.document.title = '" . addslashes(AmpConfig::get('site_title')) . "';";
        } ?>
        document.onbeforeunload = null;
    }

    function TogglePlayerVisibility()
    {
        if ($("#webplayer").is(":visible")) {
            // Player hidden: let the visualizer (if active) cover the full screen
            // instead of leaving a gap where the player was.
            $("#webplayer").slideUp(function () {
                $('body').addClass('webplayer-hidden');
            });
        } else {
            $('body').removeClass('webplayer-hidden');
            $("#webplayer").slideDown();
        }
    }

    function TogglePlaylistShow()
    {
        // Show/hide the in-bar playlist (separate from the expand side panel).
        var container = $('#jp_container_1');
        var collapsed = container.toggleClass('jp-playlist-collapsed').hasClass('jp-playlist-collapsed');
        if (collapsed) {
            // hiding the playlist also closes the expanded side panel
            container.removeClass('jp-playlist-expanded');
            Cookies.set('jp_playlist_expanded', 'false', {expires: 7, <?php echo $cookie_string; ?>});
        }
        Cookies.set('jp_playlist_collapsed', collapsed, {expires: 7, <?php echo $cookie_string; ?>});
        updatePlaylistControls();
    }

    function TogglePlaylistExpand()
    {
        // Expand the visible playlist into a tall side panel above the player bar.
        var expanded = $('#jp_container_1').toggleClass('jp-playlist-expanded').hasClass('jp-playlist-expanded');
        Cookies.set('jp_playlist_expanded', expanded, {expires: 7, <?php echo $cookie_string; ?>});
    }

    function updatePlaylistControls()
    {
        // Expand stays available even while the in-bar playlist is hidden: the
        // expanded side panel out-cascades .jp-playlist-collapsed, so it can pop
        // out over a hidden playlist and closing it returns to the hidden state.
        $('#expandplaylistbtn').css('visibility', 'visible');
    }

    function ToggleNowPlaying()
    {
        // .playing_info lives outside #jp_container_1, so toggle on <body>.
        var hidden = $('body').toggleClass('jp-nowplaying-hidden').hasClass('jp-nowplaying-hidden');
        Cookies.set('jp_nowplaying_hidden', hidden, {expires: 7, <?php echo $cookie_string; ?>});
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
            return ($('#visualizer').css('visibility') == 'visible');
        }

        var vizRAF = null;
        var vizTick = 0;
        var vizBassPrev = 0;
        // sparkle particles spawned on beats: {x, y, vx, vy, life, hue}
        var vizParticles = [];
        // per-spoke random seeds so the radial bars vary in hue/length/angle
        var vizSeeds = [];
        for (var vs = 0; vs < 160; vs++) {
            vizSeeds.push(Math.random());
        }

        function drawVisualizer()
        {
            var canvas = document.getElementById('viz-canvas');
            if (!canvas || analyserNode === null) {
                vizRAF = null;
                return;
            }
            var ctx = canvas.getContext('2d');
            var width = canvas.width = (canvas.clientWidth || canvas.offsetWidth || 300);
            var height = canvas.height = (canvas.clientHeight || canvas.offsetHeight || 150);
            var bins = analyserNode.frequencyBinCount;
            var data = new Uint8Array(bins);
            analyserNode.getByteFrequencyData(data);
            ctx.clearRect(0, 0, width, height);

            // overall loudness drives the central pulse
            var sum = 0;
            for (var s = 0; s < bins; s++) { sum += data[s]; }
            var avg = sum / bins / 255;

            // bass energy (lowest ~8% of bins) drives beat detection + core glow
            var bassBins = Math.max(1, Math.floor(bins * 0.08));
            var bassSum = 0;
            for (var b = 0; b < bassBins; b++) { bassSum += data[b]; }
            var bass = bassSum / bassBins / 255;

            var cx = width / 2, cy = height / 2;
            var minDim = Math.min(width, height);
            var baseR = minDim * 0.11 * (1 + avg * 0.9);
            var maxLen = minDim * 0.46;
            var spokes = 120;
            var stride = Math.max(1, Math.floor(bins / spokes));
            vizTick += 0.004;
            var hueShift = (vizTick * 40) % 360;   // slow global colour drift
            ctx.lineCap = 'round';
            // additive blending so overlapping bars, glow and sparkles bloom
            ctx.globalCompositeOperation = 'lighter';

            // soft radial core glow that swells with the bass
            var coreR = baseR * (2.4 + bass * 2.2);
            var core = ctx.createRadialGradient(cx, cy, 0, cx, cy, coreR);
            core.addColorStop(0, 'hsla(' + Math.round(190 + hueShift) % 360 + ', 95%, 65%, ' + (0.35 + bass * 0.5) + ')');
            core.addColorStop(1, 'hsla(' + Math.round(190 + hueShift) % 360 + ', 95%, 55%, 0)');
            ctx.fillStyle = core;
            ctx.beginPath();
            ctx.arc(cx, cy, coreR, 0, Math.PI * 2);
            ctx.fill();

            ctx.shadowBlur = 8 + avg * 22;
            for (var i = 0; i < spokes; i++) {
                var value = data[(i * stride) % bins] / 255;
                var seed = vizSeeds[i % vizSeeds.length];
                // random per-spoke angle offset, length scale and hue
                var angle = (i / spokes) * Math.PI * 2 + vizTick + seed * 0.4;
                var len = baseR + value * maxLen * (0.5 + seed * 0.9);
                var hue = Math.round(150 + seed * 130 + value * 50 + hueShift) % 360;
                var col = 'hsl(' + hue + ', 90%, ' + Math.round(50 + value * 30) + '%)';
                var tipX = cx + Math.cos(angle) * len, tipY = cy + Math.sin(angle) * len;
                ctx.strokeStyle = col;
                ctx.shadowColor = col;
                ctx.lineWidth = 3 + value * 9;
                ctx.beginPath();
                ctx.moveTo(cx + Math.cos(angle) * baseR, cy + Math.sin(angle) * baseR);
                ctx.lineTo(tipX, tipY);
                ctx.stroke();
                // bright dot on the tip of the loudest spokes
                if (value > 0.35) {
                    ctx.fillStyle = 'hsl(' + hue + ', 95%, 75%)';
                    ctx.beginPath();
                    ctx.arc(tipX, tipY, 2 + value * 4, 0, Math.PI * 2);
                    ctx.fill();
                }
            }
            ctx.shadowBlur = 0;

            // reactive ring pulsing with loudness
            ctx.beginPath();
            ctx.arc(cx, cy, baseR * (1.15 + avg * 0.7), 0, Math.PI * 2);
            ctx.strokeStyle = 'hsla(' + Math.round(190 + hueShift) % 360 + ', 90%, 70%, ' + (0.3 + avg * 0.5) + ')';
            ctx.lineWidth = 2 + avg * 6;
            ctx.stroke();

            // on a bass hit, burst out a ring of sparkle particles
            if (bass - vizBassPrev > 0.10 && bass > 0.35) {
                var burst = 6 + Math.floor(bass * 12);
                for (var p = 0; p < burst; p++) {
                    var pa = (p / burst) * Math.PI * 2 + Math.random() * 0.4;
                    var sp = (2 + Math.random() * 3) * (1 + bass);
                    vizParticles.push({
                        x: cx + Math.cos(pa) * baseR,
                        y: cy + Math.sin(pa) * baseR,
                        vx: Math.cos(pa) * sp,
                        vy: Math.sin(pa) * sp,
                        life: 1,
                        hue: Math.round(150 + Math.random() * 160 + hueShift) % 360
                    });
                }
            }
            vizBassPrev = bass;

            // advance + draw sparkles (fade and drift outward), cap the pool
            for (var q = vizParticles.length - 1; q >= 0; q--) {
                var pt = vizParticles[q];
                pt.x += pt.vx;
                pt.y += pt.vy;
                pt.vx *= 0.96;
                pt.vy *= 0.96;
                pt.life -= 0.02;
                if (pt.life <= 0) {
                    vizParticles.splice(q, 1);
                    continue;
                }
                ctx.fillStyle = 'hsla(' + pt.hue + ', 95%, 70%, ' + pt.life + ')';
                ctx.beginPath();
                ctx.arc(pt.x, pt.y, 1 + pt.life * 2.5, 0, Math.PI * 2);
                ctx.fill();
            }
            if (vizParticles.length > 400) {
                vizParticles.splice(0, vizParticles.length - 400);
            }

            ctx.globalCompositeOperation = 'source-over';
            vizRAF = requestAnimationFrame(drawVisualizer);
        }

        function ShowVisualizer()
        {
            if (isVisualizerEnabled()) {
                if (vizRAF !== null) {
                    cancelAnimationFrame(vizRAF);
                    vizRAF = null;
                }
                $('#visualizer').css('visibility', 'hidden');
                $('#vizfullbtn').css('visibility', 'hidden');
                $('#webplayer').removeClass('viz-active');
                return;
            }

            if (currentTrackIsRemote()) {
                alert("<?php echo addslashes(T_('The visualizer and equalizer are not available for remote catalog streams.')); ?>");
                return;
            }
            if (!ensureAudioGraph()) {
                alert("<?php echo addslashes(T_("Your browser doesn't support this feature.")); ?>");
                return;
            }
            if (audioContext && audioContext.state === 'suspended') {
                audioContext.resume();
            }

            $('#visualizer').css('visibility', 'visible');
            $('#vizfullbtn').css('visibility', 'visible');
            $('#webplayer').addClass('viz-active');
            $('#webplayer').show();
            $("#webplayer-minimize").show();
            drawVisualizer();
        }

        function ShowVisualizerFullScreen()
        {
            if (!isVisualizerEnabled()) {
                ShowVisualizer();
            }

            var element = document.getElementById("visualizer");
            if (element.requestFullscreen) {
                element.requestFullscreen();
            } else if (element.webkitRequestFullscreen) {
                element.webkitRequestFullscreen();
            } else {
                alert("<?php echo addslashes(T_('Full-Screen not supported by your browser')); ?>");
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
        var analyserNode = null;
        var replaygainEnabled = false;
        var replaygainNode = null;
        var eqFilters = [];
        var eqBands = [80, 240, 750, 2200, 6000];
        initAudioContext();

        function currentTrackIsRemote()
        {
            try {
                if (typeof jplaylist !== 'undefined' && jplaylist && jplaylist.playlist) {
                    var track = jplaylist.playlist[jplaylist.current];
                    return !!(track && track.remote);
                }
            } catch (e) {}
            return false;
        }

        // Radio and any other directly referenced url the server did not flag; a redirect still needs `remote`.
        function isCrossOriginSource(src)
        {
            if (!src) {
                return false;
            }
            try {
                return (new URL(src, window.location.href)).origin !== window.location.origin;
            } catch (e) {
                return false;
            }
        }

        // Build the shared Web Audio graph once. Only ONE MediaElementSourceNode
        // may exist per media element, so ReplayGain and the visualizer share it:
        //   mediaSource -> replaygainNode -> destination
        //                                 -> analyserNode (tap for the visualizer)
        function ensureAudioGraph()
        {
            if (mediaSource !== null) {
                return true;
            }
            // Never route a remote (cross-origin) stream through Web Audio, or it plays silent.
            if (currentTrackIsRemote()) {
                return false;
            }
            var mediaElement = $('.jp-jplayer').find('audio').get(0);
            if (!mediaElement || audioContext === null) {
                return false;
            }
            if (isCrossOriginSource(mediaElement.currentSrc)) {
                return false;
            }
            mediaSource = audioContext.createMediaElementSource(mediaElement);
            replaygainNode = audioContext.createGain();
            replaygainNode.gain.value = 1;
            analyserNode = audioContext.createAnalyser();
            analyserNode.fftSize = 2048;
            buildEqualizer();
            // mediaSource -> [EQ bands] -> replaygainNode -> destination (+ analyser tap)
            if (eqFilters.length) {
                mediaSource.connect(eqFilters[0]);
                for (var i = 0; i < eqFilters.length - 1; i++) {
                    eqFilters[i].connect(eqFilters[i + 1]);
                }
                eqFilters[eqFilters.length - 1].connect(replaygainNode);
            } else {
                mediaSource.connect(replaygainNode);
            }
            replaygainNode.connect(audioContext.destination);
            replaygainNode.connect(analyserNode);
            return true;
        }

        // 5-band peaking equalizer (80/240/750/2200/6000 Hz) spliced into the graph.
        function buildEqualizer()
        {
            if (eqFilters.length || audioContext === null) {
                return;
            }
            eqFilters = eqBands.map(function (freq) {
                var f = audioContext.createBiquadFilter();
                f.type = 'peaking';
                f.frequency.value = freq;
                f.Q.value = 1;
                f.gain.value = 0;
                return f;
            });
        }

        function setEqBand(index, value)
        {
            if (!ensureAudioGraph()) {
                return;
            }
            if (audioContext && audioContext.state === 'suspended') {
                audioContext.resume();
            }
            if (eqFilters[index]) {
                eqFilters[index].gain.value = parseFloat(value);
            }
        }

        function ShowEqualizer()
        {
            if (currentTrackIsRemote()) {
                alert("<?php echo addslashes(T_('The visualizer and equalizer are not available for remote catalog streams.')); ?>");
                return;
            }
            ensureAudioGraph();
            if (audioContext && audioContext.state === 'suspended') {
                audioContext.resume();
            }
            var eq = $('#equalizer');
            eq.css('visibility', (eq.css('visibility') === 'visible') ? 'hidden' : 'visible');
        }

        function ToggleReplayGain()
        {
            if (!ensureAudioGraph()) {
                return;
            }
            if (audioContext && audioContext.state === 'suspended') {
                audioContext.resume();
            }

            replaygainEnabled = !replaygainEnabled;
            Cookies.set('replaygain', replaygainEnabled, {<?php echo $cookie_string; ?>});
            ApplyReplayGain();

            if (replaygainEnabled) {
                $('#replaygainbtn').css('box-shadow', '0px 1px 0px 0px <?php echo $highlight; ?>');
            } else {
                $('#replaygainbtn').css('box-shadow', '');
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
    var brkey = '';
    var brconn = null;
    var brListening = false;

    function startBroadcast(key)
    {
        brkey = key;
        brListening = false;

        listenBroadcast();
        brconn.onopen = function(e) {
            sendBroadcastMessage('AUTH_SID', '<?php echo session_id(); ?>');
            sendBroadcastMessage('REGISTER_BROADCAST', brkey);
            sendBroadcastMessage('SONG', currentjpitem.attr("data-media_id"));
        };
    }

    function startBroadcastListening(broadcast_id)
    {
        brListening = true;
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

    function restoreListenerControls()
    {
        $('.jp-previous').css('visibility', '');
        $('.jp-play').css('visibility', '');
        $('.jp-pause').css('visibility', '');
        $('.jp-next').css('visibility', '');
        $('.jp-stop').css('visibility', '');
        $('.jp-toggles').css('visibility', '');
        $('.jp-playlist').css('visibility', '');
        $('#broadcast').css('visibility', '');
        $('.jp-seek-bar').css('pointer-events', '');
    }

    function onBroadcastSocketError(e)
    {
        console.error('[broadcast] websocket error connecting to <?php echo Broadcast_Server::get_address(); ?>', e);
    }

    function onBroadcastSocketClose(e)
    {
        console.error('[broadcast] websocket closed (code ' + e.code + (e.reason ? ', ' + e.reason : '') + ')');

        if (brListening) {
            restoreListenerControls();
            displayNotification('<?php echo addslashes(T_('Could not connect to the broadcast. Resuming normal playback.')); ?>', 5000);
        }

        brListening = false;
        brkey = '';
        brconn = null;
    }

    function listenBroadcast()
    {
        if (brconn != null) {
            stopBroadcast();
        }

        brconn = new WebSocket('<?php echo Broadcast_Server::get_address(); ?>');
        brconn.onmessage = receiveBroadcastMessage;
        brconn.onerror = onBroadcastSocketError;
        brconn.onclose = onBroadcastSocketClose;
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
                        try {
                            addMedia($.parseJSON(atob(msg[1])));
                        } catch (err) {
                            console.error('[broadcast] failed to parse SONG payload', err);
                            break;
                        }
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
                        console.warn('[broadcast] unknown message code: ' + msg[0]);
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
