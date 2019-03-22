<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPLv3)
 * Copyright 2001 - 2017 Ampache.org
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */


if (@is_readable(AmpConfig::get('prefix') . '/config/motd.php')) {
    echo '<div id="motd">';
    require_once AmpConfig::get('prefix') . '/config/motd.php';
    echo '</div><br />';
}

foreach (Plugin::get_plugins('display_home') as $plugin_name) {
    $plugin = new Plugin($plugin_name);
    if ($plugin->load($GLOBALS['user'])) {
        $plugin->_plugin->display_home();
    }
}
?>
<?php if (AmpConfig::get('home_now_playing')) {
    ?>
<div id="now_playing">
    <?php show_now_playing(); ?>
</div> <!-- Close Now Playing Div -->
<?php
} ?>
<!-- Randomly selected albums of the moment -->
<?php
if (Art::is_enabled()) {
        if (AmpConfig::get('home_moment_albums')) {
            echo Ajax::observe('window', 'load', Ajax::action('?page=index&action=random_albums', 'random_albums')); ?>
<div id="random_selection" class="random_selection">
    <?php UI::show_box_top(T_('Albums of the Moment'));
            echo T_('Loading...');
            UI::show_box_bottom(); ?>
</div>
<?php
        }
        if (AmpConfig::get('home_moment_videos') && AmpConfig::get('allow_video')) {
            echo Ajax::observe('window', 'load', Ajax::action('?page=index&action=random_videos', 'random_videos')); ?>
<div id="random_video_selection" class="random_selection">
    <?php UI::show_box_top(T_('Videos of the Moment'));
            echo T_('Loading...');
            UI::show_box_bottom(); ?>
</div>
    <?php
        } ?>
<?php
    } ?>
<?php if (AmpConfig::get('home_recently_played')) {
        ?>
<!-- Recently Played -->
<div id="recently_played">
    <?php
        $data = Song::get_recently_played();
        Song::build_cache(array_keys($data));
        require_once AmpConfig::get('prefix') . UI::find_template('show_recently_played.inc.php'); ?>
</div>
<?php
    } ?>
