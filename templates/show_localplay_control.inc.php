<?php
/*

 Copyright (c) Ampache.org
 All rights reserved.

 This program is free software; you can redistribute it and/or
 modify it under the terms of the GNU General Public License
 as published by the Free Software Foundation; either version 2
 of the License, or (at your option) any later version.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with this program; if not, write to the Free Software
 Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.

*/

?>
<div id="localplay-control">
<?php echo Ajax::button('?page=localplay&action=command&command=prev','prev',_('Previous'),'localplay_control_previous'); ?>
<?php echo Ajax::button('?page=localplay&action=command&command=stop','stop',_('Stop'),'localplay_control_stop'); ?>
<?php echo Ajax::button('?page=localplay&action=command&command=pause','pause',_('Pause'),'localplay_control_pause'); ?>
<?php echo Ajax::button('?page=localplay&action=command&command=play','play',_('Play'),'localplay_control_play'); ?>
<?php echo Ajax::button('?page=localplay&action=command&command=next','next',_('Next'),'localplay_control_next'); ?>
</div>
