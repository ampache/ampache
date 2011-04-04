<?php
/* vim:set tabstop=8 softtabstop=8 shiftwidth=8 noexpandtab: */
/**
 * Show Playlist Row
 *
 *
 * LICENSE: GNU General Public License, version 2 (GPLv2)
 * Copyright (c) 2001 - 2011 Ampache.org All Rights Reserved
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
 * @package	Ampache
 * @copyright	2001 - 2011 Ampache.org
 * @license	http://opensource.org/licenses/gpl-2.0 GPLv2
 * @link	http://www.ampache.org/
 */

?>
<td class="cel_add">
	<?php echo Ajax::button('?action=basket&type=playlist&id=' . $playlist->id,'add',_('Add'),'add_playlist_' . $playlist->id); ?>
	<?php echo Ajax::button('?action=basket&type=playlist_random&id=' . $playlist->id,'random',_('Random'),'random_playlist_' . $playlist->id); ?>
</td>
<td class="cel_playlist"><?php echo $playlist->f_link; ?></td>
<td class="cel_type"><?php echo $playlist->f_type; ?></td>
<td class="cel_songs"><?php echo $count; ?></td>
<td class="cel_owner"><?php echo scrub_out($playlist->f_user); ?></td>
<td class="cel_action">
        <?php if (Access::check_function('batch_download')) { ?>
                <a href="<?php echo Config::get('web_path'); ?>/batch.php?action=playlist&amp;id=<?php echo $playlist->id; ?>">
                        <?php echo get_user_icon('batch_download',_('Batch Download')); ?>
                </a>
        <?php } ?>
	<?php if ($playlist->has_access()) { ?>
		<?php echo Ajax::button('?action=show_edit_object&type=playlist_row&id=' . $playlist->id,'edit',_('Edit'),'edit_playlist_' . $playlist->id); ?>
		<?php echo Ajax::button('?page=browse&action=delete_object&type=playlist&id=' . $playlist->id,'delete',_('Delete'),'delete_playlist_' . $playlist->id); ?>
	<?php } ?>
</td>
