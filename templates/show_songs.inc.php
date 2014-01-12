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

$web_path = AmpConfig::get('web_path');
$tags_list = Tag::get_display(Tag::get_tags());
$thcount = 8;
?>
<?php if ($browse->get_show_header()) require AmpConfig::get('prefix') . '/templates/list_header.inc.php'; ?>
<table id="reorder_songs_table" class="tabledata" cellpadding="0" cellspacing="0">
    <tr class="th-top">
    <?php if (AmpConfig::get('directplay')) { ++$thcount; ?>
        <th class="cel_directplay"><?php echo T_('Play'); ?></th>
    <?php } ?>
        <th class="cel_add"><?php echo T_('Add'); ?></th>
        <th class="cel_song"><?php echo Ajax::text('?page=browse&action=set_sort&browse_id=' . $browse->id . '&sort=title', T_('Song Title'), 'sort_song_title'); ?></th>
        <th class="cel_artist"><?php echo Ajax::text('?page=browse&action=set_sort&browse_id=' . $browse->id . '&sort=artist', T_('Artist'), 'sort_song_artist'); ?></th>
        <th class="cel_album"><?php echo Ajax::text('?page=browse&action=set_sort&browse_id=' . $browse->id . '&sort=album', T_('Album'), 'sort_song_album'); ?></th>
        <th class="cel_tags"><?php echo T_('Tags'); ?></th>
        <th class="cel_track"><?php echo Ajax::text('?page=browse&action=set_sort&browse_id=' . $browse->id . '&sort=track', T_('Track'), 'sort_song_track'); ?></th>
        <th class="cel_time"><?php echo Ajax::text('?page=browse&action=set_sort&browse_id=' . $browse->id . '&sort=time', T_('Time'), 'sort_song_time'); ?></th>
    <?php if (AmpConfig::get('ratings')) {
        ++$thcount;
        Rating::build_cache('song', $object_ids);
    ?>
        <th class="cel_rating"><?php echo T_('Rating'); ?></th>
    <?php } ?>
    <?php if (AmpConfig::get('userflags')) {
        ++$thcount;
        Userflag::build_cache('song', $object_ids);
    ?>
        <th class="cel_userflag"><?php echo T_('Flag'); ?></th>
    <?php } ?>
        <th class="cel_action"><?php echo T_('Action'); ?></th>
    <?php if (isset($argument) && $argument) { ++$thcount; ?>
        <th class="cel_drag"></th>
    <?php } ?>
    </tr>

    <tbody id="sortableplaylist">
    <?php
        foreach ($object_ids as $song_id) {
            $song = new Song($song_id);
            $song->format();
    ?>
        <tr class="<?php echo UI::flip_class(); ?>" id="song_<?php echo $song->id; ?>">
            <?php require AmpConfig::get('prefix') . '/templates/show_song_row.inc.php'; ?>
        </tr>
    <?php } ?>
    </tbody>

<?php if (!count($object_ids)) { ?>
    <tr class="<?php echo UI::flip_class(); ?>">
        <td colspan="<?php echo $thcount; ?>"><span class="nodata"><?php echo T_('No song found'); ?></span></td>
    </tr>
<?php } ?>

    <tr class="th-bottom">
    <?php if (AmpConfig::get('directplay')) { ?>
        <th class="cel_directplay"><?php echo T_('Play'); ?></th>
    <?php } ?>
        <th class="cel_add"><?php echo T_('Add'); ?></th>
        <th class="cel_song"><?php echo Ajax::text('?page=browse&action=set_sort&browse_id=' . $browse->id . '&type=song&sort=title', T_('Song Title'),'sort_song_title_bottom'); ?></th>
        <th class="cel_artist"><?php echo Ajax::text('?page=browse&action=set_sort&browse_id=' . $browse->id . '&type=song&sort=artist', T_('Artist'),'sort_song_artist_bottom'); ?></th>
        <th class="cel_album"><?php echo Ajax::text('?page=browse&action=set_sort&browse_id=' . $browse->id . '&type=song&sort=album', T_('Album'),'sort_song_album_bottom'); ?></th>
        <th class="cel_tags"><?php echo T_('Tags'); ?></th>
        <th class="cel_track"><?php echo Ajax::text('?page=browse&action=set_sort&browse_id=' . $browse->id . '&type=song&sort=track', T_('Track'),'sort_song_track_bottom'); ?></th>
        <th class="cel_time"><?php echo Ajax::text('?page=browse&action=set_sort&browse_id=' . $browse->id . '&type=song&sort=time', T_('Time'),'sort_song_time_bottom'); ?></th>
    <?php if (AmpConfig::get('ratings')) { ?>
        <th class="cel_rating"><?php echo T_('Rating'); ?></th>
    <?php } ?>
    <?php if (AmpConfig::get('userflags')) { ?>
            <th class="cel_userflag"><?php echo T_('Flag'); ?></th>
    <?php } ?>
        <th class="cel_action"><?php echo T_('Action'); ?></th>
    <?php if (isset($argument) && $argument) { ?>
        <th class="cel_drag"></th>
    <?php } ?>
    </tr>
</table>
<?php if ($browse->get_show_header()) require AmpConfig::get('prefix') . '/templates/list_header.inc.php'; ?>
