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
if ($browse->get_show_header()) require AmpConfig::get('prefix') . '/templates/list_header.inc.php';
?>
<table class="tabledata" cellpadding="0" cellspacing="0" data-objecttype="video">
    <thead>
        <tr class="th-top">
            <th class="cel_play essential"></th>
        <?php if (Art::is_enabled()) { ?>
            <th class="cel_cover"><?php echo T_('Art'); ?></th>
        <?php } ?>
            <th class="cel_title essential persist"><?php echo Ajax::text('?page=browse&action=set_sort&browse_id=' . $browse->id . '&type=video&sort=title', T_('Title'),'sort_video_title'); ?></th>
<?php
if (isset($video_type) && $video_type != 'video') {
    require AmpConfig::get('prefix') . '/templates/show_partial_' . $video_type . 's.inc.php';
}
?>
            <th class="cel_release_date optional"><?php echo Ajax::text('?page=browse&action=set_sort&browse_id=' . $browse->id . '&type=video&sort=release_date', T_('Release Date'),'sort_video_release_date'); ?></th>
            <th class="cel_codec optional"><?php echo Ajax::text('?page=browse&action=set_sort&browse_id=' . $browse->id . '&type=video&sort=codec', T_('Codec'),'sort_video_codec'); ?></th>
            <th class="cel_resolution optional"><?php echo Ajax::text('?page=browse&action=set_sort&browse_id=' . $browse->id . '&type=video&sort=resolution', T_('Resolution'),'sort_video_rez'); ?></th>
            <th class="cel_length optional"><?php echo Ajax::text('?page=browse&action=set_sort&browse_id=' . $browse->id . '&type=video&sort=length', T_('Time'),'sort_video_length'); ?></th>
            <th class="cel_tags optional"><?php echo T_('Tags'); ?></th>
            <?php if (AmpConfig::get('ratings')) { ?>
                <th class="cel_rating optional"><?php echo T_('Rating'); ?></th>
            <?php } ?>
            <?php if (AmpConfig::get('userflags')) { ?>
                <th class="cel_userflag optional"><?php echo T_('Fav.'); ?></th>
            <?php } ?>
            <th class="cel_action essential"><?php echo T_('Action'); ?></th>
        </tr>
    </thead>
    <tbody>
        <?php
        /* Foreach through every artist that has been passed to us */
        foreach ($object_ids as $video_id) {
                if (isset($video_type)) {
                    $libitem = new $video_type($video_id);
                } else {
                    $libitem = new Video($video_id);
                }
                $libitem->format();
        ?>
        <tr id="video_<?php echo $libitem->id; ?>" class="<?php echo UI::flip_class(); ?>">
            <?php require AmpConfig::get('prefix') . '/templates/show_video_row.inc.php'; ?>
        </tr>
        <?php } //end foreach  ?>
        <?php if (!count($object_ids)) { ?>
        <tr class="<?php echo UI::flip_class(); ?>">
            <td colspan="42"><span class="nodata"><?php echo T_('No video found'); ?></span></td>
        </tr>
        <?php } ?>
    </tbody>
    <tfoot>
        <tr class="th-bottom">
            <th class="cel_play"></th>
        <?php if (Art::is_enabled()) { ?>
            <th class="cel_cover"><?php echo T_('Art'); ?></th>
        <?php } ?>
            <th class="cel_title"><?php echo Ajax::text('?page=browse&action=set_sort&browse_id=' . $browse->id . '&type=video&sort=title', T_('Title'),'sort_video_title'); ?></th>
<?php
if (isset($video_type) && $video_type != 'video') {
    require AmpConfig::get('prefix') . '/templates/show_partial_' . $video_type . 's.inc.php';
}
?>
            <th class="cel_release_date"><?php echo Ajax::text('?page=browse&action=set_sort&browse_id=' . $browse->id . '&type=video&sort=release_date', T_('Release Date'),'sort_video_release_date'); ?></th>
            <th class="cel_codec"><?php echo Ajax::text('?page=browse&action=set_sort&browse_id=' . $browse->id . '&type=video&sort=codec', T_('Codec'),'sort_video_codec'); ?></th>
            <th class="cel_resolution"><?php echo Ajax::text('?page=browse&action=set_sort&browse_id=' . $browse->id . '&type=video&sort=resolution', T_('Resolution'),'sort_video_rez'); ?></th>
            <th class="cel_length"><?php echo Ajax::text('?page=browse&action=set_sort&browse_id=' . $browse->id . '&type=video&sort=length', T_('Time'),'sort_video_length'); ?></th>
            <th class="cel_tags"><?php echo T_('Tags'); ?></th>
            <?php if (AmpConfig::get('ratings')) { ?>
                <th class="cel_rating"><?php echo T_('Rating'); ?></th>
            <?php } ?>
            <?php if (AmpConfig::get('userflags')) { ?>
                <th class="cel_userflag"><?php echo T_('Fav.'); ?></th>
            <?php } ?>
            <th class="cel_action"><?php echo T_('Action'); ?></th>
        </tr>
    </tfoot>
</table>
<script src="<?php echo AmpConfig::get('web_path'); ?>/lib/javascript/tabledata.js" language="javascript" type="text/javascript"></script>
<?php if ($browse->get_show_header()) require AmpConfig::get('prefix') . '/templates/list_header.inc.php'; ?>
