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
<form style="display:inline;" name="f" method="get" action="<?php echo conf('web_path') . "/$action"; ?>" enctype="multipart/form-data">
	<label for="match" accesskey="S"><?php echo $text; ?></label> 
	<input type="text" size="5" id="match" name="match" value="<?php echo $match; ?>" />
	<input type="hidden" name="action" value="<?php echo scrub_out($_REQUEST['action']); ?>">
</form>
