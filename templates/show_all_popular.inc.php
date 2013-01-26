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
<table class="tabledata">
<tr>
    <td valign="top" >
        <?php show_info_box(T_('Most Popular Artists'), 'artist', $artists); ?>
    </td>
    <td valign="top">
        <?php show_info_box(T_('Most Popular Albums'), '', $albums); ?>
    </td>
    <td valign="top">
        <?php show_info_box(T_('Most Popular Genres'), '', $genres); ?>
    </td>
</tr>
<tr><td colspan="2">&nbsp;</td></tr>
<tr>
    <td valign="top">
        <?php show_info_box(T_('Most Popular Songs'), 'song', $songs); ?>
    </td>
    <td valign="top">
        <?php show_info_box(T_('Most Popular Live Streams'),'live_stream',$live_streams); ?>
    </td>
    <td valign="top">
        <?php show_info_box(T_('Most Popular Tags'),'tags',$tags); ?>
    </td>
</tr>
<tr><td colspan="2">&nbsp;</td></tr>
</table>
