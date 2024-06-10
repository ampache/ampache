<?php

declare(strict_types=0);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2024
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

use Ampache\Config\AmpConfig;
use Ampache\Module\Statistics\Stats;
use Ampache\Module\System\Plugin\PluginTypeEnum;
use Ampache\Repository\Model\Plugin;
use Ampache\Repository\Model\Song;
use Ampache\Module\Api\Ajax;
use Ampache\Module\System\Core;
use Ampache\Module\Util\Ui;
use Ampache\Repository\Model\User;

?>
<div id="browse_header">
<?php require_once Ui::find_template('show_form_browse.inc.php'); ?>
</div> <!-- Close browse_header Div -->

<?php $user = Core::get_global('user');
if ($user instanceof User) {
    foreach (Plugin::get_plugins(PluginTypeEnum::HOMEPAGE_WIDGET) as $plugin_name) {
        $plugin = new Plugin($plugin_name);
        if ($plugin->_plugin !== null && $plugin->load($user)) {
            $plugin->_plugin->display_home();
        }
    }
} ?>

<?php if (AmpConfig::get('home_now_playing')) { ?>
<div id="now_playing">
    <?php show_now_playing(); ?>
</div> <!-- Close Now Playing Div -->
<?php } ?>
<!-- Randomly selected Albums of the Moment -->
<?php if (AmpConfig::get('home_moment_albums')) {
    $showAlbum = AmpConfig::get('album_group');
    if ($showAlbum) {
        echo Ajax::observe('window', 'load', Ajax::action('?page=index&action=random_albums', 'random_albums'));
    } else {
        echo Ajax::observe('window', 'load', Ajax::action('?page=index&action=random_album_disks', 'random_album_disks'));
    } ?>
<div id="random_selection" class="random_selection">
    <?php Ui::show_box_top(T_('Albums of the Moment'));
    echo T_('Loading...');
    Ui::show_box_bottom(); ?>
</div>
<?php
}
if (AmpConfig::get('home_moment_videos') && AmpConfig::get('allow_video')) {
    echo Ajax::observe('window', 'load', Ajax::action('?page=index&action=random_videos', 'random_videos')); ?>
<div id="random_video_selection" class="random_selection">
    <?php Ui::show_box_top(T_('Videos of the Moment'));
    echo T_('Loading...');
    Ui::show_box_bottom(); ?>
</div>
    <?php
} ?>
<?php if (AmpConfig::get('home_recently_played')) { ?>
<!-- Recently Played -->
<div id="recently_played">
<?php
    $user      = Core::get_global('user');
    $user_id   = $user->id ?? -1;
    $ajax_page = 'index';
    if (AmpConfig::get('home_recently_played_all')) {
        $data = Stats::get_recently_played($user_id);
        require_once Ui::find_template('show_recently_played_all.inc.php');
    } else {
        $data = Stats::get_recently_played($user_id, 'stream', 'song');
        Song::build_cache(array_keys($data));
        require_once Ui::find_template('show_recently_played.inc.php');
    } ?>
</div>
<?php } ?>
