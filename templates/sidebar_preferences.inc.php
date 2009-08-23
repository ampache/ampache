<?php 
/*

 Copyright (c) Ampache.org
 All Rights Reserved

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

/* This one is a little dynamic as we add plugins or localplay modules
 * they can have their own preference sections so we need to build the
 * links based on that, always ignore 'internal' though
 */
$catagories = Preference::get_catagories();  
?>
<ul class="sb2" id="sb_preferences">
  <li><h4><?php echo _('Preferences'); ?></h4>
    <ul class="sb3" id="sb_preferences_sections">
<?php 
	foreach ($catagories as $name) { 
		if ($name == 'system') { continue; } 
		$f_name = ucfirst($name); 
?>
      <li id="sb_preferences_sections_<?php echo $f_name; ?>"><a href="<?php echo $web_path; ?>/preferences.php?tab=<?php echo $name; ?>"><?php echo _($f_name); ?></a></li>
<?php } ?>
      <li id="sb_preferences_sections_account"><a href="<?php echo $web_path; ?>/preferences.php?tab=account"><?php echo _('Account'); ?></a></li>
    </ul>
  </li>
</ul>
