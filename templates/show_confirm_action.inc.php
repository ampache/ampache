<?php
/*      
        
 Copyright (c) 2001 - 2006 Ampache.org
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
?>

<br />
<div class="text-box" style="margin-right:25%;text-align:center;margin-left:25%;">
<form name="confirm" method="post" action="<?php echo $web_path; ?>/<?php echo $script; ?>?<?php echo $arg; ?>" enctype="multipart/form-data">
	<p><?php echo $text; ?></p>
	<p>
		<input type="submit" name="confirm" value="<?php echo _("Yes"); ?>" />
		<input type="submit" name="confirm" value="<?php echo _("No"); ?>" />
	</p>
</form>
</div>
