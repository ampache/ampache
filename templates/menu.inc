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
	@header 
 A template file

*/
$web_path = conf('web_path');
$items = array(_("Home") => htmlspecialchars($web_path . "/index.php"));

if ($GLOBALS['user']->prefs['play_type'] == 'mpd' && conf('localplay_menu')) {
	$items += array(_("Local Play") => htmlspecialchars($web_path . "/mpd.php"));
}

$items += array(_("Browse")	=> htmlspecialchars($web_path . "/browse.php"),
//		_("Albums")	=> htmlspecialchars($web_path . "/albums.php"),
//		_("Artists")	=> htmlspecialchars($web_path . "/artists.php"),
		_("Playlists")	=> htmlspecialchars($web_path . "/playlist.php"),
		_("Search")	=> htmlspecialchars($web_path . "/search.php"),
		_("Preferences") => htmlspecialchars($web_path . "/preferences.php")
        );
if ($GLOBALS['user']->prefs['upload']) { 
	$items = array_merge($items, array(_("Upload") => $web_path . "/upload.php"));
}
?>

<ul id="mainmenu">
<?php
foreach (array_keys($items) as $item) {
	if (_($high) == $item) {
		print("\t\t<li class=\"active\"><a class=\"active\" href=\"$items[$item]\">$item</a></li>\n");
	}
	else {
		print("\t\t<li><a href=\"$items[$item]\">$item</a></li>\n");
	}
} // end foreach items

if ((!conf('use_auth')) || ($GLOBALS['user']->has_access(100))) {
	if ($high == 'Admin') {
		print("\t\t<li class=\"active\"><a class=\"active\" href=\"" . $web_path . "/admin/\">" . _("Admin") . "</a></li>\n");
	}
	else {
		print("\t\t<li><a href=\"" . $web_path . "/admin/\">" . _("Admin") . "</a></li>\n");
	}
} // if we aren't using auth or 100

// now do the user specific stuff
if (conf('use_auth')) {
	if (($high == 'Stats') || ($high == 'Profile')) {
		print("\t\t<li class=\"active\">".$GLOBALS['user']->username.": <a class=\"active\" href=\"".$web_path."/user.php?action=show_edit_profile\">" . _("Account") . "</a> |".
		"<a class=\"active\" href=\"".$web_path."/stats.php\">" . _("Stats") . "</a> |".
		"<a class=\"active\" href=\"".$web_path."/logout.php\">" . _("Logout") . "</a> </li>\n");
	} // if stats or profile
	else {
		echo "\t\t<li>".$GLOBALS['user']->username.": " .
		"<a href=\"".$web_path."/user.php?action=show_edit_profile\">" . _("Account") . "</a> |".
		"<a href=\"".$web_path."/stats.php\">" . _("Stats") . "</a> |".
		"<a href=\"".$web_path."/logout.php\">" . _("Logout") . "</a> </li>\n";
	} // else
}  // if use_auth
elseif ($_SESSION['userdata']['id'] != '-1') { 
	print("\t\t<li><a href=\"".$web_path."/logout.php\">" . _("Logout") . "</a> </li>\n");
} // else no user
?>
</ul>
<br />
