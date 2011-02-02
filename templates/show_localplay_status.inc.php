<?php
/* vim:set tabstop=8 softtabstop=8 shiftwidth=8 noexpandtab: */
/*
 * Show Localplay Status
 *
 * PHP version 5
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
 * @category	Template
 * @package	Template
 * @author	Karl Vollmer <vollmer@ampache.org>
 * @copyright	2001 - 2011 Ampache.org
 * @license	http://opensource.org/licenses/gpl-2.0 GPLv2
 * @version	PHP 5.2
 * @link	http://www.ampache.org/
 * @since	File available since Release 1.0
 */

$status = $localplay->status();
$now_playing = $status['track_title'] ? $status['track_title'] . ' - ' . $status['track_album'] . ' - ' . $status['track_artist'] : '';
?>
<?php Ajax::start_container('localplay_status'); ?>
<?php show_box_top(_('Localplay Control') . ' - '. strtoupper($localplay->type)); ?>
<?php echo _('Now Playing'); ?>:<i><?php echo $now_playing; ?></i>
<div id="information_actions">
<ul>
<li>
<?php echo Ajax::button('?page=localplay&action=command&command=volume_mute','volumemute',_('Mute'),'localplay_mute'); ?>
<?php echo Ajax::button('?page=localplay&action=command&command=volume_down','volumedn',_('Decrease Volume'),'localplay_volume_dn'); ?>
<?php echo Ajax::button('?page=localplay&action=command&command=volume_up','volumeup',_('Increase Volume'),'localplay_volume_up'); ?>
<?php echo _('Volume'); ?>:<?php echo $status['volume']; ?>%
</li>
<li>
	<?php echo print_bool($status['repeat']); ?> |
	<?php echo Ajax::text('?page=localplay&action=repeat&value=' . invert_bool($status['repeat']), print_bool(invert_bool($status['repeat'])), 'localplay_repeat'); ?>
	<?php echo _('Repeat'); ?>
</li>
<li>
	<?php echo print_bool($status['random']); ?> |
	<?php echo Ajax::text('?page=localplay&action=random&value=' . invert_bool($status['random']), print_bool(invert_bool($status['random'])), 'localplay_random'); ?>
	<?php echo _('Random'); ?>
</li>
<li>
	<?php echo Ajax::button('?page=localplay&action=command&command=delete_all','delete',_('Clear Playlist'),'localplay_clear_all'); ?><?php echo _('Clear Playlist'); ?>
</li>
</ul>
</div>
<?php show_box_bottom(); ?>
<?php Ajax::end_container(); ?>
