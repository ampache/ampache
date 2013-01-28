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
<title><?php echo Config::get('site_title'); ?></title>
<link rel="stylesheet" href="<?php echo Config::get('web_path'); ?>/templates/html5_player.css" type="text/css" media="screen" />
<link rel="stylesheet" href="<?php echo Config::get('web_path').Config::get('theme_path').'/templates/'.'default.css'; ?>" type="text/css" media="screen" />
<script src="<?php echo Config::get('web_path'); ?>/modules/prototype/prototype.js" language="javascript" type="text/javascript"></script>
<script type="text/javascript">
var playlist_items={
<?php
$i = 0;
$playlist = new Stream_Playlist(scrub_in($_REQUEST['playlist_id']));
foreach($playlist->urls as $item)
{
    echo ($i++ > 0 ? ',' : '') . $i . ': {';
    foreach(array('id', 'title', 'type', 'album', 'time', 'author', 'info_url') as $member)
    {
        echo $member . ': "' . addslashes($item->$member) . '",';
    }
    echo 'play_url: "' . $item->url . '",';
    echo 'albumart_url: "' . $item->image_url . '",';
    echo 'media_type: "' . $type . '"}';
}
?>
};
</script>
<script src="<?php echo Config::get('web_path'); ?>/lib/javascript/html5_player.js" language="javascript" type="text/javascript"></script>
</head>
<body id="html5_player">
    <div id="player">
        <div id="albumart"></div>
        <div id="search">
            <input id="input_search" type="text" value="<?php echo T_('search') ?>"/>
            <div id="clear_search"><?php echo T_('clear') ?></div>
        </div>
        <div id="title"><?php echo T_('Loading...') ?></div>
        <div id="album"><?php echo T_('Loading...') ?></div>
        <div id="artist"><?php echo T_('Loading...') ?></div>
        <div id="progress_text"><?php echo T_('Loading...') ?></div>
        <div id="stop"><?php echo T_('Stop') ?></div>
        <div id="play"><?php echo T_('Play') ?></div>
        <div id="pause"><?php echo T_('Pause') ?></div>
        <div id="previous"><?php echo T_('Previous') ?></div>
        <div id="next"><?php echo T_('Next') ?></div>
    </div>
    <div>
        <ul id="playlist">
        </ul>
    </div>
</body>
</html>
