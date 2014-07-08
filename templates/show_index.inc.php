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
?>
<div id="now_playing">
    <?php show_now_playing(); ?>
</div> <!-- Close Now Playing Div -->
<!-- Randomly selected albums of the moment -->
<?php
if (Art::is_enabled()) {
    echo Ajax::observe('window', 'load', Ajax::action('?page=index&action=random_albums', 'random_albums'));
?>
<div id="random_selection" class="random_selection">
    <?php UI::show_box_top(T_('Albums of the Moment')); echo T_('Loading...'); UI::show_box_bottom(); ?>
</div>
<?php
if (AmpConfig::get('allow_video')) {
    echo Ajax::observe('window', 'load', Ajax::action('?page=index&action=random_videos', 'random_videos'));
?>
<div id="random_video_selection" class="random_selection">
    <?php UI::show_box_top(T_('Videos of the Moment')); echo T_('Loading...'); UI::show_box_bottom(); ?>
</div>
<?php } ?>
<?php } ?>
<!-- Recently Played -->
<div id="recently_played">
    <?php
        $data = Song::get_recently_played();
        Song::build_cache(array_keys($data));
        require_once AmpConfig::get('prefix') . '/templates/show_recently_played.inc.php';
    ?>
</div>
<!-- Shoutbox Objects, if shoutbox is enabled -->
<?php if (AmpConfig::get('sociable')) { ?>
<div id="shout_objects">
    <?php
        $shouts = Shoutbox::get_top('5');
        if (count($shouts)) {
            require_once AmpConfig::get('prefix') . '/templates/show_shoutbox.inc.php';
        }
    ?>
</div>
<?php } ?>
