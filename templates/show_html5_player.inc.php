<?php
/* vim:set tabstop=4 softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU General Public License, version 2 (GPLv2)
 * Copyright 2013 Ampache.org
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License v2
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 *
 */

header('Cache-Control: no-cache');
header('Pragma: no-cache');
header('Expires: ' . gmdate(DATE_RFC1123, time()-1));

function browser_info($agent=null)
{
    // Declare known browsers to look for
    $known = array('msie', 'trident', 'firefox', 'safari', 'webkit', 'opera', 'netscape', 'konqueror', 'gecko');

    // Clean up agent and build regex that matches phrases for known browsers
    // (e.g. "Firefox/2.0" or "MSIE 6.0" (This only matches the major and minor
    // version numbers.  E.g. "2.0.0.6" is parsed as simply "2.0"
    $agent = strtolower($agent ? $agent : $_SERVER['HTTP_USER_AGENT']);
    $pattern = '#(?<browser>' . join('|', $known) . ')[/ ]+(?<version>[0-9]+(?:\.[0-9]+)?)#';

    // Find all phrases (or return empty array if none found)
    if (!preg_match_all($pattern, $agent, $matches)) return array();

    // Since some UAs have more than one phrase (e.g Firefox has a Gecko phrase,
    // Opera 7,8 have a MSIE phrase), use the last one found (the right-most one
    // in the UA).  That's usually the most correct.
    $i = count($matches['browser'])-1;
    return array($matches['browser'][$i] => $matches['version'][$i]);

}

?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0//EN">
<html>
<head>
<title><?php echo Config::get('site_title'); ?></title>
<?php
if ($iframed) {
?>
<link rel="stylesheet" href="<?php echo Config::get('web_path'); ?>/templates/jplayer.midnight.black-iframed.css" type="text/css" />
<?php
} else {
?>
<link rel="stylesheet" href="<?php echo Config::get('web_path'); ?>/templates/jplayer.midnight.black.css" type="text/css" />
<?php require_once Config::get('prefix') . '/templates/stylesheets.inc.php'; ?>
<?php
}
?>
<script src="<?php echo Config::get('web_path'); ?>/modules/jplayer/jquery.min.js" language="javascript" type="text/javascript"></script>
<script src="<?php echo Config::get('web_path'); ?>/modules/jplayer/jquery.jplayer.min.js" language="javascript" type="text/javascript"></script>
<script src="<?php echo Config::get('web_path'); ?>/modules/jplayer/jplayer.playlist.min.js" language="javascript" type="text/javascript"></script>
<script type="text/javascript">
    $(document).ready(function(){
        var myPlaylist = new jPlayerPlaylist({
            jPlayer: "#jquery_jplayer_1",
            cssSelectorAncestor: "#jp_container_1"
        }, [
<?php
$i = 0;
$playlist = new Stream_Playlist(scrub_in($_REQUEST['playlist_id']));
$jtypes = array();
foreach ($playlist->urls as $item) {
    echo ($i++ > 0 ? ',' : '') . '{' . "\n";
    foreach (array('title', 'author') as $member) {
        if ($member == "author")
            $kmember = "artist";
        else
            $kmember = $member;

        echo $kmember . ': "' . addslashes($item->$member) . '",' . "\n";
    }

    $url = $item->url;
    $browsers = array_keys(browser_info());
    if (count($browsers) > 0 ) {
        $browser = $browsers[0];
    }
    if ($browser == "msie" || $browser == "trident" || $browser == "webkit" || $browser == "safari") {
        $type = "mp3";
    } else {
        $type = "ogg";
    }

    $ftype = "mp3";
    $urlinfo = Stream_URL::parse($url);
    if ($urlinfo['id']) {
        $song = new Song($urlinfo['id']);
        $ftype = $song->type;
    }

    // Check transcode is required
    $transcode = false;
    if ($type != $ftype) {
        $transcode_cfg = Config::get('transcode');
        $valid_types = Song::get_stream_types_for_type($ftype);
        if ($transcode_cfg != 'never' && in_array('transcode', $valid_types)) {
            $transcode = true;
            $url .= '&transcode_to=' . $type; // &content_length=required
        }
    }
    if (!$transcode) {
        // Transcode not available for this type, keep real type and hope for flash fallback
        $type = $ftype;
    }

    $jtype = ($type == "ogg" || $type == "flac") ? "oga" : $type;

    if (!in_array($jtype, $jtypes)) {
        $jtypes[] = $jtype;
    }
    echo $jtype.': "' . $url;
    echo '",' . "\n";
    echo 'poster: "' . $item->image_url . (!$iframed ? '&thumb=4' : '') . '" }' . "\n";
}
?>
        ], {
            playlistOptions: {
                autoPlay: true,
                loopOnPrevious: false,
                shuffleOnLoop: true,
                enableRemoveControls: false,
                displayTime: 'slow',
                addTime: 'fast',
                removeTime: 'fast',
                shuffleTime: 'slow'
            },
            swfPath: "<?php echo Config::get('web_path'); ?>/modules/jplayer/",
            supplied: "<?php echo join(",", $jtypes); ?>",
            audioFullScreen: true,
            size: {
<?php
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
?>
            }
        });

    $("#jquery_jplayer_1").bind($.jPlayer.event.play, function (event) {
        var current = myPlaylist.current,
            playlist = myPlaylist.playlist;
        $.each(playlist, function (index, obj) {
            if (index == current) {
                $('.playing_title').text(obj.title);
                $('.playing_artist').text(obj.artist);
<?php
if (Config::get('song_page_title')) {
    if ($iframed) {
        echo "window.parent.document";
    } else {
        echo "document";
    }
    echo ".title = obj.title + ' - ' + obj.artist + ' | " . addslashes(Config::get('site_title')) . "';";
}
?>                
            }
        });
    });
});
</script>
<script language="javascript" type="text/javascript">
function ExitPlayer()
{
    var ff = parent.parent.document.getElementById('frame_footer');
    var maindiv = parent.parent.document.getElementById('maindiv');
    if (ff.getAttribute('className') == 'frame_footer_visible') {
        ff.setAttribute('className', 'frame_footer_hide');
        ff.setAttribute('class', 'frame_footer_hide');

        maindiv.style.height = parent.parent.innerHeight + "px";
    }
    ff.setAttribute('src', '');
    return false;
}
</script>
</head>
<body>
<?php
if ($iframed) {
?>
  <div class="jp-close">
    <a href="javascript:ExitPlayer();" title="Close Player"><img src="images/close.png" border="0" /></a>
  </div>
<?php
}
?>
<div class="playing_info">
    <div class="playing_artist"></div>
    <div class="playing_title"></div>
</div>
<div class="jp-area">
  <div id="jquery_jplayer_1" class="jp-jplayer"></div>
  <div id="jp_container_1" class="jp-audio">
    <div class="jp-type-playlist">
      <div class="jp-gui jp-interface">
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
        <div class="jp-volume-bar">
          <div class="jp-volume-bar-value"></div>
        </div>
        <div class="jp-current-time"></div>
        <div class="jp-duration"></div>
        <ul class="jp-toggles">
            <li><a href="javascript:;" class="jp-shuffle" tabindex="1" title="shuffle">shuffle</a></li>
            <li><a href="javascript:;" class="jp-shuffle-off" tabindex="1" title="shuffle off">shuffle off</a></li>
            <li><a href="javascript:;" class="jp-repeat" tabindex="1" title="repeat">repeat</a></li>
            <li><a href="javascript:;" class="jp-repeat-off" tabindex="1" title="repeat off">repeat off</a></li>
        </ul>
      </div>
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
</body>
</html>
