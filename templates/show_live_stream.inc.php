<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU General Public License, version 2 (GPLv2)
 * Copyright 2001 - 2014 Ampache.org
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
<?php if (Access::check('interface', '50')) { ?>
<?php UI::show_box_top(T_('Manage Radio Stations'),'info-box'); ?>
<div id="information_actions">
<ul>
<li>
    <a href="<?php echo AmpConfig::get('web_path'); ?>/radio.php?action=show_create"><?php echo UI::get_icon('add', T_('Add')); ?> <?php echo T_('Add Radio Station'); ?></a>
</li>
</ul>
</div>
<?php UI::show_box_bottom(); ?>
<?php } ?>
