<?php

use Ampache\Config\AmpConfig;
use Ampache\Module\Authorization\Access;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Module\Playback\Stream_Playlist;
use Ampache\Module\Playback\WebPlayer;
use Ampache\Module\System\Core;
use Ampache\Module\Util\Ui;
use Ampache\Repository\Model\Broadcast;
use Ampache\Repository\Model\Preference;

/** @var bool $isVideo  */

// show_html5_player.inc.php
/** @var bool $isDemocratic */
/** @var bool $isRandom */
/** @var bool $isShare */
/** @var bool $iframed */
/** @var bool|null $embed */
/** @var Stream_Playlist $playlist */

$web_path = AmpConfig::get_web_path('/client');

$cookie_string = (make_bool(AmpConfig::get('cookie_secure')))
    ? "expires: 7, path: '/', secure: true, samesite: 'Strict'"
    : "expires: 7, path: '/', samesite: 'Strict'";

$autoplay     = true;
$embed        = $embed ?? false;
// When true the caller owns the surrounding document (<head>/<body>) and the
// player CSS/JS headers; only the player markup + init script are emitted.
$playerFragment = $playerFragment ?? false;
$loop           = ($isRandom || $isDemocratic);
$jp_volume      = (float) AmpConfig::get('jp_volume', 0.80);
$removeCount    = (int) AmpConfig::get('webplayer_removeplayed', 0);
$canSlideshow   = Preference::exists('flickr_api_key');
$removePlayed   = ($removeCount > 0);
if ($removePlayed && $removeCount === 999) {
    $removeCount = 0;
}
if ($isShare) {
    $autoplay = (array_key_exists('autoplay', $_REQUEST) && make_bool($_REQUEST['autoplay']));
}
if ($iframed === false && !$playerFragment) {
    require_once Ui::find_template('show_html5_player_headers.inc.php');
}
$prev       = addslashes(T_('Previous'));
$play       = addslashes(T_('Play'));
$pause      = addslashes(T_('Pause'));
$next       = addslashes(T_('Next'));
$stop       = addslashes(T_('Stop'));
$mute       = addslashes(T_('Mute'));
$unmute     = addslashes(T_('Unmute'));
$maxvol     = addslashes(T_('Max Volume'));
$fullscreen = addslashes(T_('Full Screen'));
$restscreen = addslashes(T_('Restore Screen'));
$shuffleon  = addslashes(T_('Shuffle'));
$shuffleoff = addslashes(T_('Shuffle Off'));
$repeaton   = addslashes(T_('Repeat'));
$repeatoff  = addslashes(T_('Repeat Off'));
$showalbum  = addslashes(T_('Show Album'));
$replaygain = (AmpConfig::get('theme_color', 'dark') == 'light')
    ? 'replaygain_dark'
    : 'replaygain'; ?>
<script>
    // The web player identifier. We currently use current date milliseconds as unique identifier.
    var jpuqid = (new Date()).getMilliseconds();
    var jplaylist = null;
    var timeoffset = 0;
    var last_int_position = 0
    var currentjpitem = null;
    var currentAudioElement = undefined;

    // Random/democratic streams only carry a placeholder in the client playlist.
    // Poll the server for the real internal song that is currently playing and
    // fill in its title / artist / album / artwork / action buttons.
    var nowPlayingPoll = null;
    var nowPlayingObjectId = null;

    function pollNowPlaying()
    {
        if (typeof jsAjaxUrl === 'undefined') {
            return;
        }
        $.getJSON(jsAjaxUrl + '?page=player&action=now_playing', function (data) {
            if (data && data.found) {
                $('.playing_title').html(data.title);
                $('.playing_artist').html(data.artist);
                if (data.art) {
                    $('.playing_art').attr('src', data.art).show();
                }
                // only rebuild the action row when the song changes (it hosts the rating widget)
                if (data.actions && data.object_id && data.object_id !== nowPlayingObjectId) {
                    nowPlayingObjectId = data.object_id;
                    $('.playing_actions').html(data.actions);
                    ajaxPut(jsAjaxUrl + '?action=action_buttons&object_type=' + data.object_type + '&object_id=' + data.object_id);
                }
            }
        });
    }

    function startNowPlayingPoll()
    {
        if (nowPlayingPoll !== null) {
            return;
        }
        pollNowPlaying();
        nowPlayingPoll = setInterval(pollNowPlaying, 7000);
    }

    function stopNowPlayingPoll()
    {
        if (nowPlayingPoll !== null) {
            clearInterval(nowPlayingPoll);
            nowPlayingPoll = null;
        }
    }

    $(document).ready(function(){
        if (!isNaN(Cookies.get('jp_volume'))) {
            var jp_volume = Cookies.get('jp_volume');
        } else {
            var jp_volume = <?php echo $jp_volume; ?>;
        }

        var replaygainPersist = Cookies.get('replaygain');

        <?php if ($isShare === false) { ?>
        // Compact mode: default to collapsed on small screens, expanded on desktop.
        // A saved cookie (set by the toggle buttons) overrides the breakpoint default.
        var isSmallScreen = (window.innerWidth <= 768);
        var playlistCollapsed = Cookies.get('jp_playlist_collapsed');
        if (playlistCollapsed === undefined) {
            playlistCollapsed = isSmallScreen ? 'true' : 'false';
        }
        if (playlistCollapsed === 'true') {
            $('#jp_container_1').addClass('jp-playlist-collapsed');
        }
        if (Cookies.get('jp_playlist_expanded') === 'true') {
            $('#jp_container_1').addClass('jp-playlist-expanded');
        }
        if (typeof updatePlaylistControls === 'function') {
            updatePlaylistControls();
        }
        var nowPlayingHidden = Cookies.get('jp_nowplaying_hidden');
        if (nowPlayingHidden === undefined) {
            nowPlayingHidden = isSmallScreen ? 'true' : 'false';
        }
        if (nowPlayingHidden === 'true') {
            $('body').addClass('jp-nowplaying-hidden');
        }
        <?php } ?>

        jplaylist = new jPlayerPlaylist({
            jPlayer: "#jquery_jplayer_1",
            cssSelectorAncestor: "#jp_container_1"
        }, [], {
            playlistOptions: {
                autoPlay: <?php echo ($autoplay) ? 'true' : 'false'; ?>,
                removePlayed: <?php echo ($removePlayed) ? 'true' : 'false'; ?>, // remove tracks before the current playlist item
                removeCount: <?php echo $removeCount; ?>, // shift the index back to keep x items BEFORE the current index
                loopBack: false, // repeat a finished playlist from the start
                shuffleOnLoop: false,
                enableRemoveControls: <?php echo ($isShare) ? 'false' : 'true'; ?>,
                displayTime: 'slow',
                addTime: 'fast',
                removeTime: 'fast',
                shuffleTime: 'slow'
            },
            preload: '<?php echo ($isShare) ? 'none' : 'auto'; ?>',
            loop: <?php echo ($loop) ? 'true' : 'false'; ?>, // this is the jplayer loop status
            audioFullScreen: true,
            smoothPlayBar: true,
            toggleDuration: true,
            keyEnabled: true,
            solution: "html",
            nativeSupport: true,
            oggSupport: false,
            supplied: "mp3, flac, m4a, oga, ogg, wav, m3u, m3u8, m4v, m3u8v, m3uv, ogv, webmv, flv, rtmpv",
            volume: jp_volume,
            <?php if ($isShare === false) { ?>
            size: {
                <?php if ($isVideo) {
                    if ($iframed) { ?>
                width: "640px",
                <?php } else { ?>
                width: "192px",
                height: "108px",
                <?php } ?>
                cssClass: "jp-video-360p"
                <?php
                } else {
                    if ($isRandom) { ?>
                    visibility: "hidden",
                <?php } elseif ($iframed) { ?>
                width: "80px",
                height: "80px",
                <?php } else { ?>
                width: "200px",
                height: "auto",
                <?php }
                } ?>
            }
            <?php } ?>
        });

        // Allow control from the OS via e.g. MPRIS D-Bus interface on Linux
        if ("mediaSession" in navigator) {
            navigator.mediaSession.setActionHandler("nexttrack", () => { jplaylist.next(); });
            navigator.mediaSession.setActionHandler("previoustrack", () => { jplaylist.previous(); });
        }

        $("#jquery_jplayer_1").bind($.jPlayer.event.play, function (event) {
            <?php if ($isRandom || $isDemocratic) { ?>
            startNowPlayingPoll();
            <?php } ?>
            // Splice the shared audio graph (EQ + ReplayGain) in as soon as playback starts so the equalizer is active.
            if (typeof ensureAudioGraph === 'function' && ensureAudioGraph() && audioContext && audioContext.state === 'suspended') {
                audioContext.resume();
            }
            if (replaygainPersist === 'true' && replaygainEnabled === false && typeof ToggleReplayGain === 'function') {
                ToggleReplayGain();
            }
            var current = jplaylist.current,
                playlist = jplaylist.playlist;
            var pos = $(".jp-playlist-current").position().top + $(".jp-playlist").scrollTop();
            $(".jp-playlist").scrollTop(pos);
            <?php if ($iframed && AmpConfig::get('webplayer_pausetabs')) { ?>
            localStorage.setItem('ampache-current-webplayer', jpuqid);
            <?php } ?>

            var currenti = $(".jp-playlist li").eq(current);
            $.each(playlist, function (index, obj) {
                if (index == current) {
                    if (currentjpitem != currenti) {
                        var previousartist = 0;
                        if (currentjpitem != null) {
                            previousartist = currentjpitem.attr("data-artist_id");
                        }
                        currentjpitem = currenti;
                        <?php if ($iframed) { ?>
                        if (previousartist != currentjpitem.attr("data-artist_id")) {
                            NotifyOfNewArtist();
                        }
                        <?php } ?>
                        <?php if ($iframed && AmpConfig::get('browser_notify')) { ?>
                        NotifyOfNewSong(obj.title, obj.artist, currentjpitem.attr("data-poster"));
                        <?php } ?>
                        if ("mediaSession" in navigator) {
                            // Allow the browser to expose what's playing the the OS via e.g. MPRIS on Linux
                            navigator.mediaSession.metadata = new MediaMetadata({
                                title: obj.title,
                                artist: obj.artist,
                                artwork: [
                                    {
                                        src: currentjpitem.attr("data-poster"),
                                        sizes: "96x96",
                                        type: "image/png"
                                    },
                                    {
                                        // Not the right size, but 256x256 is necessary for
                                        // Android device to display the artwork
                                        src: currentjpitem.attr("data-poster"),
                                        sizes: "256x256",
                                        type: "image/png"
                                    }
                                ],
                                album: currentjpitem.attr("data-album_name"),
                            });
                            navigator.mediaSession.playbackState = "playing";
                        }
                        if (typeof ApplyReplayGain === 'function') {
                            ApplyReplayGain();
                        }
                    }
                    if (brkey != '') {
                        sendBroadcastMessage('SONG', currentjpitem.attr("data-media_id"));
                    }
                    if (playlist[index]['media_type'] === "song") {
                        var currenttype = 'song'
                        var currentobject = 'song_id'
                        var actiontype = 'song'
                    } else if (playlist[index]['media_type'] === "video") {
                        var currenttype = 'video'
                        var currentobject = 'video_id'
                        var actiontype = 'song'
                    } else if (playlist[index]['media_type'] === "live_stream") {
                        var currenttype = 'radio'
                        var currentobject = 'radio'
                        var actiontype = 'live_stream'
                    } else if (playlist[index]['media_type'] === "song_preview") {
                        var currenttype = 'song_preview'
                        var currentobject = 'song_preview'
                    } else if (playlist[index]['media_type'] === "podcast_episode") {
                        var currenttype = 'podcast_episode'
                        var currentobject = 'podcast_episode'
                        var actiontype = 'podcast_episode'
                    } else if (playlist[index]['media_type'] === "democratic") {
                        var currenttype = 'democratic'
                        var currentobject = 'democratic'
                    } else if (playlist[index]['media_type'] === "random") {
                        var currenttype = 'random'
                        var currentobject = 'random'
                    } else {
                        var currenttype = 'song'
                        var currentobject = 'song_id'
                    }

                    obj.title = obj.title.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
                    obj.artist = obj.artist.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');

                    <?php if (
                        $isVideo === false
                        && $isShare === false
                    ) {
                        if ($iframed) {
                            if (AmpConfig::get('sociable')) {
                                echo "ajaxPut(jsAjaxUrl + '?page=' + currenttype + '&action=shouts&object_type=' + currenttype + '&object_id=' + currentjpitem.attr('data-media_id'), 'shouts_data');";
                            }
                            echo "ajaxPut(jsAjaxUrl + '?action=action_buttons&object_type=' + actiontype + '&object_id=' + currentjpitem.attr('data-media_id'));";
                            echo "var titleobj = (typeof actiontype !== 'undefined') ? '<a href=\"javascript:NavigateTo(\'" . $web_path . "/' + currenttype + '.php?action=show_' + currenttype + '&' + currentobject + '=' + currentjpitem.attr('data-media_id') + '\');\" title=\"' + obj.title + '\">' + obj.title + '</a>' : obj.title;";
                            echo "var artistobj = (currentjpitem.attr('data-artist_id') !== 'undefined') ? '<a href=\"javascript:NavigateTo(\'" . $web_path . "/artists.php?action=show&artist=' + currentjpitem.attr('data-artist_id') + '\');\" title=\"' + obj.artist + '\">' + obj.artist + '</a>' : obj.artist;";
                            echo "var lyricsobj = (typeof actiontype !== 'undefined' && currenttype === 'song') ? '<a href=\"javascript:NavigateTo(\'" . $web_path . "/' + currenttype + '.php?action=show_lyrics&' + currentobject + '=' + currentjpitem.attr('data-media_id') + '\');\">" . addslashes(T_('Show Lyrics')) . "</a>' : '';";
                            echo "var actionsobj = (currentjpitem.attr('data-album_id') !== 'undefined') ? '<a href=\"javascript:NavigateTo(\'" . $web_path . "/albums.php?action=show&album=' + currentjpitem.attr('data-album_id') + '\');\" title=\"" . $showalbum . "\">" . Ui::get_material_symbol('album', $showalbum) . "</a> |' : '';";
                            echo "actionsobj += (currentjpitem.attr('data-albumdisk_id') !== 'undefined') ? '<a href=\"javascript:NavigateTo(\'" . $web_path . "/albums.php?action=show_disk&album_disk=' + currentjpitem.attr('data-albumdisk_id') + '\');\" title=\"" . $showalbum . "\">" . Ui::get_material_symbol('album', $showalbum) . "</a> |' : '';";
                            if (AmpConfig::get('sociable') && (!AmpConfig::get('use_auth') || Access::check(AccessTypeEnum::INTERFACE, AccessLevelEnum::USER))) {
                                echo "actionsobj += (typeof actiontype !== 'undefined') ? ' <a href=\"javascript:NavigateTo(\'" . $web_path . "/shout.php?action=show_add_shout&type=' + currenttype + '&id=' + currentjpitem.attr('data-media_id') + '\');\">" . Ui::get_material_symbol('comment', addslashes(T_('Post Shout'))) . "</a> |' : '';";
                            }
                            echo "actionsobj += '<div id=\'action_buttons\'></div>';";
                        } else {
                            echo "var titleobj = obj.title;";
                            echo "var artistobj = obj.artist;";
                        } ?>
                    $('.playing_title').html(titleobj);
                    $('.playing_artist').html(artistobj);
                    <?php // random/democratic still play regular songs, so the per-song actions (album, shout, rating) apply
                    if ($iframed) { ?>
                    $('.playing_actions').html(actionsobj);
                    <?php if (AmpConfig::get('show_lyrics')) { ?>
                    $('.playing_lyrics').html(lyricsobj);
                    <?php }
                    }
                    }
if (AmpConfig::get('song_page_title') && $isShare === false) {
    echo "var mediaTitle = obj.title;\n";
    echo "if (obj.artist !== null) mediaTitle += ' - ' + obj.artist;\n";
    echo "document.title = mediaTitle + ' | " . addslashes(AmpConfig::get('site_title', '')) . "';";
} ?>
                }
            });

            if (brkey != '') {
                sendBroadcastMessage('PLAYER_PLAY', 1);
            }
        });

        $("#jquery_jplayer_1").bind($.jPlayer.event.timeupdate, function (event) {
            if (brkey != '') {
                sendBroadcastMessage('SONG_POSITION', event.jPlayer.status.currentTime);
            }
        });

        $("#jquery_jplayer_1").bind($.jPlayer.event.pause, function (event) {
            <?php if ($isRandom || $isDemocratic) { ?>
            stopNowPlayingPoll();
            <?php } ?>
            if (brkey != '') {
                sendBroadcastMessage('PLAYER_PLAY', 0);
            }
            if ("mediaSession" in navigator) {
                navigator.mediaSession.playbackState = "paused";
            }
        });

        $("#jquery_jplayer_1").bind($.jPlayer.event.volumechange, function(event) {
            Cookies.set('jp_volume', event.jPlayer.options.volume, {<?php echo $cookie_string; ?>});
        });

        $("#jquery_jplayer_1").bind($.jPlayer.event.resize, function (event) {
            if (event.jPlayer.options.fullScreen) {
                $(".player_actions").hide();
                $(".jp-playlist").hide();
            } else {
                $(".player_actions").show();
                $(".jp-playlist").show();
            }
        });

        $('#jp_container_1' + ' ul:last').sortable({
            update: function () {
                jplaylist.scan();
            }
        });

        mediaSource = null;
        analyserNode = null;
        replaygainNode = null;
        replaygainEnabled = false;
        <?php echo WebPlayer::add_media_js($playlist); ?>
    });
</script>
<?php
// TODO: avoid share style here
if ($isShare && $isVideo) { ?>
    <style>
        div.jp-jplayer
        {
            bottom: 0 !important;
            top: 100px !important;
        }
    </style>
    <?php
}
if (!$playerFragment) { ?>
</head>
<body>
<?php } ?>
<?php $areaClass = "";
if (!$embed) {
    $areaClass .= " jp-area-center";
}
if ($embed) {
    $areaClass .= " jp-area-embed";
}

// hide that awful art section for shares
$shareStyle = ($isShare || $isRandom)
    ? "display: none;"
    : '';

if ($isVideo === false) {
    $containerClass = "jp-audio";
    $playerClass    = "jp-jplayer-audio";
    if ($isShare === false) { ?>
    <div class="playing_info">
        <img class="playing_art" alt="" style="display: none;">
        <div class="playing_artist"></div>
        <div class="playing_title"></div>
        <div class="playing_album"></div>
        <div class="playing_features">
            <div class="playing_lyrics"></div>
            <div class="playing_actions"></div>
        </div>
    </div>
    <?php }
    } else {
        $areaClass .= " jp-area-video";
        $containerClass = "jp-video jp-video-float jp-video-360p";
        $playerClass    = "jp-jplayer-video";
    } ?>
<div id="shouts_data"></div>
<div class="jp-area<?php echo $areaClass; ?>">
    <div id="jp_container_1" class="<?php echo $containerClass; ?>">
        <div class="jp-type-playlist">
            <div id="jquery_jplayer_1" class="jp-jplayer <?php echo $playerClass; ?>" style="<?php echo $shareStyle; ?>"></div>
            <div class="jp-gui">
                <?php if ($isVideo) { ?>
                    <div class="jp-video-play">
                        <a href="javascript:;" class="jp-video-play-icon" tabindex="1" title="<?php echo $play; ?>"><?php echo $play; ?></a>
                    </div>
                <?php } ?>
                <div class="jp-interface">
                    <?php if ($isVideo) { ?>
                        <div class="jp-progress">
                            <div class="jp-seek-bar">
                                <div class="jp-play-bar"></div>
                            </div>
                        </div>
                        <div class="jp-time-row">
                            <div class="jp-current-time"></div>
                            <div class="jp-duration"></div>
                        </div>
                        <div class="jp-title"></div>
                        <div class="jp-controls-holder">
                            <ul class="jp-controls">
                                <li><a href="javascript:;" class="jp-previous" tabindex="1" title="<?php echo $prev; ?>"><?php echo $prev; ?></a></li>
                                <li><a href="javascript:;" class="jp-play" tabindex="1" title="<?php echo $play; ?>"><?php echo $play; ?></a></li>
                                <li><a href="javascript:;" class="jp-pause" tabindex="1" title="<?php echo $pause; ?>"><?php echo $pause; ?></a></li>
                                <li><a href="javascript:;" class="jp-next" tabindex="1" title="<?php echo $next; ?>"><?php echo $next; ?></a></li>
                                <li><a href="javascript:;" class="jp-stop" tabindex="1" title="<?php echo $stop; ?>"><?php echo $stop; ?></a></li>
                                <li><a href="javascript:;" class="jp-mute" tabindex="1" title="<?php echo $mute; ?>"><?php echo $mute; ?></a></li>
                                <li><a href="javascript:;" class="jp-unmute" tabindex="1" title="<?php echo $unmute; ?>"><?php echo $unmute; ?></a></li>
                                <li><a href="javascript:;" class="jp-volume-max" tabindex="1" title="<?php echo $maxvol; ?>"><?php echo $maxvol; ?></a></li>
                            </ul>
                            <div class="jp-volume-bar">
                                <div class="jp-volume-bar-value"></div>
                            </div>

                            <ul class="jp-toggles">
                                <li><a href="javascript:;" class="jp-full-screen" tabindex="1" title="<?php echo $fullscreen; ?>"><?php echo $fullscreen; ?></a></li>
                                <li><a href="javascript:;" class="jp-restore-screen" tabindex="1" title="<?php echo $restscreen; ?>"><?php echo $restscreen; ?></a></li>
                                <li><a href="javascript:;" class="jp-shuffle" tabindex="1" title="<?php echo $shuffleon; ?>"><?php echo $shuffleon; ?></a></li>
                                <li><a href="javascript:;" class="jp-shuffle-off" tabindex="1" title="<?php echo $shuffleoff; ?>"><?php echo $shuffleoff; ?></a></li>
                                <li><a href="javascript:;" class="jp-repeat" tabindex="1" title="<?php echo $repeaton; ?>"><?php echo $repeaton; ?></a></li>
                                <li><a href="javascript:;" class="jp-repeat-off" tabindex="1" title="<?php echo $repeatoff; ?>"><?php echo $repeatoff; ?></a></li>
                            </ul>
                        </div>
                    <?php } else { ?>
                        <div class="jp-controls-holder">
                            <ul class="jp-controls">
                                <li><a href="javascript:;" class="jp-previous" tabindex="1" title="<?php echo $prev; ?>"><?php echo $prev; ?></a></li>
                                <li><a href="javascript:;" class="jp-play" tabindex="1" title="<?php echo $play; ?>"><?php echo $play; ?></a></li>
                                <li><a href="javascript:;" class="jp-pause" tabindex="1" title="<?php echo $pause; ?>"><?php echo $pause; ?></a></li>
                                <li><a href="javascript:;" class="jp-next" tabindex="1" title="<?php echo $next; ?>"><?php echo $next; ?></a></li>
                                <li><a href="javascript:;" class="jp-stop" tabindex="1" title="<?php echo $stop; ?>"><?php echo $stop; ?></a></li>
                                <li><a href="javascript:;" class="jp-mute" tabindex="1" title="<?php echo $mute; ?>"><?php echo $mute; ?></a></li>
                                <li><a href="javascript:;" class="jp-unmute" tabindex="1" title="<?php echo $unmute; ?>"><?php echo $unmute; ?></a></li>
                                <li><a href="javascript:;" class="jp-volume-max" tabindex="1" title="<?php echo $maxvol; ?>"><?php echo $maxvol; ?></a></li>
                            </ul>
                            <div id="jquery_jplayer_1_volume_bar" class="jp-volume-bar">
                                <div id="jquery_jplayer_1_volume_bar_value" class="jp-volume-bar-value"></div>
                            </div>
                            <ul class="jp-toggles">
                                <li><a href="javascript:;" class="jp-shuffle" tabindex="1" title="<?php echo $shuffleon; ?>"><?php echo $shuffleon; ?></a></li>
                                <li><a href="javascript:;" class="jp-shuffle-off" tabindex="1" title="<?php echo $shuffleoff; ?>"><?php echo $shuffleoff; ?></a></li>
                                <li><a href="javascript:;" class="jp-repeat" tabindex="1" title="<?php echo $repeaton; ?>"><?php echo $repeaton; ?></a></li>
                                <li><a href="javascript:;" class="jp-repeat-off" tabindex="1" title="<?php echo $repeatoff; ?>"><?php echo $repeatoff; ?></a></li>
                            </ul>
                        </div>
                        <div class="jp-progress">
                            <div class="jp-seek-bar">
                                <div class="jp-play-bar"></div>
                            </div>
                        </div>
                        <div class="jp-time-row">
                            <div class="jp-current-time"></div>
                            <div class="jp-duration"></div>
                        </div>
                    <?php } ?>
                </div>
            </div>
            <?php if ($isShare === false) { ?>
                <div class="player_actions">
                    <?php if ($iframed) { ?>
                        <?php // playlist-editing buttons make no sense when random/democratic drives the playlist;
                                  // show them dimmed and inert there so the action layout matches the regular player
                            $playlistEditable = ($isRandom === false && $isDemocratic === false); ?>
                            <div class="action_button">
                        <?php if (Access::check(AccessTypeEnum::INTERFACE, AccessLevelEnum::USER)) {
                            if ($playlistEditable) { ?>
                                <a href="javascript:SaveToExistingPlaylist(event);">
                                    <?php echo Ui::get_material_symbol('playlist_add', addslashes(T_('Add All to playlist'))); ?>
                                </a>
                            <?php } else { ?>
                                <span style="opacity: 0.25;"><?php echo Ui::get_material_symbol('playlist_add', addslashes(T_('Add All to playlist'))); ?></span>
                            <?php }
                            } ?>
                            </div>
                        <div id="playlistloopbtn" class="action_button">
                            <?php if ($playlistEditable) { ?>
                            <a href="javascript:TogglePlaylistLoop();"><?php echo Ui::get_material_symbol('laps', addslashes(T_('Loop Playlist'))); ?></a>
                            <?php } else { ?>
                            <span style="opacity: 0.25;"><?php echo Ui::get_material_symbol('laps', addslashes(T_('Loop Playlist'))); ?></span>
                            <?php } ?>
                        </div>
                        <div id="expandplaylistbtn" class="action_button">
                            <a href="javascript:TogglePlaylistExpand();"><?php echo Ui::get_material_symbol('expand_all', addslashes(T_('Expand/Collapse playlist'))); ?></a>
                        </div>
                        <div id="playlistshowbtn" class="action_button">
                            <a href="javascript:TogglePlaylistShow();"><?php echo Ui::get_material_symbol('playlist_play', addslashes(T_('Show/Hide Playlist'))); ?></a>
                        </div>
                        <?php if ($isVideo === false) { ?>
                        <div class="action_button">
                            <a href="javascript:ShowVisualizer();"><?php echo Ui::get_material_symbol('bubble_chart', addslashes(T_('Visualizer'))); ?></a>
                        </div>
                        <?php } ?>
                        <?php if ($canSlideshow) { ?>
                            <div id="slideshow" class="slideshow action_button">
                                <a href="javascript:SwapSlideshow();"><?php echo Ui::get_material_symbol('slideshow', addslashes(T_('Slideshow'))); ?></a>
                            </div>
                        <?php } ?>
                        <?php if (AmpConfig::get('broadcast') && Access::check(AccessTypeEnum::INTERFACE, AccessLevelEnum::USER)) { ?>
                            <div id="broadcast" class="broadcast action_button">
                                <?php if (AmpConfig::get('broadcast_by_default')) {
                                    $broadcasts = Broadcast::get_broadcasts(Core::get_global('user')?->getId() ?? 0);
                                    if (count($broadcasts) < 1) {
                                        $broadcast_id = Broadcast::create(addslashes(T_('My Broadcast')));
                                    } else {
                                        $broadcast_id = $broadcasts[0];
                                    }

                                    $broadcast = new Broadcast((int) $broadcast_id);
                                    $key       = Core::generate_random_key();
                                    $broadcast->update_state(1, $key);
                                    echo Broadcast::get_unbroadcast_link($broadcast_id) . '<script>startBroadcast(\'' . $key . '\');</script>';
                                } else {
                                    echo Broadcast::get_broadcast_link();
                                } ?>
                            </div>
                        <?php } ?>
                        <?php if ($isVideo === false) { ?>
                        <div id="nowplayingbtn" class="action_button">
                            <a href="javascript:ToggleNowPlaying();"><?php echo Ui::get_material_symbol('info', addslashes(T_('Show/Hide Now Playing'))); ?></a>
                        </div>
                        <?php } ?>
                        <?php // ReplayGain and the Equalizer tap the <audio> element, so they do not apply to video
                        if ($isVideo === false) { ?>
                            <div id="replaygainbtn" class="action_button">
                                <a href="javascript:ToggleReplayGain();"><?php echo Ui::get_material_symbol('graphic_eq', addslashes(T_('ReplayGain'))); ?></a>
                            </div>
                            <div id="equalizerbtn" class="action_button">
                                <a href="javascript:ShowEqualizer();"><?php echo Ui::get_material_symbol('equalizer', addslashes(T_('Equalizer'))); ?></a>
                            </div>
                            <div id="vizfullbtn" class="action_button" style="visibility: hidden">
                                <a href="javascript:ShowVisualizerFullScreen();"><?php echo Ui::get_material_symbol('fullscreen', addslashes(T_('Visualizer full-screen'))); ?></a>
                            </div>
                        <?php } ?>
                    <?php } ?>
                </div>
            <?php } ?>
            <div class="jp-playlist">
                <ul>
                    <li></li>
                </ul>
            </div>
            <div class="jp-no-solution">
                <span><?php echo addslashes(T_('Unsupported')); ?></span>
                <?php echo addslashes(T_('This media is not supported by the player. Is your browser up to date?')); ?>
            </div>
        </div>
    </div>
</div>
<?php if ($iframed === false && $isShare === false) {
    require_once Ui::find_template('uberviz.inc.php');
} ?>
<?php if ($isShare === false) { ?>
<?php echo Ui::material_symbol_sprite(); ?>
</body>
    </html>
<?php } ?>
