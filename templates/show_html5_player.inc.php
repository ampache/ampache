<?php
$autoplay = true;
if ($is_share) {
    $autoplay = ($_REQUEST['autoplay'] === 'true');
}

if (!$iframed) {
    require_once AmpConfig::get('prefix') . UI::find_template('show_html5_player_headers.inc.php');
}
?>
<script type="text/javascript">
// The web player identifier. We currently use current date milliseconds as unique identifier.
var jpuqid = (new Date()).getMilliseconds();
var jplaylist = null;
var timeoffset = 0;
var last_int_position = 0
var currentjpitem = null;
var currentAudioElement = undefined;

    $(document).ready(function(){

        if (!isNaN($.cookie('jp_volume'))) {
            var jp_volume = $.cookie('jp_volume');
        } else {
            var jp_volume = 0.80;
        }

        jplaylist = new jPlayerPlaylist({
            jPlayer: "#jquery_jplayer_1",
            cssSelectorAncestor: "#jp_container_1"
        }, [], {
            playlistOptions: {
                autoPlay: <?php echo $autoplay ? 'true' : 'false'; ?>,
                loopOnPrevious: false,
                shuffleOnLoop: true,
                enableRemoveControls: true,
                displayTime: 'slow',
                addTime: 'fast',
                removeTime: 'fast',
                shuffleTime: 'slow'
            },
            swfPath: "<?php echo AmpConfig::get('web_path'); ?>/lib/vendor/happyworm/jplayer/dist/jplayer",
            preload: 'none',
            audioFullScreen: true,
            smoothPlayBar: true,
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
echo implode(',', $solutions);

$supplied = WebPlayer::get_supplied_types($playlist);
?>",
            nativeSupport:true,
            oggSupport: false,
            supplied: "<?php echo implode(", ", $supplied); ?>",
            volume: jp_volume,
<?php if (AmpConfig::get('webplayer_aurora')) {
    ?>
            auroraFormats: 'flac, m4a, mp3, oga, wav',
<?php 
} ?>
<?php if (!$is_share) {
    ?>
            size: {
<?php
if ($isVideo) {
    if ($iframed) {
        ?>
                width: "640px",
<?php

    } else {
        ?>
                width: "192px",
                height: "108px",
<?php

    }
    ?>
                cssClass: "jp-video-360p"
<?php

} elseif ($isRadio) {
    // No size
} else {
    if ($iframed) {
        ?>
                width: "80px",
                height: "80px",
<?php

    } else {
        ?>
                width: "200px",
                height: "auto",
<?php

    }
}
    ?>
            }
<?php 
} ?>
        });

    $("#jquery_jplayer_1").bind($.jPlayer.event.play, function (event) {
        var current = jplaylist.current,
            playlist = jplaylist.playlist;
        var pos = $(".jp-playlist-current").position().top + $(".jp-playlist").scrollTop();
        $(".jp-playlist").scrollTop(pos);
<?php if ($iframed && AmpConfig::get('webplayer_confirmclose')) {
    ?>
        localStorage.setItem('ampache-current-webplayer', jpuqid);
<?php 
} ?>

        var currenti = $(".jp-playlist li").eq(current);
        $.each(playlist, function (index, obj) {
            if (index == current) {
                if (currentjpitem != currenti) {
                    var previousartist = 0;
                    if (currentjpitem != null) {
                        previousartist = currentjpitem.attr("data-artist_id");
                    }
                    currentjpitem = currenti;
<?php if ($iframed) {
    ?>
                    if (previousartist != currentjpitem.attr("data-artist_id")) {
                        NotifyOfNewArtist();
                    }
<?php 
} ?>
<?php if (AmpConfig::get('browser_notify')) {
    ?>
                    NotifyOfNewSong(obj.title, obj.artist, currentjpitem.attr("data-poster"));
<?php 
} ?>
                    ApplyReplayGain();
                }
                if (brkey != '') {
                    sendBroadcastMessage('SONG', currenti.attr("data-media_id"));
                }
<?php
if (!$isVideo && !$isRadio && !$is_share) {
    if ($iframed) {
        if (AmpConfig::get('sociable')) {
            echo "ajaxPut(jsAjaxUrl + '?page=song&action=shouts&object_type=song&object_id=' + currenti.attr('data-media_id'),'shouts_data');";
        }
        echo "ajaxPut(jsAjaxUrl + '?action=action_buttons&object_type=song&object_id=' + currenti.attr('data-media_id'));";
        echo "var titleobj = (currenti.attr('data-album_id') !== 'undefined') ? '<a href=\"javascript:NavigateTo(\'" . AmpConfig::get('web_path') . "/albums.php?action=show&album=' + currenti.attr('data-album_id') + '\');\" title=\"' + obj.title + '\">' + obj.title + '</a>' : obj.title;";
        echo "var artistobj = (currenti.attr('data-artist_id') !== 'undefined') ?'<a href=\"javascript:NavigateTo(\'" . AmpConfig::get('web_path') . "/artists.php?action=show&artist=' + currenti.attr('data-artist_id') + '\');\" title=\"' + obj.artist + '\">' + obj.artist + '</a>' : obj.artist;";
        echo "var lyricsobj = '<a href=\"javascript:NavigateTo(\'" . AmpConfig::get('web_path') . "/song.php?action=show_lyrics&song_id=' + currenti.attr('data-media_id') + '\');\">" . T_('Show Lyrics') . "</a>';";
        echo "var actionsobj = '|';";
        if (AmpConfig::get('sociable') && (!AmpConfig::get('use_auth') || Access::check('interface','25'))) {
            echo "actionsobj += ' <a href=\"javascript:NavigateTo(\'" . AmpConfig::get('web_path') . "/shout.php?action=show_add_shout&type=song&id=' + currenti.attr('data-media_id') + '\');\">" . UI::get_icon('comment', T_('Post Shout')) . "</a> |';";
        }
        echo "actionsobj += '<div id=\'action_buttons\'></div>';";
        if (AmpConfig::get('waveform') && !$is_share) {
            echo "var waveformobj = '';";
            if (AmpConfig::get('sociable') && Access::check('interface','25')) {
                echo "waveformobj += '<a href=\"#\" title=\"" . T_('Double click to post a new shout') . "\" onClick=\"javascript:WaveformClick(' + currenti.attr('data-media_id') + ', ClickTimeOffset(event));\">';";
            }
            echo "waveformobj += '<div class=\"waveform-shouts\"></div>';";
            echo "waveformobj += '<div class=\"waveform-time\"></div><img src=\"" . AmpConfig::get('web_path') . "/waveform.php?song_id=' + currenti.attr('data-media_id') + '\" onLoad=\"ShowWaveform();\">';";
            if (AmpConfig::get('waveform')) {
                echo "waveformobj += '</a>';";
            }
        }
    } else {
        echo "var titleobj = obj.title;";
        echo "var artistobj = obj.artist;";
    }
    ?>
                $('.playing_title').html(titleobj);
                $('.playing_artist').html(artistobj);
<?php
    if ($iframed) {
        ?>
                $('.playing_actions').html(actionsobj);
<?php
        if (AmpConfig::get('show_lyrics')) {
            ?>
                $('.playing_lyrics').html(lyricsobj);
<?php

        }
        if (AmpConfig::get('waveform') && !$is_share) {
            ?>
                $('.waveform').html(waveformobj);
<?php

        }
    }
}
if (AmpConfig::get('song_page_title') && !$is_share) {
    echo "var mediaTitle = obj.title;\n";
    echo "if (obj.artist !== null) mediaTitle += ' - ' + obj.artist;\n";
    echo "document.title = mediaTitle + ' | " . addslashes(AmpConfig::get('site_title')) . "';";
}
?>
            }
        });
<?php
    if (AmpConfig::get('waveform') && !$is_share) {
        ?>
        HideWaveform();
<?php 
    } ?>

        if (brkey != '') {
            sendBroadcastMessage('PLAYER_PLAY', 1);
        }
    });

    $("#jquery_jplayer_1").bind($.jPlayer.event.timeupdate, function (event) {
        if (brkey != '') {
            sendBroadcastMessage('SONG_POSITION', event.jPlayer.status.currentTime);
        }
<?php
    if (AmpConfig::get('waveform') && !$is_share) {
        ?>
        var int_position = Math.floor(event.jPlayer.status.currentTime);
        if (int_position != last_int_position && event.jPlayer.status.currentTime > 0) {
            last_int_position = int_position;
            if (shouts[int_position] != undefined) {
                shouts[int_position].forEach(function(e) {
                    noty({text: e,
                            type: 'alert', layout: 'topRight',
                            template: '<div class="noty_message noty_ampache"><span class="noty_text noty_ampache"></span><div class="noty_close noty_ampache"></div></div>',
                            timeout: 2500,
                        });
                });
            }
        }
        if (event.jPlayer.status.duration > 0) {
            var leftpos = 400 * (event.jPlayer.status.currentTime / event.jPlayer.status.duration);
            $(".waveform-time").css({left: leftpos});
        }
<?php 
    } ?>
    });

    $("#jquery_jplayer_1").bind($.jPlayer.event.pause, function (event) {
        if (brkey != '') {
            sendBroadcastMessage('PLAYER_PLAY', 0);
        }
    });

    $("#jquery_jplayer_1").bind($.jPlayer.event.volumechange, function(event) {
        $.cookie('jp_volume', event.jPlayer.options.volume, { expires: 7, path: '/'});
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
<?php
// Load Aurora.js scripts
if (AmpConfig::get('webplayer_aurora')) {
    $atypes = array();
    foreach ($supplied as $stype) {
        if ($stype == 'ogg') {
            // Ogg could requires vorbis/opus codecs
            if (!in_array('ogg', $atypes)) {
                $atypes[] = 'ogg';
            }
            if (!in_array('vorbis', $atypes)) {
                $atypes[] = 'vorbis';
            }
            if (!in_array('opus', $atypes)) {
                $atypes[] = 'opus';
            }
        } else {
            if ($stype == 'm4a') {
                // m4a could requires aac / alac codecs
            if (!in_array('aac', $atypes)) {
                $atypes[] = 'aac';
            }
                if (!in_array('alac', $atypes)) {
                    $atypes[] = 'alac';
                }
            } else {
                // We support that other filetypes requires a codec name matching the filetype
            if (!in_array($stype, $atypes)) {
                $atypes[] = $stype;
            }
            }
        }
    }

    // Load only existing codec scripts
    foreach ($atypes as $atype) {
        $spath = '/modules/aurora.js/' . $atype . '.js';
        if (Core::is_readable(AmpConfig::get('prefix') . $spath)) {
            echo '<script src="' . AmpConfig::get('web_path') . $spath . '" language="javascript" type="text/javascript"></script>' . "\n";
        }
    }
}

// TODO: avoid share style here
if ($is_share && $isVideo) {
    ?>
<style>
    div.jp-jplayer
    {
        bottom: 0px !important;
        top: 100px !important;
    }
</style>
<?php

}
?>
</head>
<body>
<?php
if ($iframed && !$is_share) {
    ?>
  <div class="jp-close">
    <a href="javascript:ExitPlayer();" title="Close Player"><img src="images/close.png" border="0" /></a>
  </div>
<?php

}
?>
<?php
$areaClass = "";
if ((!AmpConfig::get('waveform') || $is_share) && !$embed) {
    $areaClass .= " jp-area-center";
}
if ($embed) {
    $areaClass .= " jp-area-embed";
}

if (!$isVideo) {
    $containerClass = "jp-audio";
    $playerClass    = "jp-jplayer-audio";
    ?>
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
    <div class="jp-type-playlist">
      <div id="jquery_jplayer_1" class="jp-jplayer <?php echo $playerClass; ?>"></div>
      <div class="jp-gui">
<?php
if ($isVideo) {
    ?>
        <div class="jp-video-play">
            <a href="javascript:;" class="jp-video-play-icon" tabindex="1">play</a>
        </div>
<?php 
} ?>
        <div class="jp-interface">
<?php
if ($isVideo) {
    ?>
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
                    <li><a href="javascript:;" class="jp-previous" tabindex="1">previous</a></li>
                    <li><a href="javascript:;" class="jp-play" tabindex="1">play</a></li>
                    <li><a href="javascript:;" class="jp-pause" tabindex="1">pause</a></li>
                    <li><a href="javascript:;" class="jp-next" tabindex="1">next</a></li>
                    <li><a href="javascript:;" class="jp-stop" tabindex="1">stop</a></li>
                    <li><a href="javascript:;" class="jp-mute" tabindex="1" title="mute">mute</a></li>
                    <li><a href="javascript:;" class="jp-unmute" tabindex="1" title="unmute">unmute</a></li>
                    <li><a href="javascript:;" class="jp-volume-max" tabindex="1" title="max volume">max volume</a></li>
                </ul>
                <div class="jp-volume-bar">
                    <div class="jp-volume-bar-value"></div>
                </div>

                <ul class="jp-toggles">
                    <li><a href="javascript:;" class="jp-full-screen" tabindex="1" title="full screen">full screen</a></li>
                    <li><a href="javascript:;" class="jp-restore-screen" tabindex="1" title="restore screen">restore screen</a></li>
                    <li><a href="javascript:;" class="jp-shuffle" tabindex="1" title="shuffle">shuffle</a></li>
                    <li><a href="javascript:;" class="jp-shuffle-off" tabindex="1" title="shuffle off">shuffle off</a></li>
                    <li><a href="javascript:;" class="jp-repeat" tabindex="1" title="repeat">repeat</a></li>
                    <li><a href="javascript:;" class="jp-repeat-off" tabindex="1" title="repeat off">repeat off</a></li>
                </ul>
            </div>
<?php 
} else {
    ?>
            <ul class="jp-controls">
              <li><a href="javascript:;" class="jp-previous" tabindex="1">previous</a></li>
              <li><a href="javascript:;" class="jp-play" tabindex="1">play</a></li>
              <li><a href="javascript:;" class="jp-pause" tabindex="1">pause</a></li>
              <li><a href="javascript:;" class="jp-next" tabindex="1">next</a></li>
              <li><a href="javascript:;" class="jp-stop" tabindex="1">stop</a></li>
              <li><a href="javascript:;" class="jp-mute" tabindex="1" title="mute">mute</a></li>
              <li><a href="javascript:;" class="jp-unmute" tabindex="1" title="unmute">unmute</a></li>
              <li><a href="javascript:;" class="jp-volume-max" tabindex="1" title="max volume">max volume</a></li>
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
                <li><a href="javascript:;" class="jp-shuffle" tabindex="1" title="shuffle">shuffle</a></li>
                <li><a href="javascript:;" class="jp-shuffle-off" tabindex="1" title="shuffle off">shuffle off</a></li>
                <li><a href="javascript:;" class="jp-repeat" tabindex="1" title="repeat">repeat</a></li>
                <li><a href="javascript:;" class="jp-repeat-off" tabindex="1" title="repeat off">repeat off</a></li>
            </ul>
<?php if (AmpConfig::get('waveform') && !$is_share) {
    ?>
            <div class="waveform"></div>
<?php 
}
    ?>
<?php 
} ?>
        </div>
      </div>
<?php if (!$is_share) {
    ?>
      <div class="player_actions">
<?php if (AmpConfig::get('broadcast') && Access::check('interface', '25')) {
    ?>
        <div id="broadcast" class="broadcast action_button">
<?php
        if (AmpConfig::get('broadcast_by_default')) {
            $broadcasts = Broadcast::get_broadcasts($GLOBALS['user']->id);
            if (count($broadcasts) < 1) {
                $broadcast_id = Broadcast::create(T_('My Broadcast'));
            } else {
                $broadcast_id = $broadcasts[0];
            }

            $broadcast = new Broadcast($broadcast_id);
            $key       = Broadcast::generate_key();
            $broadcast->update_state(true, $key);
            echo Broadcast::get_unbroadcast_link($broadcast_id) . '<script type="text/javascript">startBroadcast(\'' . $key . '\');</script>';
        } else {
            echo Broadcast::get_broadcast_link();
        }
    ?>
        </div>
<?php 
}
    ?>
<?php if ($iframed) {
    ?>
        <?php if (Access::check('interface', '25')) {
    ?>
            <div class="action_button">
                <a onclick="javascript:SaveToExistingPlaylist(event);">
                    <?php echo UI::get_icon('playlist_add', T_('Add to existing playlist'));
    ?>
                </a>
            </div>

        <?php 
}
    ?>
        <div id="slideshow" class="slideshow action_button">
            <a href="javascript:SwapSlideshow();"><?php echo UI::get_icon('image', T_('Slideshow'));
    ?></a>
        </div>
<?php if (AmpConfig::get('webplayer_html5')) {
    ?>
        <div id="equalizerbtn" class="action_button" style="visibility: hidden;">
            <a href="javascript:ShowEqualizer();"><?php echo UI::get_icon('equalizer', T_('Equalizer'));
    ?></a>
        </div>
        <div class="action_button">
            <a href="javascript:ShowVisualizer();"><?php echo UI::get_icon('visualizer', T_('Visualizer'));
    ?></a>
        </div>
        <div class="action_button">
            <a onClick="ShowVisualizerFullScreen();" href="#"><?php echo UI::get_icon('fullscreen', T_('Visualizer Full-Screen'));
    ?></a>
        </div>
        <div id="replaygainbtn" class="action_button">
            <a href="javascript:ToggleReplayGain();"><?php echo UI::get_icon('replaygain', T_('ReplayGain'));
    ?></a>
        </div>
<?php 
}
    ?>
<?php 
}
    ?>
      </div>
<?php 
} ?>
      <div class="jp-playlist">
          <ul>
              <li></li>
          </ul>
      </div>
      <div class="jp-no-solution">
        <span>Unsupported</span>
        This media is not supported by the player. Is your browser up to date?
      </div>
    </div>
  </div>
</div>
<?php
if (!$iframed || $is_share) {
    require_once AmpConfig::get('prefix') . UI::find_template('uberviz.inc.php');
}
?>
<?php if (!$is_share) {
    ?>
</body>
</html>
<?php 
} ?>
