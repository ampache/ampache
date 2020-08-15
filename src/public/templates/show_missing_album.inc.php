<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright 2001 - 2020 Ampache.org
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 */

$web_path = AmpConfig::get('web_path');

// Title for this album
$title = scrub_out($walbum->name) . '&nbsp;(' . $walbum->year . ')';
$title .= '&nbsp;-&nbsp;' . $walbum->f_artist_link; ?>
<?php UI::show_box_top($title, 'info-box missing'); ?>
<div class="item_art">
<?php
// Attempt to find the art.
$art                      = new Art($walbum->mbid, 'album');
$options['artist']        = $artist->name;
$options['album_name']    = $walbum->name;
$options['keyword']       = $artist->name . " " . $walbum->name;
$images                   = $art->gather($options, '1');

if (count($images) > 0 && !empty($images[0]['url'])) {
    $name = '[' . $artist->name . '] ' . scrub_out($walbum->name);

    $image = $images[0]['url'];

    echo "<a href=\"" . $image . "\" rel=\"prettyPhoto\">";
    echo "<img src=\"" . $image . "\" alt=\"" . $name . "\" alt=\"" . $name . "\" height=\"128\" width=\"128\" />";
    echo "</a>\n";
} ?>
</div>
<div id="information_actions">
<h3><?php echo T_('Actions'); ?>:</h3>
<ul>
    <li>
        <?php echo T_('Actions'); ?>:
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
    $browse->show_objects($walbum->songs); ?>
</div>
