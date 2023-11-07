<?php

use Ampache\Config\AmpConfig;
use Ampache\Repository\Model\Broadcast;
use Ampache\Module\Authorization\Access;
use Ampache\Module\Playback\WebPlayer;
use Ampache\Module\System\Core;
use Ampache\Module\Util\EnvironmentInterface;
use Ampache\Module\Util\Ui;

// TODO remove me
global $dic;

/** @var bool $isVideo  */
/** @var bool $isRadio */
/** @var bool $isShare */
/** @var bool $isDemocratic */
/** @var bool $isRandom */
/** @var Ampache\Module\Playback\Stream_Playlist $playlist */

$environment   = $dic->get(EnvironmentInterface::class);
$web_path      = (string)AmpConfig::get('web_path', '');
$cookie_string = (make_bool(AmpConfig::get('cookie_secure')))
    ? "expires: 7, path: '/', secure: true, samesite: 'Strict'"
    : "expires: 7, path: '/', samesite: 'Strict'";

$autoplay       = true;
$iframed        = $iframed ?? false;
$isShare        = $isShare ?? false;
$embed          = $embed ?? false;
$loop           = ($isRandom || $isDemocratic);
$removeCount    = (int)AmpConfig::get('webplayer_removeplayed', 0);
$removePlayed   = ($removeCount > 0);
if ($removePlayed && $removeCount === 999) {
    $removeCount = 0;
}
if ($isShare) {
    $autoplay = (array_key_exists('autoplay', $_REQUEST) && make_bool($_REQUEST['autoplay']));
}
if (!$iframed) {
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
$replaygain = (AmpConfig::get('theme_color') == 'light')
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

    $(document).ready(function(){

        if (!isNaN(Cookies.get('jp_volume'))) {
            var jp_volume = Cookies.get('jp_volume');
        } else {
            var jp_volume = 0.80;
        }

        var replaygainPersist = Cookies.get('replaygain');

        jplaylist = new jPlayerPlaylist({
            jPlayer: "#jquery_jplayer_1",
            cssSelectorAncestor: "#jp_container_1"
        }, [], {
            playlistOptions: {
                autoPlay: <?php echo ($autoplay) ? 'true' : 'false'; ?>,
                removePlayed: <?php echo ($removePlayed) ? 'true' : 'false'; ?>, // remove tracks before the current playlist item
                removeCount: <?php echo $removeCount; ?>, // shift the index back to keep x items BEFORE the current index
                loopBack:  false, // repeat a finished playlist from the start
                shuffleOnLoop: false,
                enableRemoveControls: true,
                displayTime: 'slow',
                addTime: 'fast',
                removeTime: 'fast',
                shuffleTime: 'slow'
            },
            swfPath: "<?php echo $web_path; ?>/lib/modules/jplayer",
            preload: 'auto',
            loop: <?php echo ($loop) ? 'true' : 'false'; ?>, // this is the jplayer loop status
            audioFullScreen: true,
            smoothPlayBar: true,
            toggleDuration: true,
            keyEnabled: true,
            solution: "<?php
                $solutions = array();
if (AmpConfig::get('webplayer_html5')) {
    $solutions[] = 'html';
}
if (AmpConfig::get('webplayer_flash')) {
    $solutions[] = 'flash';
}
if (AmpConfig::get('webplayer_aurora')) {
    $solutions[] = 'aurora';
}
echo implode(',', $solutions); ?>",
            nativeSupport: true,
            oggSupport: false,
            supplied: "mp3, flac, m4a, oga, ogg, wav, m3u, m3u8, m4v, m3u8v, m3uv, ogv, webmv, flv, rtmpv",
            volume: jp_volume,
            <?php if (AmpConfig::get('webplayer_aurora')) { ?>
            auroraFormats: "wav, mp3, flac, aac, opus, m4a, oga, ogg, m3u, m3u8",
            <?php } ?>
            <?php if (!$isShare) { ?>
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
                } elseif ($isRadio) {
                    // No size
                } else {
                    if ($iframed) { ?>
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

        $("#jquery_jplayer_1").bind($.jPlayer.event.play, function (event) {
            if (replaygainPersist === 'true' && replaygainEnabled === false) {
                ToggleReplayGain();
            }
            var current = jplaylist.current,
                playlist = jplaylist.playlist;
            var pos = $(".jp-playlist-current").position().top + $(".jp-playlist").scrollTop();
            $(".jp-playlist").scrollTop(pos);
            <?php if ($iframed && AmpConfig::get('webplayer_confirmclose')) { ?>
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
                        <?php if (AmpConfig::get('browser_notify')) { ?>
                        NotifyOfNewSong(obj.title, obj.artist, currentjpitem.attr("data-poster"));
                        <?php } ?>
                        ApplyReplayGain();
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

                    <?php if (!$isVideo && !$isRadio && !$isShare) {
                        if ($iframed && !$isRadio && !$isRandom && !$isDemocratic) {
                            if (AmpConfig::get('sociable')) {
                                echo "ajaxPut(jsAjaxUrl + '?page=' + currenttype + '&action=shouts&object_type=' + currenttype + '&object_id=' + currentjpitem.attr('data-media_id'), 'shouts_data');";
                            }
                            echo "ajaxPut(jsAjaxUrl + '?action=action_buttons&object_type=' + actiontype + '&object_id=' + currentjpitem.attr('data-media_id'));";
                            echo "var titleobj = (typeof actiontype !== 'undefined') ? '<a href=\"javascript:NavigateTo(\'" . $web_path . "/' + currenttype + '.php?action=show_' + currenttype + '&' + currentobject + '=' + currentjpitem.attr('data-media_id') + '\');\" title=\"' + obj.title + '\">' + obj.title + '</a>' : obj.title;";
                            echo "var artistobj = (currentjpitem.attr('data-artist_id') !== 'undefined') ? '<a href=\"javascript:NavigateTo(\'" . $web_path . "/artists.php?action=show&artist=' + currentjpitem.attr('data-artist_id') + '\');\" title=\"' + obj.artist + '\">' + obj.artist + '</a>' : obj.artist;";
                            echo "var lyricsobj = (typeof actiontype !== 'undefined' && currenttype === 'song') ? '<a href=\"javascript:NavigateTo(\'" . $web_path . "/' + currenttype + '.php?action=show_lyrics&' + currentobject + '=' + currentjpitem.attr('data-media_id') + '\');\">" . addslashes(T_('Show Lyrics')) . "</a>' : '';";
                            echo "var actionsobj = (currentjpitem.attr('data-album_id') !== 'undefined') ? '<a href=\"javascript:NavigateTo(\'" . $web_path . "/albums.php?action=show&album=' + currentjpitem.attr('data-album_id') + '\');\" title=\"" . $showalbum . "\">" . Ui::get_icon('album', $showalbum) . "</a> |' : '';";
                            echo "actionsobj += (currentjpitem.attr('data-albumdisk_id') !== 'undefined') ? '<a href=\"javascript:NavigateTo(\'" . $web_path . "/albums.php?action=show_disk&album_disk=' + currentjpitem.attr('data-albumdisk_id') + '\');\" title=\"" . $showalbum . "\">" . Ui::get_icon('album', $showalbum) . "</a> |' : '';";
                            if (AmpConfig::get('sociable') && (!AmpConfig::get('use_auth') || Access::check('interface', 25))) {
                                echo "actionsobj += (typeof actiontype !== 'undefined') ? ' <a href=\"javascript:NavigateTo(\'" . $web_path . "/shout.php?action=show_add_shout&type=' + currenttype + '&id=' + currentjpitem.attr('data-media_id') + '\');\">" . Ui::get_icon('comment', addslashes(T_('Post Shout'))) . "</a> |' : '';";
                            }
                            echo "actionsobj += '<div id=\'action_buttons\'></div>';";
                            if (AmpConfig::get('waveform') && !$isShare) {
                                echo "var waveformobj = '';";
                                if (AmpConfig::get('sociable') && Access::check('interface', 25)) {
                                    echo "waveformobj += '<a href=\"#\" title=\"" . addslashes(T_('Double click to post a new shout')) . "\" onClick=\"javascript:WaveformClick(' + currentjpitem.attr('data-media_id') + ', ClickTimeOffset(event));\">';";
                                }
                                echo "waveformobj += '<div class=\"waveform-shouts\"></div>';";
                                echo "waveformobj += '<div class=\"waveform-time\"></div><img src=\"" . $web_path . "/waveform.php?' + currentobject + '=' + currentjpitem.attr('data-media_id') + '\" onLoad=\"ShowWaveform();\">';";
                                if (AmpConfig::get('waveform')) {
                                    echo "waveformobj += '</a>';";
                                }
                            }
                        } else {
                            echo "var titleobj = obj.title;";
                            echo "var artistobj = obj.artist;";
                        } ?>
                    $('.playing_title').html(titleobj);
                    $('.playing_artist').html(artistobj);
                    <?php if ($iframed && !$isRadio && !$isRandom && !$isDemocratic) { ?>
                    $('.playing_actions').html(actionsobj);
                    <?php if (AmpConfig::get('show_lyrics')) { ?>
                    $('.playing_lyrics').html(lyricsobj);
                    <?php }
                    if (AmpConfig::get('waveform') && !$isShare) { ?>
                    $('.waveform').html(waveformobj);
                    <?php }
                    }
                    }
                    if (AmpConfig::get('song_page_title') && !$isShare) {
                        echo "var mediaTitle = obj.title;\n";
                        echo "if (obj.artist !== null) mediaTitle += ' - ' + obj.artist;\n";
                        echo "document.title = mediaTitle + ' | " . addslashes(AmpConfig::get('site_title', '')) . "';";
                    } ?>
                }
            });
            <?php if (AmpConfig::get('waveform') && !$isShare) { ?>
            HideWaveform();
            <?php } ?>

            if (brkey != '') {
                sendBroadcastMessage('PLAYER_PLAY', 1);
            }
        });

        $("#jquery_jplayer_1").bind($.jPlayer.event.timeupdate, function (event) {
            if (brkey != '') {
                sendBroadcastMessage('SONG_POSITION', event.jPlayer.status.currentTime);
            }
            <?php if (AmpConfig::get('waveform') && !$isShare) { ?>
            var int_position = Math.floor(event.jPlayer.status.currentTime);
            if (int_position != last_int_position && event.jPlayer.status.currentTime > 0) {
                last_int_position = int_position;
                if (shouts[int_position] != undefined) {
                    shouts[int_position].forEach(function(e) {
                        console.log(e);
                    });
                }
            }
            if (event.jPlayer.status.duration > 0) {
                var leftpos = 400 * (event.jPlayer.status.currentTime / event.jPlayer.status.duration);
                $(".waveform-time").css({left: leftpos});
            }
            <?php } ?>
        });

        $("#jquery_jplayer_1").bind($.jPlayer.event.pause, function (event) {
            if (brkey != '') {
                sendBroadcastMessage('PLAYER_PLAY', 0);
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

        replaygainNode = null;
        replaygainEnabled = false;
        <?php echo WebPlayer::add_media_js($playlist); ?>

        $("#jquery_jplayer_1").resizable({
            alsoResize: "#jquery_jplayer_1 video",
            handles: "nw, ne, se, sw, n, e, w, s"
        });

        $("#jquery_jplayer_1 video").resizable();

        $("#jquery_jplayer_1").draggable();

    });
</script>
<?php // Load Aurora.js scripts
if (AmpConfig::get('webplayer_aurora')) {
    $atypes = array('mp3', 'flac', 'ogg', 'vorbis', 'opus', 'aac', 'alac');
    // Load only existing codec scripts
    if (!$isVideo) {
        foreach ($atypes as $atype) {
            $spath = $web_path . '/lib/modules/aurora.js/' . $atype . '.js';
            if (Core::is_readable($spath)) {
                echo '<script src="' . $spath . '" defer></script>' . "\n";
            }
        }
    }
}

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
} ?>
</head>
<body>
<?php $areaClass = "";
if ((!AmpConfig::get('waveform') || $isShare) && !$embed) {
    $areaClass .= " jp-area-center";
}
if ($embed) {
    $areaClass .= " jp-area-embed";
}

// hide that awful art section for shares
$shareStyle = ($isShare)
    ? "display: none;"
    : '';

if (!$isVideo) {
    $containerClass = "jp-audio";
    $playerClass    = "jp-jplayer-audio"; ?>
    <div class="playing_info">
        <div class="playing_artist"></div>
        <div class="playing_title"></div>
        <div class="playing_features">
            <div class="playing_lyrics"></div>
            <div class="playing_actions"></div>
        </div>
    </div>
    <?php
} else {
    $areaClass .= " jp-area-video";
    $containerClass = "jp-video jp-video-float jp-video-360p";
    $playerClass    = "jp-jplayer-video";
} ?>
<div id="shouts_data"></div>
<div class="jp-area<?php echo $areaClass; ?>">
    <div id="jp_container_1" class="<?php echo $containerClass; ?>">
        <div class="jp-type-playlist" style="background: #191919"">
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
                        <div class="jp-current-time"></div>
                        <div class="jp-duration"></div>
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
                        <div class="jp-progress">
                            <div class="jp-seek-bar">
                                <div class="jp-play-bar"></div>
                            </div>
                        </div>
                        <div id="jquery_jplayer_1_volume_bar" class="jp-volume-bar">
                            <div id="jquery_jplayer_1_volume_bar_value" class="jp-volume-bar-value"></div>
                        </div>
                        <div class="jp-current-time"></div>
                        <div class="jp-duration"></div>
                        <ul class="jp-toggles">
                            <li><a href="javascript:;" class="jp-shuffle" tabindex="1" title="<?php echo $shuffleon; ?>"><?php echo $shuffleon; ?></a></li>
                            <li><a href="javascript:;" class="jp-shuffle-off" tabindex="1" title="<?php echo $shuffleoff; ?>"><?php echo $shuffleoff; ?></a></li>
                            <li><a href="javascript:;" class="jp-repeat" tabindex="1" title="<?php echo $repeaton; ?>"><?php echo $repeaton; ?></a></li>
                            <li><a href="javascript:;" class="jp-repeat-off" tabindex="1" title="<?php echo $repeatoff; ?>"><?php echo $repeatoff; ?></a></li>
                        </ul>
                        <?php if (AmpConfig::get('waveform') && !$isShare) { ?>
                            <div class="waveform"></div>
                        <?php } ?>
                    <?php } ?>
                </div>
            </div>
            <?php if (!$isShare && !$environment->isMobile()) { ?>
                <div class="player_actions">
                    <?php if (AmpConfig::get('broadcast') && Access::check('interface', 25)) { ?>
                        <div id="broadcast" class="broadcast action_button">
                            <?php if (AmpConfig::get('broadcast_by_default')) {
                                $broadcasts = Broadcast::get_broadcasts(Core::get_global('user')->id);
                                if (count($broadcasts) < 1) {
                                    $broadcast_id = Broadcast::create(addslashes(T_('My Broadcast')));
                                } else {
                                    $broadcast_id = $broadcasts[0];
                                }

                                $broadcast = new Broadcast((int) $broadcast_id);
                                $key       = Broadcast::generate_key();
                                $broadcast->update_state(true, $key);
                                echo Broadcast::get_unbroadcast_link($broadcast_id) . '<script>startBroadcast(\'' . $key . '\');</script>';
                            } else {
                                echo Broadcast::get_broadcast_link();
                            } ?>
                        </div>
                    <?php } ?>
                    <?php if ($iframed && (!$isRadio && !$isRandom && !$isDemocratic)) { ?>
                        <?php if (Access::check('interface', 25)) { ?>
                            <div class="action_button">
                                <a href="javascript:SaveToExistingPlaylist(event);">
                                    <?php echo Ui::get_icon('playlist_add_all', addslashes(T_('Add All to playlist'))); ?>
                                </a>
                            </div>
                        <?php } ?>
                        <div id="slideshow" class="slideshow action_button">
                            <a href="javascript:SwapSlideshow();"><?php echo Ui::get_icon('image', addslashes(T_('Slideshow'))); ?></a>
                        </div>
                        <div id="expandplaylistbtn" class="action_button">
                            <a href="javascript:TogglePlaylistExpand();"><?php echo Ui::get_icon('multilines', addslashes(T_('Expand/Collapse playlist'))); ?></a>
                        </div>
                        <div id="playlistloopbtn" class="action_button">
                            <a href="javascript:TogglePlaylistLoop();"><?php echo Ui::get_icon('playlist_loop', addslashes(T_('Loop Playlist'))); ?></a>
                        </div>
                        <?php if (AmpConfig::get('webplayer_html5')) { ?>
                            <div class="action_button">
                                <a href="javascript:ShowVisualizer();"><?php echo Ui::get_icon('visualizer', addslashes(T_('Visualizer'))); ?></a>
                            </div>
                            <div id="replaygainbtn" class="action_button">
                                <a href="javascript:ToggleReplayGain();"><?php echo Ui::get_icon($replaygain, addslashes(T_('ReplayGain'))); ?></a>
                            </div>
                            <div id="vizfullbtn" class="action_button" style="visibility: hidden;">
                                <a href="javascript:ShowVisualizerFullScreen();"><?php echo Ui::get_icon('fullscreen', addslashes(T_('Visualizer full-screen'))); ?></a>
                            </div>
                            <div id="equalizerbtn" class="action_button" style="visibility: hidden;">
                                <a href="javascript:ShowEqualizer();"><?php echo Ui::get_icon('equalizer', addslashes(T_('Equalizer'))); ?></a>
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
<?php if (!$iframed || $isShare) {
    require_once Ui::find_template('uberviz.inc.php');
} ?>
<?php if (!$isShare) { ?>
</body>
    </html>
<?php } ?>
