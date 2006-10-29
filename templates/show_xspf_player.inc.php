<?php
/*

 Copyright (c) 2001 - 2006 Ampache.org
 All rights reserved.

 This program is free software; you can redistribute it and/or
 modify it under the terms of the GNU General Public License v2
 as published by the Free Software Foundation

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with this program; if not, write to the Free Software
 Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.

*/
?>

<html>
<head><title>Ampache XSPF Player</title></head>
<body style="margin:0px; padding:0px; border:0px;">
<object classid="clsid:d27cdb6e-ae6d-11cf-96b8-444553540000" codebase="http://fpdownload.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=7,0,0,0" width="400" height="168">
<param name="allowScriptAccess" value="sameDomain"/>
<param name="movie" value="<?php echo conf('web_path'); ?>/modules/flash/xspf_player.swf?playlist_url=<?php echo conf('web_path'); ?>/song.php<?php echo $play_info; ?>&autoplay=true&autoload=true"/>
<param name="quality" value="high"/>
<param name="bgcolor" value="#E6E6E6"/>
<embed src="<?php echo conf('web_path'); ?>/modules/flash/xspf_player.swf?playlist_url=<?php echo conf('web_path'); ?>/song.php<?php echo $play_info; ?>&autoplay=true&autoload=true" quality="high" bgcolor="#E6E6E6" name="xspf_player" allowscriptaccess="sameDomain" type="application/x-shockwave-flash" pluginspage="http://www.macromedia.com/go/getflashplayer" align="center" height="168" width="400"></embed>
</object>
</body>
</html>
