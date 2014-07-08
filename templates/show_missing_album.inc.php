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

// Title for this album
$title = scrub_out($walbum->name) . '&nbsp;(' . $walbum->year . ')';
$title .= '&nbsp;-&nbsp;' . $walbum->f_artist_link;
?>
<?php UI::show_box_top($title,'info-box missing'); ?>
<div class="item_art">
<?php
// Attempt to find the art.
$art = new Art($walbum->mbid, 'album');
$options['artist']     = $artist->name;
$options['album_name']    = $walbum->name;
$options['keyword']    = $artist->name . " " . $walbum->name;
$images = $art->gather($options, '1');

if (count($images) > 0 && !empty($images[0]['url'])) {
    $name = '[' . $artist->name . '] ' . scrub_out($walbum->name);

    $image = $images[0]['url'];

    echo "<a href=\"". $image ."\" rel=\"prettyPhoto\">";
    echo "<img src=\"" . $image . "\" alt=\"".$name."\" alt=\"".$name."\" height=\"128\" width=\"128\" />";
    echo "</a>\n";
}
?>
</div>
<div id="information_actions">
<h3><?php echo T_('Actions'); ?>:</h3>
<ul>
    <li>
        <?php echo T_('Wanted actions'); ?>:
        <div id="wanted_action_<?php echo $walbum->mbid; ?>">
        <?php $walbum->show_action_buttons(); ?>
        </div>
    </li>
</ul>
</div>
<?php UI::show_box_bottom(); ?>
<div id="additional_information">
&nbsp;
</div>
<div>
<?php
    $browse = new Browse();
    $browse->set_type('song_preview');
    $browse->set_static_content(true);
    $browse->show_objects($walbum->songs);
?>
</div>
