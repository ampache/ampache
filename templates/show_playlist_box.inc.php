<?php
/*

 Copyright (c) 2001 - 2006 Ampache.org
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

<table class="text-box">
<tr><td>
	<span class="header1"><?php echo _('Playlist Actions'); ?></span><br />
	&nbsp;&nbsp;&nbsp;<a href="<?php echo conf('web_path'); ?>/playlist.php?action=new"><?php echo _('Create New Playlist'); ?></a><br />
	&nbsp;&nbsp;&nbsp;<a href="<?php echo conf('web_path'); ?>/playlist.php"><?php echo _('View All Playlists'); ?></a><br />
	&nbsp;&nbsp;&nbsp;<a href="<?php echo conf('web_path'); ?>/playlist.php?action=show_import_playlist"><?php echo _('Import From File'); ?></a><br />
</td></tr>
</table>
