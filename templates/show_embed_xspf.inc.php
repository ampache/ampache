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
<span class="xspf_player">
<?php
show_box_top(_('XSPF Player'));
?>
<div id="mp3player">
<script type="text/javascript" src="<?php echo conf('web_path'); ?>/modules/flash/swfobject.js"></script>
<script type="text/javascript">
<!--
var flashObj = new SWFObject ("<?php echo conf('web_path'); ?>/modules/flash/XSPF_RadioV.swf?action=play&playlist=<?php echo conf('web_path'); ?>/modules/flash/xspf_player.php?tmp_id=<?php echo $_REQUEST['play_info']; ?>&folder=<?php echo conf('web_path'); ?>/modules/flash/&textcolor=033066&color=E6E6E6&loop=playlist&lma=yes&viewinfo=true&vol=30&display=1@. - @2@ - @", "FMP3", "270", "190", 7, "#FFFFFF", true);
flashObj.write ("mp3player");
// -->
</script>
</div>
<?php
show_box_bottom();
?>
</span>