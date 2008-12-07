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

/* Define the time places starting at 0 */
$time_unit = array('',_('seconds ago'),_('minutes ago'),_('hours ago'),_('days ago'),_('weeks ago'),_('months ago'),_('years ago')); 
$link = Config::get('use_rss') ? ' ' . AmpacheRSS::get_display('recently_played') :  '';
show_box_top(_('Recently Played') . $link);
?>
<table class="tabledata" cellpadding="0" cellspacing="0">
<colgroup>
  <col id="col_add" />
  <col id="col_username" />
  <col id="col_song" />
  <col id="col_album" />
  <col id="col_artist" />
  <col id="col_lastplayed" />
</colgroup>
<tr class="th-top">
	<th class="cel_add"><?php echo _('Add'); ?></th>
	<th class="cel_song"><?php echo _('Song'); ?></th>
	<th class="cel_album"><?php echo _('Album'); ?></th>
	<th class="cel_artist"><?php echo _('Artist'); ?></th>
	<th class="cel_username"><?php echo _('Username'); ?></th>
	<th class="cel_lastplayed"><?php echo _('Last Played'); ?></th>
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
		if ($time_place > '6') { 
			$final = $amount . '+'; 
			break; 
		} 
	}

	$time_string = $final . ' ' . $time_unit[$time_place];

	$song->format(); 
?>
<tr class="<?php echo flip_class(); ?>">
	<td class="cel_add">
        <?php echo Ajax::button('?action=basket&type=song&id=' . $song->id,'add',_('Add'),'add_' . $song->id); ?>
	</td>
	<td class="cel_song"><?php echo $song->f_link; ?></td>
	<td class="cel_album"><?php echo $song->f_album_link; ?></td>
	<td class="cel_artist"><?php echo $song->f_artist_link; ?></td>
	<td class="cel_username">
		<a href="<?php echo Config::get('web_path'); ?>/stats.php?action=show_user&amp;user_id=<?php echo scrub_out($row_user->id); ?>">
		<?php echo scrub_out($row_user->fullname); ?>
		</a>
	</td>
	<td class="cel_lastplayed"><?php echo $time_string; ?></td>
</tr>
<?php } ?>
<?php if (!count($data)) { ?>
<tr>
	<td colspan="6"><span class="fatalerror"><?php echo _('Not Enough Data'); ?></span></td>
</tr>
<?php } ?>
<tr class="th-bottom">
	<th class="cel_add"><?php echo _('Add'); ?></th>
	<th class="cel_username"><?php echo _('Username'); ?></th>
	<th class="cel_song"><?php echo _('Song'); ?></th>
	<th class="cel_album"><?php echo _('Album'); ?></th>
	<th class="cel_artist"><?php echo _('Artist'); ?></th>
	<th class="cel_lastplayed"><?php echo _('Last Played'); ?></th>
</tr>
</table>
<?php show_box_bottom(); ?>
