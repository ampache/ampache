<?php
/* vim:set tabstop=4 softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU General Public License, version 2 (GPLv2)
 * Copyright 2015 Ampache.org
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
<meta property="og:title" content="<?php echo AmpConfig::get('site_title'); ?>" />
<meta property="og:image" content="<?php echo AmpConfig::get('web_path'); ?>/themes/reborn/images/ampache.png"/>
<meta property="og:description" content="A web based audio/video streaming application and file manager allowing you to access your music & videos from anywhere, using almost any internet enabled device." />
<meta property="og:site_name" content="Ampache"/>
<?php
if (!$is_share) {
    $playlist = new Stream_Playlist(scrub_in($_REQUEST['playlist_id']));
}

$isRadio = false;
$isVideo = false;
$radio = null;
if (isset($playlist)) {
    if (WebPlayer::is_playlist_radio($playlist)) {
        // Special stuff for web radio (to better handle Icecast/Shoutcast metadata ...)
        // No special stuff for now
        $isRadio = true;
        $radio = $playlist->urls[0];
    } else {
        $isVideo = WebPlayer::is_playlist_video($playlist);
    }
}
require_once AmpConfig::get('prefix') . '/templates/show_html5_player.inc.php';
?>
