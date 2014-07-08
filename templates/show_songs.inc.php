<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU General Public License, version 2 (GPLv2)
 * Copyright 2001 - 2014 Ampache.org
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

$web_path = AmpConfig::get('web_path');
$thcount = 8;
?>
<?php if ($browse->get_show_header()) require AmpConfig::get('prefix') . '/templates/list_header.inc.php'; ?>
<table id="reorder_songs_table_<?php echo $browse->get_filter('album'); ?>" class="tabledata" cellpadding="0" cellspacing="0" data-objecttype="song">
    <thead>
        <tr class="th-top">
            <th class="cel_play essential"></th>
            <th class="cel_song essential persist"><?php echo Ajax::text('?page=browse&action=set_sort&browse_id=' . $browse->id . '&sort=title', T_('Song Title'), 'sort_song_title'.$browse->id); ?></th>
            <th class="cel_add essential"></th>
            <th class="cel_artist optional"><?php echo Ajax::text('?page=browse&action=set_sort&browse_id=' . $browse->id . '&sort=artist', T_('Artist'), 'sort_song_artist'.$browse->id); ?></th>
            <th class="cel_album essential"><?php echo Ajax::text('?page=browse&action=set_sort&browse_id=' . $browse->id . '&sort=album', T_('Album'), 'sort_song_album'.$browse->id); ?></th>
            <th class="cel_tags optional"><?php echo T_('Tags'); ?></th>
            <th class="cel_time optional"><?php echo Ajax::text('?page=browse&action=set_sort&browse_id=' . $browse->id . '&sort=time', T_('Time'), 'sort_song_time'.$browse->id); ?></th>
        <?php if (AmpConfig::get('ratings')) {
            ++$thcount;
            Rating::build_cache('song', $object_ids);
        ?>
            <th class="cel_rating optional"><?php echo T_('Rating'); ?></th>
        <?php } ?>
        <?php if (AmpConfig::get('userflags')) {
            ++$thcount;
            Userflag::build_cache('song', $object_ids);
        ?>
            <th class="cel_userflag optional"><?php echo T_('Fav.'); ?></th>
        <?php } ?>
            <th class="cel_action essential"><?php echo T_('Action'); ?></th>
        <?php if (isset($argument) && $argument) { ++$thcount; ?>
            <th class="cel_drag essential"></th>
        <?php } ?>
        </tr>
    </thead>
    <tbody id="sortableplaylist_<?php echo $browse->get_filter('album'); ?>">
        <?php
            foreach ($object_ids as $song_id) {
                $libitem = new Song($song_id);
                $libitem->format();
        ?>
            <tr class="<?php echo UI::flip_class(); ?>" id="song_<?php echo $libitem->id; ?>">
                <?php require AmpConfig::get('prefix') . '/templates/show_song_row.inc.php'; ?>
            </tr>
        <?php } ?>

    <?php if (!count($object_ids)) { ?>
        <tr class="<?php echo UI::flip_class(); ?>">
            <td colspan="<?php echo $thcount; ?>"><span class="nodata"><?php echo T_('No song found'); ?></span></td>
        </tr>
    <?php } ?>
    </tbody>
    <tfoot>
        <tr class="th-bottom">
            <th class="cel_play"></th>
            <th class="cel_song"><?php echo Ajax::text('?page=browse&action=set_sort&browse_id=' . $browse->id . '&sort=title', T_('Song Title'), 'sort_song_title'.$browse->id); ?></th>
            <th class="cel_add"></th>
            <th class="cel_artist"><?php echo Ajax::text('?page=browse&action=set_sort&browse_id=' . $browse->id . '&sort=artist', T_('Artist'), 'sort_song_artist'.$browse->id); ?></th>
            <th class="cel_album"><?php echo Ajax::text('?page=browse&action=set_sort&browse_id=' . $browse->id . '&sort=album', T_('Album'), 'sort_song_album'.$browse->id); ?></th>
            <th class="cel_tags"><?php echo T_('Tags'); ?></th>
            <th class="cel_time"><?php echo Ajax::text('?page=browse&action=set_sort&browse_id=' . $browse->id . '&sort=time', T_('Time'), 'sort_song_time'.$browse->id); ?></th>
        <?php if (AmpConfig::get('ratings')) { ?>
            <th class="cel_rating"><?php echo T_('Rating'); ?></th>
        <?php } ?>
        <?php if (AmpConfig::get('userflags')) { ?>
            <th class="cel_userflag"></th>
        <?php } ?>
            <th class="cel_action"></th>
        <?php if (isset($argument) && $argument) { ?>
            <th class="cel_drag"></th>
        <?php } ?>
        </tr>
    </tfoot>
</table>
<script src="<?php echo AmpConfig::get('web_path'); ?>/lib/javascript/tabledata.js" language="javascript" type="text/javascript"></script>
<?php if ($browse->get_show_header()) require AmpConfig::get('prefix') . '/templates/list_header.inc.php'; ?>
