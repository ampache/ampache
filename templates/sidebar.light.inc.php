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

$web_path = AmpConfig::get('web_path');
?>

<ul id="sidebar-light">
    <li><?php echo UI::create_link('content', 'index', array('action' => 'currently_playing'), '<img src="' . $web_path . '/images/topmenu-home.png" title="' . T_('Home') . '"/>', 'light_index_currently_playing'); ?></li>
    <li><?php echo UI::create_link('content', 'browse', array('action' => 'artist'), '<img src="' . $web_path . '/images/topmenu-music.png" title="' . T_('Artists') . '"/>', 'light_browse_artist'); ?></li>
    <li><?php echo UI::create_link('content', 'browse', array('action' => 'playlist'), '<img src="' . $web_path . '/images/topmenu-playlist.png" title="' . T_('Playlists') . '"/>', 'light_browse_playlist'); ?></li>
    <li><?php echo UI::create_link('content', 'stats', array('action' => 'userflag'), '<img src="' . $web_path . '/images/topmenu-favorite.png" title="' . T_('Favorites') . '"/>', 'light_stats_userflag'); ?></li>
</ul>
