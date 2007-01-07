<?php
/*

 Copyright (c) 2001 - 2006 Ampache.org
 All rights reserved.

 This program is free software; you can redistribute it and/or
 modify it under the terms of the GNU General Public License v2
 as published by the Free Software Foundation.

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
 * that have ['title'] ['url'] ['active'] and ['cssclass'] url assumes no conf('web_path')
 */

$admin_items[] = array('title'=>_('Users'),'url'=>'admin/users.php','active'=>$location['page'], 'cssclass'=>'sidebar_admin_users');
$admin_items[] = array('title'=>_('Mail Users'),'url'=>'admin/mail.php','active'=>$location['page'], 'cssclass'=>'sidebar_admin_mail_users');
$admin_items[] = array('title'=>_('Catalog'),'url'=>'admin/index.php','active'=>$location['page'], 'cssclass'=>'sidebar_admin_catalog');
$admin_items[] = array('title'=>_('Config'),'url'=>'admin/preferences.php','active'=>$location['page'], 'cssclass'=>'sidebar_admin_config');
$admin_items[] = array('title'=>_('Access List'),'url'=>'admin/access.php','active'=>$location['page'], 'cssclass'=>'sidebar_admin_access_list');

$browse_items[] = array('title'=>_("Albums"),'url'=>'albums.php','active'=>$location['page'], 'cssclass'=>'sidebar_browse_albums');
$browse_items[] = array('title'=>_("Artists"),'url'=>'artists.php','active'=>$location['page'], 'cssclass'=>'sidebar_browse_artists');
$browse_items[] = array('title'=>_("Genre"),'url'=>'browse.php?action=genre','active'=>$location['page'], 'cssclass'=>'sidebar_browse_genre');
$browse_items[] = array('title'=>_('Song Title'),'url'=>'browse.php?action=song_title','active'=>$location['page'], 'cssclass'=>'sidebar_browse_song_title');

$web_path = conf('web_path');

?>
<h3>&nbsp;</h3>
<ul id="navlist">
	<li id="sidebar_home"<?php
                if ($location['page'] == "index.php"){
                    echo " class=\"activetopmenu\" ";
                    }?>>
		<a href="<?php echo $web_path; ?>/index.php"><?php echo _('Home'); ?></a>
	</li>
<?php if ($GLOBALS['user']->has_access(100)) { ?>
	<li id="sidebar_admin"<?php
                if ($location['page'] == 'admin/index.php' ||
                    $location['page'] == 'admin/users.php' ||
                    $location['page'] == 'admin/mail.php' ||
                    $location['page'] == 'admin/catalog.php' ||
                    $location['page'] == 'admin/preferences.php' ||
                    $location['page'] == 'admin/access.php' ){
                    echo " class=\"activetopmenu\" ";
                    }?>>
		<a href="<?php echo $web_path; ?>/admin/index.php"><?php echo _('Admin'); ?></a>
	<?php
		if ($GLOBALS['theme']['submenu'] != 'simple' AND $GLOBALS['theme']['submenu'] != 'full') {
			show_submenu($admin_items); 
			echo "\t</li>\n";
		} 
		else { 
			if ($location['section'] == 'admin' || $GLOBALS['theme']['submenu'] == 'full') {
				echo "\t</li>\n";
				show_submenu($admin_items);
			} 
		} // end if browse sub menu
} // end if access 
	?>
	
	<li id="sidebar_prefs"<?php
                if ($location['page'] == "preferences.php" ){
                    echo " class=\"activetopmenu\" ";
                    }?>>
		<a href="<?php echo $web_path; ?>/preferences.php"><?php echo _('Preferences'); ?></a>
	</li>
	<li id="sidebar_browse"<?php
                if ($location['page'] == "browse.php" ||
                    $location['page'] == "artists.php" ||
                    $location['page'] == "albums.php" ){
                    echo " class=\"activetopmenu\" ";
                    }?>>
		<a href="<?php echo $web_path; ?>/browse.php"><?php echo _('Browse'); ?></a> 
		<?php 
		if ($GLOBALS['theme']['submenu'] != 'simple' AND $GLOBALS['theme']['submenu'] != 'full') { 
			show_submenu($browse_items);
			echo "\t</li>\n";
		}
		else { 
			if ($location['section'] == 'browse' || $GLOBALS['theme']['submenu'] == 'full') { 
				echo "\t</li>\n";
				show_submenu($browse_items);
			}
		}
		?>
	<li id="sidebar_plists"<?php
                if ($location['page'] == "playlist.php"){
                    echo " class=\"activetopmenu\" ";
                    }?>>
		<a href="<?php echo $web_path; ?>/playlist.php"><?php echo _('Playlists'); ?></a>
	</li>
	<li id="sidebar_stats"<?php
                if ($location['page'] == "stats.php"){
                    echo " class=\"activetopmenu\" ";
                    }?>>
		<a href="<?php echo $web_path; ?>/stats.php"><?php echo _('Statistics'); ?></a>
	</li>
	<li id="sidebar_search"<?php
                if ($location['page'] == "search.php"){
                    echo " class=\"activetopmenu\" ";
                    }?>>
		<a href="<?php echo $web_path; ?>/search.php"><?php echo _('Search'); ?></a>
	</li>
<?php if ($GLOBALS['theme']['orientation'] != 'horizontal') { ?>
	<li id="sidebar_subsearch">
		<form name="sub_search" method="post" action="<?php echo $web_path; ?>/search.php" enctype="multipart/form-data" style="Display:inline">
		<input type="text" name="search_string" value="" size="5" />
		<input class="smallbutton" type="submit" value="<?php echo _('Search'); ?>" /> 
		<input type="hidden" name="action" value="quick_search" />
		<input type="hidden" name="method" value="fuzzy" />
		<input type="hidden" name="object_type" value="song" />
		</form>
	</li>
<?php } // end if ($GLOBALS['theme']['orientation'] != 'horizontal')?>
	<li id="sidebar_random"<?php
                if ($location['page'] == "randomplay.php"){
                    echo " class=\"activetopmenu\" ";
                    }?>>
		<a href="<?php echo $web_path; ?>/randomplay.php"><?php echo _('Random'); ?></a>
	</li>
<?php if ($GLOBALS['theme']['orientation'] != 'horizontal') { ?> 
	<li id="sidebar_form">
		<form name="sub_random" method="post" enctype="multipart/form-data" action="<?php echo $web_path; ?>/song.php?action=random&amp;method=stream" style="Display:inline">
		<select name="random" >
			<option value="1">1</option>
			<option value="5" selected="selected">5</option>
			<option value="10">10</option>
			<option value="20">20</option>
			<option value="30">30</option>
			<option value="50">50</option>
			<option value="100">100</option>
			<option value="500">500</option>
			<option value="1000">1000</option>
			<option value="-1"><?php echo _('All'); ?></option>
		</select>
		<?php show_genre_pulldown('genre','','','13',''); ?>
					<br />  
		<select name="random_type" >
			<option value="Songs"><?php echo _('Songs'); ?></option>
			<option value="length"><?php echo _('Minutes'); ?></option>
			<option value="full_artist"><?php echo _('Artists'); ?></option>
			<option value="full_album"><?php echo _('Albums'); ?></option>
			<option value="unplayed"><?php echo _('Less Played'); ?></option>
		</select>
		<br /> 
		<?php show_catalog_pulldown('catalog',''); ?>
		<br />
		<input class="smallbutton" type="submit" value="<?php echo _('Enqueue'); ?>" />
		</form>
	</li>
<?php } // end if ($GLOBALS['theme']['orientation'] != 'horizontal') ?> 
<?php if ($GLOBALS['user']->prefs['localplay_level'] > 0 AND conf('allow_localplay_playback')) { ?>
	<li id="sidebar_localplay">
		<a href="<?php echo $web_path; ?>/localplay.php"><?php echo _('Localplay'); ?></a>
	</li>
<?php if ($GLOBALS['theme']['orientation'] != 'horizontal') { ?>
	<li id="sidebar_localplay_ctrl">
		<?php require_once(conf('prefix') . '/templates/show_localplay_control.inc.php'); ?>
	</li>
<?php } // if horizontal orientation ?>
<?php } // if localplay access ?>
	<li>
		<?php 
			$required_info  = conf('ajax_info');
			$ajax_url       = conf('ajax_url');
		?>
		<?php require_once(conf('prefix') . '/templates/show_playtype_switch.inc.php'); ?>
	</li>
<?php if (conf('allow_democratic_playback')) { ?>
	<li>
		<a href="<?php echo $web_path; ?>/tv.php"><?php echo _('Democratic View'); ?></a>
	</li>
<?php } // if democratic play ?>
<?php if (conf('use_auth')) { ?>
	<li id="sidebar_logout"><a href="<?php echo $web_path; ?>/logout.php"><?php echo _('Logout'); ?></a></li>
<?php } // end (conf('use_auth'))?>
</ul>
