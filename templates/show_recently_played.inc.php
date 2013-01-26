<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU General Public License, version 2 (GPLv2)
 * Copyright 2001 - 2013 Ampache.org
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
 */

$link = Config::get('use_rss') ? ' ' . Ampache_RSS::get_display('recently_played') :  '';
UI::show_box_top(T_('Recently Played') . $link, 'box box_recently_played');
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
    <th class="cel_add"><?php echo T_('Add'); ?></th>
    <th class="cel_song"><?php echo T_('Song'); ?></th>
    <th class="cel_album"><?php echo T_('Album'); ?></th>
    <th class="cel_artist"><?php echo T_('Artist'); ?></th>
    <th class="cel_username"><?php echo T_('Username'); ?></th>
    <th class="cel_lastplayed"><?php echo T_('Last Played'); ?></th>
</tr>
<?php foreach ($data as $row) {
    $row_user = new User($row['user']);
    $song = new Song($row['object_id']);
    $interval = intval(time() - $row['date']);

    if ($interval < 60) {
        $unit = 'seconds';
    }
    else if ($interval < 3600) {
        $interval = floor($interval / 60);
        $unit = 'minutes';
    }
    else if ($interval < 86400) {
        $interval = floor($interval / 3600);
        $unit = 'hours';
    }
    else if ($interval < 604800) {
        $interval = floor($interval / 86400);
        $unit = 'days';
    }
    else if ($interval < 2592000) {
        $interval = floor($interval / 604800);
        $unit = 'weeks';
    }
    else if ($interval < 31556926) {
        $interval = floor($interval / 2592000);
        $unit = 'months';
    }
    else if ($interval < 631138519) {
        $interval = floor($interval / 31556926); 
        $unit = 'years';
    }
    else {
        $interval = floor($interval / 315569260);
        $unit = 'decades';
    }

    // I wonder how smart gettext is?
    $time_string = sprintf(T_ngettext('%d ' . rtrim($unit, 's') . ' ago', '%d ' . $unit . ' ago', $interval), $interval);

    $song->format();
?>
<tr class="<?php echo UI::flip_class(); ?>">
    <td class="cel_add">
        <?php echo Ajax::button('?action=basket&type=song&id=' . $song->id,'add', T_('Add'),'add_' . $song->id); ?>
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
    <td colspan="6"><span class="fatalerror"><?php echo T_('Not Enough Data'); ?></span></td>
</tr>
<?php } ?>
<tr class="th-bottom">
    <th class="cel_add"><?php echo T_('Add'); ?></th>
    <th class="cel_username"><?php echo T_('Username'); ?></th>
    <th class="cel_song"><?php echo T_('Song'); ?></th>
    <th class="cel_album"><?php echo T_('Album'); ?></th>
    <th class="cel_artist"><?php echo T_('Artist'); ?></th>
    <th class="cel_lastplayed"><?php echo T_('Last Played'); ?></th>
</tr>
</table>
<?php UI::show_box_bottom(); ?>
