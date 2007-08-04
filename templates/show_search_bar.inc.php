<?php
/*

 Copyright (c) 2001 - 2007 Ampache.org
 All rights reserved.

 This program is free software; you can redistribute it and/or
 modify it under the terms of the GNU General Public License
 as published by the Free Software Foundation; version 2
 of the License.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with this program; if not, write to the Free Software
 Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.

*/
?>
<div id="sb_Subsearch">
        <form name="search" method="post" action="<?php echo $web_path; ?>/search.php" enctype="multipart/form-data" style="Display:inline">
        <input type="text" name="search_string" value="" size="5" />
        <input class="smallbutton" type="submit" value="<?php echo _('Search'); ?>" />
        <input type="hidden" name="action" value="quick_search" />
        <input type="hidden" name="method" value="fuzzy" />
        <input type="hidden" name="object_type" value="song" />
	<a href="<?php echo $web_path; ?>/search.php" class="smallbutton"><?php echo _('Advanced Search'); ?></a>
        </form>
</div>

