<?php
/*
 
 Copyright (c) 2001 - 2006 Ampache.org
 All rights reserved. 

 This program is free software; you can redistribute it and/or
 modify it under the terms of the GNU General Public License v2
 as published by the Free Software Foundation.
 
 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with this program; if not, write to the Free Software
 Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 
*/

?>
<table class="tabledata">
<tr>
	<td valign="top" >
		<?php show_info_box(_('Most Popular Artists'), 'artist', $artists); ?>
	</td>
	<td valign="top">
		<?php show_info_box(_('Most Popular Albums'), '', $albums); ?>
	</td>
	<td valign="top">
		<?php show_info_box(_('Most Popular Genres'), '', $genres); ?>
	</td>
</tr>
<tr><td colspan="2">&nbsp;</td></tr>
<tr>
	<td valign="top">
		<?php show_info_box(_('Most Popular Songs'), 'song', $songs); ?>
	</td>
	<td valign="top">
		<?php show_info_box(_('Most Popular Live Streams'),'live_stream',$live_streams); ?>
	</td>
	<td valign="top">
		<?php show_info_box(_('Most Popular Tags'),'tags',$tags); ?>
	</td>
</tr>
<tr><td colspan="2">&nbsp;</td></tr>
</table>