<?php
/*

 Copyright (c) 2001 - 2007 Ampache.org
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
<body style="margin:0px; padding:0px; border:0px; background-color: #000000">
<script language=JavaScript>
<!--
//Disable right mouse click Script to hide the source url for the flash player it prevents ripping music a bit.
//When used together with locked songs this will help just a bit more.
function clickIE4(){
if (event.button==2){
return false;
}
}

function clickNS4(e){
if (document.layers||document.getElementById&&!document.all){
if (e.which==2||e.which==3){
return false;
}
}
}

if (document.layers){
document.captureEvents(Event.MOUSEDOWN);
document.onmousedown=clickNS4;
}
else if (document.all&&!document.getElementById){
document.onmousedown=clickIE4;
}

document.oncontextmenu=new Function("return false")

// --> 
</script>
<script type="text/javascript">

<div id="mp3player">
<object classid="clsid:d27cdb6e-ae6d-11cf-96b8-444553540000" codebase="http://fpdownload.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=7,0,0,0" width="400" height="170" id="xspf_player" align="middle">
	<param name="allowScriptAccess" value="sameDomain" />
	<param name="movie" value="xspf_player.swf" />
	<param name="quality" value="high" />
	<param name="bgcolor" value="#ffffff" />
	<embed src="<?php echo Config::get('web_path'); ?>/modules/flash/xspf_player.swf?autoplay=true&playlist_url=<?php echo $play_url; ?>" quality="high" bgcolor="#ffffff" width="400" height="170" name="xspf_player" align="middle" allowScriptAccess="sameDomain" type="application/x-shockwave-flash" pluginspage="http://www.macromedia.com/go/getflashplayer" />
</object>
</div>

</body>
</html>
