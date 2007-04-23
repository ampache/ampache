<?php
/*

 Copyright (c) 2001 - 2007 Ampache.org
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
 * This file expect an array of 'items' which have ['0']['url'] ['0']['title']
 * ['0']['active'] == true/false and ['0']['cssclass'] this is called from show_submenu($items);
 */

?>
<ul class="subnavside">
<?php 
	foreach ($items as $item) { 
		if ($item['url'] == $item['active']) { 
			$li_class = "class=\"activesubmenu\"";
		} 
		$li_id = "id=\"" . $item['cssclass'] . "\"";
		?>
		<li <?php echo $li_class; echo $li_id; ?>><a href="<?php echo Config::get('web_path') . "/" .  $item['url']; ?>"><?php echo $item['title']; ?></a></li>
	<?php unset($li_id); } // END foreach ($items as $item) ?>
</ul>
