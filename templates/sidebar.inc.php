<?php
/*

 Copyright (c) 2001 - 2006 Ampache.org
 All rights reserved.

 This program is free software; you can redistribute it and/or
 modify it under the terms of the GNU General Public License
 as published by the Free Software Foundation; either version 2
 of the License, or (at your option) any later version.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with this program; if not, write to the Free Software
 Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.

*/

/**
 * This is kind of the wrong place to do this, but let's define the different submenu's that could possibly be 
 * displayed on this page, this calls the show_submenu($items); function which takes an array of items
 * that have ['title'] ['url'] and ['active'] url assumes no conf('web_path')
 */

$admin_items[] = array('title'=>'Users','url'=>'admin/users.php','active'=>'');
$admin_items[] = array('title'=>'Mail Users','url'=>'admin/mail.php','active'=>'');
$admin_items[] = array('title'=>'Catalog','url'=>'admin/catalog.php','active'=>'');
$admin_items[] = array('title'=>'Site Preferences','url'=>'admin/preferences.php','active'=>'');
$admin_items[] = array('title'=>'Access List','url'=>'admin/access.php','active'=>'');

$browse_items[] = array('title'=>'Albums','url'=>'albums.php','active'=>'');
$browse_items[] = array('title'=>'Artists','url'=>'artists.php','active'=>'');
$browse_items[] = array('title'=>'Genre','url'=>'browse.php?action=genre','active'=>'');
//$browse_items[] = array('title'=>'File','url'=>'files.php','active'=>'');

?>
<div id="navcontainer">
	<ul id="navlist">
		<li id="active">
			<a href="<?php echo conf('web_path'); ?>/index.php" id="current"><?php echo _("Home"); ?></a>
		</li>
		<?php if ($GLOBALS['user']->has_access(100)) { ?>
		<li>
			<a href="<?php echo conf('web_path'); ?>/admin/index.php"><?php echo _("Admin"); ?></a>
		</li>
		<?php if ($location['section'] == 'admin') { show_submenu($admin_items); } ?>
		<? } ?>
		<li>
			<a href="<?php echo conf('web_path'); ?>/preferences.php"><?php echo _("Preferences"); ?></a>
		</li>
		<li>
			<a href="<?php echo conf('web_path'); ?>/browse.php"><?php echo _("Browse"); ?></a>
		</li>
		<?php if ($location['section'] == 'browse') { show_submenu($browse_items); } ?>
		<?php if ($GLOBALS['user']->prefs['upload']) { ?>
		<li>
			<a href="<?php echo conf('web_path'); ?>/upload.php"><?php echo _("Upload"); ?></a>
		</li>
		<?php } ?>
		<li>
			<a href="<?php echo conf('web_path'); ?>/playlist.php"><?php echo _("Playlists"); ?></a>
		</li>
		<?php if ($GLOBALS['user']->prefs['play_type'] == 'mpd') { ?>
		<li>
			<a href="<?php echo conf('web_path'); ?>/mpd.php"><?php echo _("Local Play"); ?></a>
		</li>
		<?php } ?>
		<li>
			<a href="<?php echo conf('web_path'); ?>/search.php"><?php echo _("Search"); ?></a>
		</li>
	</ul>
	<ul class="subnavside">
		<li class="subnavbutton">
			<form name="sub_search" method="post" action="<?php echo conf('web_path'); ?>/search.php" enctype="multipart/form-data" style="Display:inline">
			<input type="text" name="search_string" value="<?php echo scrub_out($_REQUEST['search_string']); ?>" size="8" />
        	        <input class="smallbutton" type="submit" value="<?php echo _("Search"); ?>" />
        	        <input type="hidden" name="action" value="quick_search" />
        	        <input type="hidden" name="method" value="fuzzy" />
        	        <input type="hidden" name="object_type" value="song" />
        	        <input type="hidden" name="search_object[]" value="all" />		
			</form>
		</li>
	</ul>
	<ul>
		<li>
			<a href="<?php echo conf('web_path'); ?>/randomplay.php"><?php echo _("Random Play"); ?></a>
		</li>
	</ul>
	<ul class="subnavside">
		<li class="subnavbutton">
			<form name="random" method="post" enctype="multipart/form-data" action="<?php echo conf('web_path'); ?>/song.php" style="Display:inline">
			<input type="hidden" name="action" value="m3u" />
			<select name="random" style="width:110px;">
				<option value="1">1</option>
				<option value="5">5</option>
				<option value="10">10</option>
				<option value="20">20</option>
				<option value="30">30</option>
				<option value="50">50</option>
				<option value="100">100</option>
				<option value="500">500</option>
				<option value="1000">1000</option>
				<option value="-1"><?php echo _("All"); ?></option>
			</select>
			<br />
			<select name="Quantifier" style="width:110px;">
				<option value="Songs"><?php echo _("Songs"); ?></option>
				<option value="Minutes"><?php echo _("Minutes"); ?></option>
				<option value="Artists"><?php echo _("Artists"); ?></option>
				<option value="Albums"><?php echo _("Albums"); ?></option>
				<option value="Less Played"><?php echo _("Less Played"); ?></option>
			</select>
			<br />
			<?php show_catalog_pulldown('catalog','width:110px;'); ?>
			<br />
			<input type="hidden" name="aaction" value="Play!" />
			<input class="smallbutton" type="submit" name="aaction" value="<?php echo _("Enqueue"); ?>" />
			</form>
		</li>
	</ul>
		<?php if (conf('use_auth')) { ?>
			<ul><li><a href="<?php echo conf('web_path'); ?>/logout.php">Logout</a></li></ul>
		<?php } ?>
	
</div>
