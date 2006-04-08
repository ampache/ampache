<?php
/*

 Copyright (c) 2001 - 2006 Ampache.org
 All rights reserved

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
	@header Show Preferences
	@discussion shows edit page for preferences

*/

/**
 * This page has a few tabs, as such we need to figure out which tab we are on 
 * and display the information accordingly 
 */
$current_tab 	= scrub_in($_REQUEST['tab']);
if (!$current_tab) { $current_tab = 'theme'; } 
$link		= conf('web_path') . $target;

/* CSS construction bs */
$link_active	= "a_" . $current_tab;
${$link_active} = "id=\"current\"";
$tab_active	= "tab_" .$current_tab;
${$tab_active}  = "id=\"tabactive\"";

/* I'm cheating a little here, check to see if we want to show the
 * Apply to All button on this page 
 */
if (($GLOBALS['user']->has_access(100)) AND ($user_id == '-1' AND conf('use_auth'))) { 
	$show_apply_to_all = true;
}
?>
<span class="header1">
<?php echo _("Editing"); ?> <?php echo $fullname; ?> <?php echo _('preferences'); ?> 
<?php if ($GLOBALS['user']->has_access(100)) { ?>
[<a href="<?php echo conf('web_path'); ?>/admin/preferences.php?action=fix_preferences&amp;user_id=<?php echo $user_id; ?>"><?php echo _("Rebuild Preferences"); ?></a>]
<?php } ?>
</span>

<div id="tabcontainer">
<ul id="tablist">
<li <?php echo $tab_theme; ?>>
	<a href="<?php echo $link; ?>?tab=theme&amp;user_id=<?php echo $user_id; ?>" <?php echo $a_theme; ?>>Theme</a>
</li>
<li <?php echo $tab_streaming; ?>>
	<a href="<?php echo $link; ?>?tab=streaming&amp;user_id=<?php echo $user_id; ?>" <?php echo $a_streaming; ?>>Streaming</a>
</li>
<li <?php echo $tab_interface; ?>>
	<a href="<?php echo $link; ?>?tab=interface&amp;user_id=<?php echo $user_id; ?>" <?php echo $a_interface; ?>>Interface</a>
</li>
<li <?php echo $tab_options; ?>>
	<a href="<?php echo $link; ?>?tab=options&amp;user_id=<?php echo $user_id; ?>" <?php echo $a_options; ?>>Options</a>
</li>
<?php if ($user_id != '-1') { ?>
<li <?php echo $tab_account; ?>>
	<a href="<?php echo $link; ?>?tab=account&amp;user_id=<?php echo $user_id; ?>" <?php echo $a_account; ?>>Account</a>
</li>
<?php } else { ?>
<li <?php echo $tab_system; ?>>
	<a href="<?php echo $link; ?>?tab=system&amp;user_id=<?php echo $user_id; ?>" <?php echo $a_system; ?>>System</a>
</li>
<?php } ?>
</ul>
</div>
<div class="text-box" style="width:45em;">
<form method="post" name="preferences" action="<?php echo conf('web_path'); ?><?php echo $target; ?>" enctype="multipart/form-data">
<?php 
if ($current_tab != 'account') { 
	show_preference_box($preferences[$current_tab]); 
?>
	<input class="button" type="submit" value="<?php echo _("Update Preferences"); ?>" />
	<input type="hidden" name="action" value="update_preferences" />
	<input type="hidden" name="user_id" value="<?php echo $user_id; ?>" />&nbsp;&nbsp;
	<input type="hidden" name="tab" value="<?php echo scrub_out($current_tab); ?>" />
	<input class="button" type="submit" name="action" value="<?php echo _("Cancel"); ?>" />
<?php
	} 

	else { 
		$this_user = new User($user_id);
		require (conf('prefix') . '/templates/show_user.inc.php');
	}
?>
</form>
</div>
