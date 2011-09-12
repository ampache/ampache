<?php
/* vim:set tabstop=8 softtabstop=8 shiftwidth=8 noexpandtab: */
/**
 * Show Democratic
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

/* HINT: Democratic Name */
$string = $democratic->is_enabled() ? sprintf(_('%s Playlist') ,$democratic->name) : _('Democratic Playlist');
show_box_top($string , 'info-box');
?>
<div id="information_actions">
<ul>
<?php if ($democratic->is_enabled()) { ?>
<li>
	<?php echo _('Cooldown'); ?>:<?php echo $democratic->f_cooldown; ?>
</li>
<?php } ?>
<?php if (Access::check('interface','75')) { ?>
<li>
	<a href="<?php echo Config::get('web_path'); ?>/democratic.php?action=manage"><?php echo get_user_icon('server_lightning',_('Configure Democratic Playlist')); ?></a>
	<?php echo _('Configure Democratic Playlist'); ?>
</li>
<?php if ($democratic->is_enabled()) { ?>
<li>
	<?php echo Ajax::button('?page=democratic&action=send_playlist&democratic_id=' . scrub_out($democratic->id),'all',_('Play'),'play_democratic'); ?>
	<?php echo _('Play Democratic Playlist'); ?>
</li>
<li>
	<?php echo Ajax::button('?page=democratic&action=clear_playlist&democratic_id=' . scrub_out($democratic->id),'delete',_('Clear Playlist'),'clear_democratic'); ?>
	<?php echo _('Clear Playlist'); ?>
</li>
<?php } ?>
<?php } ?>
</ul>

</div>
<?php show_box_bottom(); ?>
