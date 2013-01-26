<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU General Public License, version 2 (GPLv2)
 * Copyright 2001 - 2013 Ampache.org
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License v2
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 *
 */
?>
<td colspan="5">
<form method="post" id="edit_artist_<?php echo $artist->id; ?>">
<table class="inline-edit" cellpadding="3" cellspacing="0">
<tr>
<td>
    <input type="text" name="name" value="<?php echo scrub_out($artist->f_full_name); ?>" />
</td>
<td>
    <input type="hidden" name="id" value="<?php echo $artist->id; ?>" />
    <input type="hidden" name="type" value="artist_row" />
    <?php echo Ajax::button('?action=edit_object&id=' . $artist->id . '&type=artist_row','download', T_('Save Changes'),'save_artist_' . $artist->id,'edit_artist_' . $artist->id); ?>

</tr>
</table>
</form>
</td>
