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

$web_path = Config::get('web_path');

/* Flagged Information Gathering */
$flagged    = Flag::get_recent(10);

/* Disabled Information Gathering */
$songs = Song::get_disabled(10);
?>

<?php UI::show_box_top(T_('Last Ten Flagged Records')); ?>
    <?php require Config::get('prefix') . '/templates/show_flagged.inc.php'; ?>
<?php UI::show_box_bottom(); ?>

<?php UI::show_box_top(T_('Disabled Songs')); ?>
    <!-- Show Last 10 Disabled Songs -->&nbsp;
    <?php require Config::get('prefix') . '/templates/show_disabled_songs.inc.php'; ?>
    <div>
    <a class="button" href="<?php echo $web_path; ?>/admin/catalog.php?action=show_disabled"><?php echo T_('Show All'); ?>...</a>
    </div>
<?php UI::show_box_bottom(); ?>
