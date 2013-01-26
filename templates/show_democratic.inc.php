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

$string = $democratic->is_enabled() ? sprintf(T_('%s Playlist') ,$democratic->name) : T_('Democratic Playlist');
UI::show_box_top($string , 'info-box');
?>
<div id="information_actions">
<ul>
<?php if ($democratic->is_enabled()) { ?>
<li>
    <?php echo T_('Cooldown'); ?>:<?php echo $democratic->f_cooldown; ?>
</li>
<?php } ?>
<?php if (Access::check('interface','75')) { ?>
<li>
    <a href="<?php echo Config::get('web_path'); ?>/democratic.php?action=manage"><?php echo UI::get_icon('server_lightning', T_('Configure Democratic Playlist')); ?></a>
    <?php echo T_('Configure Democratic Playlist'); ?>
</li>
<?php if ($democratic->is_enabled()) { ?>
<li>
    <?php echo Ajax::button('?page=democratic&action=send_playlist&democratic_id=' . scrub_out($democratic->id),'all', T_('Play'),'play_democratic'); ?>
    <?php echo T_('Play Democratic Playlist'); ?>
</li>
<li>
    <?php echo Ajax::button('?page=democratic&action=clear_playlist&democratic_id=' . scrub_out($democratic->id),'delete', T_('Clear Playlist'),'clear_democratic'); ?>
    <?php echo T_('Clear Playlist'); ?>
</li>
<?php } ?>
<?php } ?>
</ul>

</div>
<?php UI::show_box_bottom(); ?>
