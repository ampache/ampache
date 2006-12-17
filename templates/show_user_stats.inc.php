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
<?php show_box_top($working_user->fullname . ' ' . _('Favorites')); ?>
<table class="tabledata">
<tr>
	<td valign="top">
	<?php 
	if (count($favorite_artists)) { 
		$items = $working_user->format_favorites($favorite_artists); 
		show_info_box(_('Favorite Artists'),'artist',$items); 
	}
	else { 
		echo "<span class=\"error\">" . _('Not Enough Data') . "</span>";
	}
	?>
	</td>
	<td valign="top">
	<?php
	if (count($favorite_albums)) { 
		$items = $working_user->format_favorites($favorite_albums);
		show_info_box(_('Favorite Albums'),'album',$items);
	}
	else { 
		echo "<span class=\"error\">" . _('Not Enough Data') . "</span>";
	}
	?>
	</td>
	<td valign="top">
	<?php
	if (count($favorite_songs)) { 
		$items = $working_user->format_favorites($favorite_songs); 
		show_info_box(_('Favorite Songs'),'your_song',$items); 
	}
	else { 
		echo "<span class=\"error\">" . _('Not Enough Data') . "</span>";
	}
	?>
	</td>
</tr>
</table>
<?php show_box_bottom(); ?>
