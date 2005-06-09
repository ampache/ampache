<?php
/*

 Copyright (c) 2001 - 2005 Ampache.org
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

/*!
	@header Admin Index
 Do most of the dirty work of displaying the mp3 catalog

*/

require ("../modules/init.php");

$action = scrub_in($_REQUEST['action']);

if (!$user->has_access(100)) { 
	header ("Location: " . conf('web_path') . "/index.php?access=denied");
	exit();
}


// let's set the preferences here so that they take affect on the fly
if ( $action == 'Update Preferences' ) {
	update_site_preferences($preferences_id, 'true', $new_m_host, $new_w_host, 
		$new_site_title, $new_login_message, $new_session_lifetime, $new_font, 
		$new_background_color, $new_primary_color, $new_secondary_color, 
		$new_primary_font_color, $new_secondary_font_color,
		$new_error_color, $new_popular_threshold);
	// reload the preferences now
	set_preferences();
}

show_template('header'); 
show_menu_items('Admin');

if ( $action == 'show_site_preferences' ) {
	show_admin_menu('Site Preferences');
}
elseif ( ($action == 'show_users') || ($action == 'show_new_user')) {
	show_admin_menu('Users');
}
elseif ( $action == 'show_update_catalog' ) {
	show_admin_menu('Catalog'); 
}
else {
	show_admin_menu('...');
}

if ( $action == 'Update Preferences' ) {
	$action = 'show_preferences';
}
elseif ( $action == 'show_update_catalog' ) {
        show_update_catalog();
}
elseif ( $action == 'show_file_manager' ) {
        show_file_manager();
}
elseif ( $action == 'show_site_preferences' ) {
	$user = new User(0);
	require (conf('prefix') . "/templates/show_preferences.inc");
}
elseif ( $action == 'show_preferences' ) {
	$user = new User($_REQUEST['user_id']);
	require (conf('prefix') . "/templates/show_preferences.inc");
}
elseif ( $action == 'show_orphaned_files' ) {
        show_orphaned_files();
}
else {
	require (conf('prefix') . "/templates/show_admin_index.inc");
} // if they didn't pick anything

echo "<br /><br />";
show_admin_menu('');
show_menu_items('Admin');
?>

</body>
</html>
