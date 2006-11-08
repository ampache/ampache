<?php
/*

 Copyright (c) 2001 - 2006 Ampache.org
 All Rights Reserved.

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

/*

 Show us the stats for the server and this user

*/
require_once('lib/init.php');

/* If we are a full admin then we can see other peoples stats! */
if ($GLOBALS['user']->has_access(100) AND isset($_REQUEST['user_id'])) { 
	$working_user = new User($_REQUEST['user_id']);
}
else { 
	$working_user = $GLOBALS['user'];
}

show_template('header');
$title = $working_user->fullname . ' ' .  _('Favorites') . ':';
?>
<?php require (conf('prefix') . '/templates/show_box_top.inc.php'); ?>
<table cellpadding="5" cellspacing="5" border="0" width="100%">
	<tr>
		<td valign="top">
		<?php
			if ( $items = $working_user->get_favorites('artist') ) {
				$items = $working_user->format_favorites($items);
				show_info_box('Favorite Artists', 'artist', $items);
			}
			else {
				echo "<span class=\"error\">" . _('Not Enough Data') . "</span>";
			}
		?>
		</td>

                <td valign="top">
                <?php
                        if ( $items = $working_user->get_favorites('song') ) { 
				$items = $working_user->format_favorites($items);
                                show_info_box('Favorite Songs', 'your_song', $items);
                        }             
                        else {
				echo "<span class=\"error\">" . _('Not Enough Data') . "</span>";
                        }
                ?>
                </td>

                <td valign="top">
                <?php
                        if ( $items = $working_user->get_favorites('album') ) { 
				$items = $working_user->format_favorites($items);
                                show_info_box('Favorite Albums', 'album', $items);
                        }             
                        else {
				echo "<span class=\"error\">" . _('Not Enough Data') . "</span>";
                        }
                ?>
                </td>
	</tr>
</table>
<?php require (conf('prefix') . '/templates/show_box_bottom.inc.php'); ?>
<?php show_footer(); ?>
