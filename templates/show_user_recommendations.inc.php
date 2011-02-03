<?php
/* vim:set tabstop=8 softtabstop=8 shiftwidth=8 noexpandtab: */
/**
 * Show User Recommendations
 *
 * PHP version 5
 *
 * LICENSE: GNU General Public License, version 2 (GPLv2)
 * Copyright (c) 2001 - 2011 Ampache.org All Rights Reserved
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License v2
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 *
 * @category	Template
 * @package	Template
 * @author	Karl Vollmer <vollmer@ampache.org>
 * @copyright	2001 - 2011 Ampache.org
 * @license	http://opensource.org/licenses/gpl-2.0 GPLv2
 * @version	PHP 5.2
 * @link	http://www.ampache.org/
 * @since	File available since Release 1.0
 */

?>
<?php show_box_top(sprintf('%s Recommendations'), $working_user->fullname); ?>
<table class="tabledata">
<tr>
	<td valign="top">
	<?php
	if (count($recommended_artists)) {
		$items = $working_user->format_recommendations($recommended_artists,'artist');
		show_info_box(_('Recommended Artists'),'artist',$items);
	}
	else {
		echo "<span class=\"error\">" . _('Not Enough Data') . "</span>";
	}
	?>
	</td>
	<td valign="top">
	<?php
	if (count($recommended_albums)) {
		$items = $working_user->format_recommendations($recommended_albums,'album');
		show_info_box(_('Recommended Albums'),'album',$items);
	}
	else {
		echo "<span class=\"error\">" . _('Not Enough Data') . "</span>";
	}
	?>
	</td>
	<td valign="top">
	<?php
	if (count($recommended_songs)) {
		$items = $working_user->format_recommendations($recommended_songs,'song');
		show_info_box(_('Recommended Songs'),'song',$items);
	}
	else {
		echo "<span class=\"error\">" . _('Not Enough Data') . "</span>";
	}
	?>
	</td>
</tr>
</table>
<?php show_box_bottom(); ?>
