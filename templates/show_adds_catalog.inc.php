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

show_box_top();
printf(_('Starting New Song Search on %s catalog'), "<strong>[$this->name]</strong>"); 
echo "<br />\n";
echo _('Found') . ": <span id=\"add_count_" . $this->id ."\">" . _('None') . "</span><br />\n";
echo _('Reading') . ":<span id=\"add_dir_" . $this->id . "\"></span><br />";
show_box_bottom(); 
?>
