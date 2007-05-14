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
<li <?php echo $sidebar_home; ?> onclick="ajaxPut('<?php echo $ajax_url; ?>?action=sidebar&amp;button=home');" >
	<?php echo get_user_icon('home','',_('Home')); ?>
</li>
<li <?php echo $sidebar_browse; ?> onclick="ajaxPut('<?php echo $ajax_url; ?>?action=sidebar&amp;button=browse');" >
	<?php echo get_user_icon('browse','',_('Browse')); ?>
</li>
<li <?php echo $sidebar_search; ?> onclick="ajaxPut('<?php echo $ajax_url; ?>?action=sidebar&amp;button=search');" >
	<?php echo get_user_icon('view','',_('Search')); ?>
</li>
<li <?php echo $sidebar_preferences; ?> onclick="ajaxPut('<?php echo $ajax_url; ?>?action=sidebar&amp;button=preferences');" >
	<?php echo get_user_icon('edit','',_('Preferences')); ?>
</li>
<?php if ($GLOBALS['user']->has_access('100')) { ?>
<li <?php echo $sidebar_admin; ?> onclick="ajaxPut('<?php echo $ajax_url; ?>?action=sidebar&amp;button=admin');" >
	<?php echo get_user_icon('admin','',_('Admin')); ?>
</li>
<?php } ?>
<li <?php echo $sidebar_player; ?> onclick="ajaxPut('<?php echo $ajax_url; ?>?action=sidebar&amp;button=player');" >
	<?php echo get_user_icon('all','',_('Player')); ?>
</li>
</ul>
<div id="sidebar-page">
<?php require_once Config::get('prefix') . '/templates/sidebar_' . $_SESSION['state']['sidebar_tab'] . '.inc.php'; ?>
</div>
