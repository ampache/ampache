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
<div id="localplay-control">
<?php echo Ajax::button('?page=localplay&action=command&command=prev','prev', T_('Previous'),'localplay_control_previous'); ?>
<?php echo Ajax::button('?page=localplay&action=command&command=stop','stop', T_('Stop'),'localplay_control_stop'); ?>
<?php echo Ajax::button('?page=localplay&action=command&command=pause','pause', T_('Pause'),'localplay_control_pause'); ?>
<?php echo Ajax::button('?page=localplay&action=command&command=play','play', T_('Play'),'localplay_control_play'); ?>
<?php echo Ajax::button('?page=localplay&action=command&command=next','next', T_('Next'),'localplay_control_next'); ?>
</div>
