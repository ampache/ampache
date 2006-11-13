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
<body style="margin:0px; padding:0px; border:0px; background-color: #000000">
<div id="mp3player">
<script type="text/javascript" src="<?php echo conf('web_path'); ?>/modules/flash/swfobject.js"></script>
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
<!--
var flashObj = new SWFObject ("<?php echo conf('web_path'); ?>/modules/flash/xspf_player.swf?action=play&playlist=<?php echo conf('web_path'); ?>/modules/flash/xspf_player.php<?php echo $play_info; ?>&folder=<?php echo conf('web_path'); ?>/modules/flash/&textcolor=033066&color=E6E6E6&loop=playlist&lma=yes&viewinfo=true&vol=30&display=1@. - @2@ - @", "FMP3", "350", "300", 7, "#000000", true);
flashObj.write ("mp3player");
// -->
</script>
</div>

</body>
</html>
