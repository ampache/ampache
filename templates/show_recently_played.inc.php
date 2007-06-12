<?php
/*

 Copyright (c) 2001 - 2007 Ampache.org
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

/* Define the time places starting at 0 */
$time_unit = array('',_('seconds ago'),_('minutes ago'),_('hours ago'),_('days ago'),_('weeks ago'),_('months ago'),_('years ago')); 

?>
<table>
<tr class="table-header">
	<td><?php echo _('Username'); ?></td>
	<td><?php echo _('Song'); ?></td>
	<td><?php echo _('Album'); ?></td>
	<td><?php echo _('Artist'); ?></td>
	<td><?php echo _('Last Played'); ?></td>
</tr>
<?php foreach ($data as $row) { 
	$row_user = new User($row['user']);
	$song = new Song($row['object_id']); 
	$amount = intval(time() - $row['date']+2); 
	$time_place = '0';

	while ($amount >= 1) { 
		$final = $amount; 
		$time_place++; 
                if ($time_place <= 2) {
                        $amount = floor($amount/60);
                }
                if ($time_place == '3') {
                        $amount = floor($amount/24);
                }
                if ($time_place == '4') {
                        $amount = floor($amount/7);
                }
                if ($time_place == '5') {
                        $amount = floor($amount/4);
                }
                if ($time_place == '6') {
                        $amount = floor ($amount/12);
                }
	}

	$time_string = $final . ' ' . $time_unit[$time_place];

	$song->format(); 
?>
<tr>
	<td>
		<a href="<?php echo Config::get('web_path'); ?>/stats.php?action=show_user&amp;user_id=<?php echo scrub_out($row_user->id); ?>">
		<?php echo scrub_out($row_user->fullname); ?>
		</a>
	</td>
	<td><?php echo $song->f_link; ?></td>
	<td><?php echo $song->f_album_link; ?></td>
	<td><?php echo $song->f_artist_link; ?></td>
	<td><?php echo $time_string; ?></td>
</tr>
<?php } ?>
</table>
