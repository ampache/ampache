<?php
/*

 Copyright (c) 2001 - 2006 Ampache.org
 All rights reserved.

 This program is free software; you can redistribute it and/or
 modify it under the terms of the GNU General Public License v2
 as published by the Free Software Foundation

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with this program; if not, write to the Free Software
 Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.

*/

$htmllang = str_replace("_","-",conf('lang'));
$location = get_location();

show_template('header'); 

/**
 * Check for the refresh mojo, if it's there then require the
 * refresh_javascript include. Must be greater then 5, I'm not
 * going to let them break their servers
 */
if (conf('refresh_limit') > 5) {
        $ajax_url = conf('ajax_url') . '?action=reload_np_tv' . conf('ajax_info');
        /* Can't have the &amp; stuff in the Javascript */
        $ajax_url = str_replace("&amp;","&",$ajax_url);
        require_once(conf('prefix') . '/templates/javascript_refresh.inc.php');
}

?>
<!-- Left Col -->
<div id="tv_left">
<?php show_box_top(_('Controls')); ?>
<!-- Control DIV -->
<div id="tv_control">
<?php 
/* If they are a admin */
if ($GLOBALS['user']->has_access(100)) { 
	require_once(conf('prefix') . '/templates/show_tv_adminctl.inc.php');	
} 
/* Else normal User */
else { 

} 
?>
</div>
<?php show_box_bottom(); ?>
<?php show_box_top(_('Current Playlist')); ?>
<div id="tv_playlist">
<?php require_once(conf('prefix') . '/templates/show_tv_playlist.inc.php'); ?>
</div>
<?php show_box_bottom(); ?>
<!-- End of Left -->
</div>
<?php show_box_top(_('Now Playing')); ?>
<div id="tv_np">
<?php require_once(conf('prefix') . '/templates/show_tv_nowplaying.inc.php'); ?>
</div>
<?php show_box_bottom(); ?>
