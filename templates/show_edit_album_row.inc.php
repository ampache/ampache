<?php
/*

 Copyright (c) 2001 - 2007 Ampache.org
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
<td colspan="6">
<form method="post" id="edit_album_<?php echo $album->id; ?>">
<input type="input" name="name" value="<?php echo scrub_out($album->name); ?>" />
<input type="hidden" name="id" value="<?php echo $album->id; ?>" />
<input type="hidden" name="type" value="album" />
<span onclick="ajaxPost('<?php echo Config::get('ajax_url'); ?>?action=edit_object&amp;id=<?php echo $album->id; ?>&amp;type=album','edit_album_<?php echo $album->id; ?>');">
	<img src="<?php echo Config::get('web_path'); ?>/images/icon_download.png">
</span>
</form>
</td>
</td>
