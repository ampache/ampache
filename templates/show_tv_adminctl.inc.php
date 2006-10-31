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
<h3><?php echo _('Admin Controls'); ?></h3>
<?php if (!$tmp_playlist->vote_active()) { ?>
<form id="form_playlist" method="post" action="<?php echo conf('web_path'); ?>/tv.php" enctype="multipart/form-data" >
<?php echo _('Base Playlist'); ?>:
<?php show_playlist_dropdown(); ?>
<input type="hidden" name="action" value="create_playlist" />
<input type="submit" value="<?php echo _('Activate'); ?>" />
</form>
<?php } else { ?>
<?php echo _('Democratic Play Active'); ?>
<?php } ?>
