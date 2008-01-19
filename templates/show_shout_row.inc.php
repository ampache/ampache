<?php
/*

 Copyright (c) 2001 - 2008 Ampache.org
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
<tr id="flagged_<?php echo $shout->id; ?>" class="<?php echo flip_class(); ?>">
	<td class="cel_object"><?php echo $shout->f_name; ?></td>
	<td class="cel_username"><?php echo $shout->f_user; ?></td>
	<td class="cel_sticky"><?php $shout->sticky; ?></td>
	<td class="cel_comment"><?php echo scrub_out($shout->comment); ?></td>
	<td class="cel_action">
	</td>
</tr>
