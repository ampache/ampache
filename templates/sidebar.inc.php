<?php
/*

 Copyright (c) Ampache.org
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
${$class_name} = ' active';

// List of buttons ( id, title, icon, access level)
$sidebar_items[] = array('id'=>'home', 'title'=>_('Home'), 'icon'=>'home', 'access'=>5);
$sidebar_items[] = array('id'=>'localplay', 'title'=>_('Localplay'), 'icon'=>'volumeup', 'access'=>5);
$sidebar_items[] = array('id'=>'preferences', 'title'=>_('Preferences'), 'icon'=>'edit', 'access'=>5);
$sidebar_items[] = array('id'=>'modules','title'=>_('Modules'),'icon'=>'plugin','access'=>100); 
$sidebar_items[] = array('id'=>'admin', 'title'=>_('Admin'), 'icon'=>'admin', 'access'=>100);


$web_path = Config::get('web_path');
$ajax_url = Config::get('ajax_url'); 

?>
<ul id="sidebar-tabs">
<?php 
	foreach ($sidebar_items as $item) { 
		if (Access::check('interface',$item['access'])) {
			$li_params = "id='sb_tab_" . $item['id'] . "' class='sb1" . ${'sidebar_'.$item['id'] } . "'";
		?><li <?php echo $li_params; ?>>
      	<?php 
        // Button
        echo Ajax::button("?page=index&action=sidebar&button=".$item['id'],$item['icon'],$item['title'],'sidebar_'.$item['id']);
      	
      	// Include subnav if it's the selected one
      	// so that it's generated inside its parent li
	if ($item['id']==$_SESSION['state']['sidebar_tab']) {
      	  ?><div id="sidebar-page"><?php
      	  require_once Config::get('prefix') . '/templates/sidebar_' . $_SESSION['state']['sidebar_tab'] . '.inc.php';
      	  ?></div><?php
        }
       ?></li><?php
     }
	}
?>
<li id="sb_tab_logout" class="sb1">
	<a href="<?php echo Config::get('web_path'); ?>/logout.php" id="sidebar_logout" >
	<?php echo get_user_icon('logout',_('Logout')); ?>
	</a>
</li>
</ul>

