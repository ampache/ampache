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
    <li><a href="<?php echo $web_path; ?>"><img src="<?php echo $web_path; ?>/images/topmenu-home.png" title="<?php echo T_('Home'); ?>" /></a></li>
    <li><a href="<?php echo $web_path; ?>/browse.php?action=artist"><img src="<?php echo $web_path; ?>/images/topmenu-music.png" title="<?php echo T_('Artists'); ?>" /></a></li>
    <li><a href="<?php echo $web_path; ?>/browse.php?action=playlist"><img src="<?php echo $web_path; ?>/images/topmenu-playlist.png" title="<?php echo T_('Playlists'); ?>" /></a></li>
    <li><a href="<?php echo $web_path; ?>/stats.php?action=userflag"><img src="<?php echo $web_path; ?>/images/topmenu-favorite.png" title="<?php echo T_('Favorites'); ?>" /></a></li>
</ul>
