<?php
/*

 Copyright (c) 2001 - 2006 Ampache.org
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
?>
<table class="box" cellspacing="0" cellpadding="0">
<tr>
	<td class="box-left-top"></td>
	<td class="box-top"></td>
	<td class="box-right-top"></td>
</tr>
<tr>
	<td class="box-left" rowspan="2"></td>
<?php if ($title) { ?>
	<td class="box-title"><?php echo $title; ?></td>
<?php } else { ?>
	<td></td>
<?php } ?>
	<td class="box-right" rowspan="2"></td>
</tr>
<tr>
	<td class="box-content" style="padding-top:3px;">
