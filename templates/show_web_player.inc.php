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

?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0//EN">
<html>
<head>
<title><?php echo AmpConfig::get('site_title'); ?></title>
<?php
$playlist = new Stream_Playlist(scrub_in($_REQUEST['playlist_id']));
?>
<script language="javascript" type="text/javascript">
var artistids = new Array();
var albumids = new Array();
var songids = new Array();
var jplaylist = new Array();
var jtypes = new Array();

function addMedia(media)
{
    artistids.push(media['artist_id']);
    albumids.push(media['album_id']);
    songids.push(media['song_id']);

    var jpmedia = {};
    jpmedia['title'] = media['title'];
    jpmedia['artist'] = media['artist'];
    jpmedia[media['filetype']] = media['url'];
    jpmedia['poster'] = media['poster'];

    jplaylist.add(jpmedia);
}
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
<?php
if (AmpConfig::get('song_page_title')) {
    echo "window.parent.document.title = '" . addslashes(AmpConfig::get('site_title')) . "';";
}
?>
    }
    window.parent.onbeforeunload = null;
    ff.setAttribute('src', '');
    return false;
}
</script>
<?php
if (WebPlayer::is_playlist_radio($playlist)) {
    // Special stuff for web radio (to better handle Icecast/Shoutcast metadata ...)
    $radio = $playlist->urls[0];
    require_once AmpConfig::get('prefix') . '/templates/show_radio_player.inc.php';
} else {
    $isVideo = WebPlayer::is_playlist_video($playlist);
    require_once AmpConfig::get('prefix') . '/templates/show_html5_player.inc.php';
}
?>
