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
$playlist = new Stream_Playlist(scrub_in($_REQUEST['playlist_id']));
$i = 0;
$jtypes = array();
$radios = array();
$playlistjs = "";
$idsjs = "";

foreach ($playlist->urls as $item) {
    $playlistjs .= ($i > 0 ? ',' : '') . '{' . "\n";
    foreach (array('title', 'author') as $member) {
        if ($member == "author")
            $kmember = "artist";
        else
            $kmember = $member;

        $playlistjs .= $kmember . ': "' . addslashes($item->$member) . '",' . "\n";
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
    $transcode = false;
    if ($urlinfo['id']) {
        $song = new Song($urlinfo['id']);
        $ftype = $song->type;
		
		$idsjs .= "artistids[" . $i . "] = '" . $song->artist . "'; albumids[" . $i . "] = '" . $song->album . "'; songids[" . $i . "] = '" . $song->id . "';";

        $transcode_cfg = Config::get('transcode');
        // Check transcode is required
        if ($transcode_cfg == 'always' || $type != $ftype) {
            $valid_types = Song::get_stream_types_for_type($ftype);
            if ($transcode_cfg == 'always' || ($transcode_cfg != 'never' && in_array('transcode', $valid_types))) {
                // Transcode only if excepted type available
                $transcode_settings = $song->get_transcode_settings($type);
                if ($transcode_settings) {
                    $transcode = true;
                } else {
                    if (!in_array('native', $valid_types)) {
                        $transcode_settings = $song->get_transcode_settings(null);
                        if ($transcode_settings) {
                            $type = $transcode_settings['format'];
                            $transcode = true;
                        }
                    }
                }

            }
        }

        if ($transcode) {
            $url .= '&transcode_to=' . $type;
        } else {
            $type = $ftype;
        }
        //$url .= "&content_length=required";
    } else {
		$idsjs .= "artistids[" . $i . "] = ''; albumids[" . $i . "] = ''; songids[" . $i . "] = '';";
        $ext = pathinfo($url, PATHINFO_EXTENSION);
        $type = $ext ?: $ftype;

        // Radio streams
        if ($item->type == "radio") {
            $radios[] = $item;
        }
    }

    $jtype = ($type == "ogg" || $type == "flac") ? "oga" : $type;

    if (!in_array($jtype, $jtypes)) {
        $jtypes[] = $jtype;
    }
    $playlistjs .= $jtype.': "' . $url;
    $playlistjs .= '",' . "\n";
    $playlistjs .= 'poster: "' . $item->image_url . (!$iframed ? '&thumb=4' : '') . '" }' . "\n";
	
	$i++;
}
?>
<script language="javascript" type="text/javascript">
var artistids = new Array();
var albumids = new Array();
var songids = new Array();
<?php echo $idsjs; ?>
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
if (Config::get('song_page_title') && $iframed) {
    echo "window.parent.document.title = '" . addslashes(Config::get('site_title')) . "';";
}
?>
    }
    ff.setAttribute('src', '');
    return false;
}
</script>
<?php
if ($i == 1 && count($radios) > 0 && Config::get('webplayer_flash')) {
    // Special stuff for web radio (to better handle Icecast/Shoutcast metadata ...)
    $radio = $radios[0];
    require_once Config::get('prefix') . '/templates/show_radio_player.inc.php';
} else {
    require_once Config::get('prefix') . '/templates/show_html5_player.inc.php';
}
?>
