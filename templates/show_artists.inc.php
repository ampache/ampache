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

session_start();

$web_path = AmpConfig::get('web_path');
$thcount = 8;
?>
<?php if ($browse->get_show_header()) require AmpConfig::get('prefix') . '/templates/list_header.inc.php'; ?>
<table class="tabledata" cellpadding="0" cellspacing="0" data-objecttype="artist">
    <thead>
        <tr class="th-top">
            <th class="cel_play essential"></th>
            <th class="cel_artist essential persist"><?php echo Ajax::text('?page=browse&action=set_sort&browse_id=' . $browse->id . '&type=artist&sort=name', T_('Artist'),'artist_sort_name'); ?></th>
            <th class="cel_add essential"></th>
            <th class="cel_songs optional"><?php echo T_('Songs');  ?></th>
            <th class="cel_albums optional"><?php echo T_('Albums'); ?></th>
            <th class="cel_time optional"><?php echo T_('Time'); ?></th>
            <th class="cel_tags optional"><?php echo T_('Tags'); ?></th>
        <?php if (AmpConfig::get('ratings')) { ++$thcount; ?>
            <th class="cel_rating optional"><?php echo T_('Rating'); ?></th>
        <?php } ?>
        <?php if (AmpConfig::get('userflags')) { ++$thcount; ?>
            <th class="cel_userflag optional"><?php echo T_('Fav.'); ?></th>
        <?php } ?>
            <th class="cel_action essential"><?php echo T_('Action'); ?></th>
        </tr>
    </thead>
    <tbody>
        <?php
        // Cache the ratings we are going to use
        if (AmpConfig::get('ratings')) { Rating::build_cache('artist',$object_ids); }
        if (AmpConfig::get('userflags')) { Userflag::build_cache('artist',$object_ids); }

        /* Foreach through every artist that has been passed to us */
        foreach ($object_ids as $artist_id) {
                $libitem = new Artist($artist_id, $_SESSION['catalog']);
                $libitem->format();
        ?>
        <tr id="artist_<?php echo $libitem->id; ?>" class="<?php echo UI::flip_class(); ?>">
            <?php require AmpConfig::get('prefix') . '/templates/show_artist_row.inc.php'; ?>
        </tr>
        <?php } //end foreach ($artists as $artist) ?>
        <?php if (!count($object_ids)) { ?>
        <tr class="<?php echo UI::flip_class(); ?>">
            <td colspan="<?php echo $thcount; ?>"><span class="nodata"><?php echo T_('No artist found'); ?></span></td>
        </tr>
        <?php } ?>
    </tbody>
    <tfoot>
        <tr class="th-bottom">
            <th class="cel_play essential"></th>
            <th class="cel_artist essential persist"><?php echo Ajax::text('?page=browse&action=set_sort&browse_id=' . $browse->id . '&type=artist&sort=name', T_('Artist'),'artist_sort_name'); ?></th>
            <th class="cel_add essential"></th>
            <th class="cel_songs optional"><?php echo T_('Songs');  ?></th>
            <th class="cel_albums optional"><?php echo T_('Albums'); ?></th>
            <th class="cel_time essential"><?php echo T_('Time'); ?></th>
            <th class="cel_tags optional"><?php echo T_('Tags'); ?></th>
        <?php if (AmpConfig::get('ratings')) { ?>
            <th class="cel_rating optional"><?php echo T_('Rating'); ?></th>
        <?php } ?>
        <?php if (AmpConfig::get('userflags')) { ?>
            <th class="cel_userflag optional"><?php echo T_('Fav.'); ?></th>
        <?php } ?>
            <th class="cel_action essential"> <?php echo T_('Action'); ?> </th>
        </tr>
    </tfoot>
</table>
<script src="<?php echo AmpConfig::get('web_path'); ?>/lib/javascript/tabledata.js" language="javascript" type="text/javascript"></script>
<?php if ($browse->get_show_header()) require AmpConfig::get('prefix') . '/templates/list_header.inc.php'; ?>
