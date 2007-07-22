<?php
/*

 Copyright (c) 2001 - 2007 Ampache.org
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

if (!$_SESSION['state']['sidebar_tab']) { $_SESSION['state']['sidebar_tab'] = 'home'; } 
$class_name = 'sidebar_' . $_SESSION['state']['sidebar_tab'];
${$class_name} = ' class="active" ';

$web_path = Config::get('web_path');
$ajax_url = Config::get('ajax_url'); 
?>
<ul id="sidebar-tabs">
<li <?php echo $sidebar_home; ?>>
	<?php echo Ajax::button("?action=sidebar&button=home",home,_('Home'),'sidebar_home'); ?>
</li>
<li <?php echo $sidebar_browse; ?>>
	<?php echo Ajax::button("?action=sidebar&button=browse",browse,_('Browse'),'sidebar_browse'); ?>
</li>
<li <?php echo $sidebar_search; ?>>
	<?php echo Ajax::button("?action=sidebar&button=search",'view',_('Search'),'sidebar_search'); ?>
</li>
<li <?php echo $sidebar_preferences; ?>>
	<?php echo Ajax::button("?action=sidebar&button=preferences",'edit',_('Preferences'),'sidebar_prefs'); ?>
</li>
<?php if ($GLOBALS['user']->has_access('100')) { ?>
<li <?php echo $sidebar_admin; ?>>
	<?php echo Ajax::button("?action=sidebar&button=admin",'admin',_('Admin'),'sidebar_admin'); ?>
</li>
<?php } ?>
<!-- <li <?php echo $sidebar_player; ?> onclick="ajaxPut('<?php echo $ajax_url; ?>?action=sidebar&button=player');" >
</li>
-->
<li>
	<a href="<?php echo Config::get('web_path'); ?>/logout.php">
	<?php echo get_user_icon('logout',_('Logout')); ?>
	</a>
</li>
</ul>
<div id="sidebar-page">
<?php require_once Config::get('prefix') . '/templates/sidebar_' . $_SESSION['state']['sidebar_tab'] . '.inc.php'; ?>
</div>
