<?php

declare(strict_types=0);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2023
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
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Module\Statistics\Stats;
use Ampache\Module\System\Plugin\PluginTypeEnum;
use Ampache\Module\Util\Upload;
use Ampache\Repository\Model\Catalog;
use Ampache\Repository\Model\Plugin;
use Ampache\Repository\Model\Preference;
use Ampache\Repository\Model\Song;
use Ampache\Repository\Model\Tmp_Playlist;
use Ampache\Repository\Model\User;
use Ampache\Repository\Model\Useractivity;
use Ampache\Module\Authorization\Access;
use Ampache\Module\System\Core;
use Ampache\Repository\Model\Browse;
use Ampache\Module\User\Activity\UserActivityRendererInterface;
use Ampache\Module\User\Following\UserFollowStateRendererInterface;
use Ampache\Module\Util\ObjectTypeToClassNameMapper;
use Ampache\Module\Util\Ui;

/** @var UserActivityRendererInterface $userActivityRenderer */
/** @var UserFollowStateRendererInterface $userFollowStateRenderer */
/** @var User $client */
/** @var int[] $following */
/** @var int[] $followers */
/** @var int[] $activities */

/** @var User $current_user */
$current_user = Core::get_global('user');
$last_seen    = $client->last_seen ? get_datetime((int) $client->last_seen) : T_('Never');
$create_date  = $client->create_date ? get_datetime((int) $client->create_date) : T_('Unknown');
$web_path     = (string)AmpConfig::get('web_path', '');
$allow_upload = Upload::can_upload($current_user);
$client->format();
Ui::show_box_top((string)$client->get_fullname()); ?>
<?php if ($client->id > 0) { ?>
<div class="user_avatar">
<?php if ($client->f_avatar) {
    echo $client->f_avatar . "<br /><br />";
}
    if (AmpConfig::get('sociable')) {
        echo $userFollowStateRenderer->render(
            $client,
            $current_user
        );

        $plugins = Plugin::get_plugins(PluginTypeEnum::USER_FIELD_WIDGET); ?>
    <ul id="plugins_user_field">
<?php foreach ($plugins as $plugin_name) {
    $plugin = new Plugin($plugin_name);
    if ($plugin->_plugin !== null && $plugin->load($client)) { ?>
        <li><?php $plugin->_plugin->display_user_field(); ?> </li>
<?php } ?>
<?php
} ?>
    </ul>
<?php
    } ?>
</div>
<dl class="media_details">
    <dt><?php echo T_('Display Name'); ?></dt>
    <dd>
        <?php echo $client->get_fullname(); ?>
        <?php if (Access::check(AccessTypeEnum::INTERFACE, AccessLevelEnum::USER) && AmpConfig::get('sociable')) { ?>
            <a id="<?php echo 'reply_pvmsg_' . $client->id; ?>" href="<?php echo $web_path; ?>/pvmsg.php?action=show_add_message&to_user=<?php echo $client->username; ?>">
                <?php echo Ui::get_icon('mail', T_('Send private message')); ?>
            </a>
        <?php } ?>
        <?php if (Access::check(AccessTypeEnum::INTERFACE, AccessLevelEnum::ADMIN)) { ?>
            <a href="<?php echo $web_path; ?>/admin/users.php?action=show_edit&user_id=<?php echo $client->id; ?>"><?php echo Ui::get_icon('edit', T_('Edit')); ?></a>
            <a href="<?php echo $web_path; ?>/admin/users.php?action=show_preferences&user_id=<?php echo $client->id; ?>"><?php echo Ui::get_icon('preferences', T_('Preferences')); ?></a>
        <?php } elseif ($client->id == $current_user->id) { ?>
            <a href="<?php echo $web_path; ?>/preferences.php?tab=account"><?php echo Ui::get_icon('edit', T_('Edit')); ?></a>

        <?php } ?>
<?php if (AmpConfig::get('use_now_playing_embedded')) { ?>
        <a href="<?php echo $web_path; ?>/now_playing.php?user_id=<?php echo $client->id; ?>" target="_blank"><?php echo Ui::get_icon('play_preview', T_('Now Playing')); ?></a>
<?php } ?>
<?php if (AmpConfig::get('show_wrapped')) { ?>
        <a href="<?php echo $web_path; ?>/mashup.php?action=wrapped&user_id=<?php echo $client->id; ?>&year=<?php echo date('Y') ?: '' ?>" target="_blank"><?php echo Ui::get_icon('info', T_('Wrapped')); ?></a>
<?php } ?>
    </dd>
    <dt><?php echo T_('Member Since'); ?></dt>
    <dd><?php echo $create_date; ?></dd>
    <dt><?php echo T_('Last Seen'); ?></dt>
    <dd><?php echo $last_seen; ?></dd>
    <?php if (Access::check(AccessTypeEnum::INTERFACE, AccessLevelEnum::CONTENT_MANAGER)) { ?>
    <dt><?php echo T_('Activity'); ?></dt>
    <dd><?php echo $client->f_usage; ?>
        <?php if (AmpConfig::get('statistical_graphs') && is_dir(__DIR__ . '/../../vendor/szymach/c-pchart/src/Chart/')) { ?>
            <a href="<?php echo $web_path; ?>/stats.php?action=graph&user_id=<?php echo $client->id; ?>"><?php echo Ui::get_icon('statistics', T_('Graphs')); ?></a>
        <?php } ?>
    </dd>
    <?php } ?>
    <dt><?php echo T_('Status'); ?></dt>
    <dd>
    <?php if ($client->is_logged_in() && $client->is_online()) { ?>
        <i style="color:green;"><?php echo T_('User is Online Now'); ?></i>
    <?php } else { ?>
        <i style="color:red;"><?php echo T_('User is Offline Now'); ?></i>
    <?php } ?>
    </dd>
</dl><br />
<?php } ?>
<?php Ui::show_box_bottom(); ?>
<div class="tabs_wrapper">
    <div id="tabs_container">
        <ul id="tabs">
            <li class="tab_active"><a href="#recently_played"><?php echo T_('Played'); ?></a></li>
            <li ><a href="#recently_skipped"><?php echo T_('Skipped'); ?></a></li>
            <?php if ($allow_upload) { ?>
            <li><a href="#artists"><?php echo T_('Artists'); ?></a></li>
            <?php } ?>
            <li><a href="#playlists"><?php echo T_('Playlists'); ?></a></li>
            <?php if (AmpConfig::get('sociable')) { ?>
            <li><a href="#following"><?php echo T_('Following'); ?></a></li>
            <li><a href="#followers"><?php echo T_('Followers'); ?></a></li>
            <li><a href="#timeline"><?php echo T_('Timeline'); ?></a></li>
            <?php } ?>
        </ul>
    </div>
    <div id="tabs_content">
        <div id="recently_played" class="tab_content" style="display: block;">
        <?php $current_list = Tmp_Playlist::get_from_username((string)$client->username);
if ($current_list) {
    $tmp_playlist = new Tmp_Playlist($current_list);
    $object_ids   = $tmp_playlist->get_items();
    if (count($object_ids) > 0) {
        Ui::show_box_top(T_('Active Playlist')); ?>
                <table>
                    <tr>
                        <td>
        <?php foreach ($object_ids as $object_data) {
            $type      = array_shift($object_data);
            $className = ObjectTypeToClassNameMapper::map($type);
            /** @var Ampache\Repository\Model\playable_item $object */
            $object = new $className(array_shift($object_data));
            echo $object->get_f_link(); ?>
            <br />
            <?php
        } ?>
                        </td>
                    </tr>
                </table><br />
        <?php Ui::show_box_bottom();
    }
}
$ajax_page  = 'stats';
$limit      = AmpConfig::get('popular_threshold', 10);
$no_refresh = true;
if (AmpConfig::get('home_recently_played_all')) {
    $data = Stats::get_recently_played($client->getId(), 'stream', null, true);
    require_once Ui::find_template('show_recently_played_all.inc.php');
} else {
    $data = Stats::get_recently_played($client->getId(), 'stream', 'song', true);
    Song::build_cache(array_keys($data));
    require Ui::find_template('show_recently_played.inc.php');
} ?>
        </div>
        <div id="recently_skipped" class="tab_content">
<?php $ajax_page = 'stats';
$limit           = AmpConfig::get('popular_threshold', 10);
$data            = Stats::get_recently_played($client->getId(), 'skip', 'song', true);
Song::build_cache(array_keys($data));
require Ui::find_template('show_recently_skipped.inc.php'); ?>
        </div>
<?php if ($allow_upload) { ?>
        <div id="artists" class="tab_content">
    <?php $sql = Catalog::get_uploads_sql('artist', $client->id);
    $browse    = new Browse();
    $browse->set_type('artist', $sql);
    $browse->set_simple_browse(true);
    $browse->show_objects();
    $browse->store(); ?>
        </div>
<?php } ?>
        <div id="playlists" class="tab_content">
        <?php
$show_all     = (($current_user->id ?? 0) == $client->id || ($current_user->access ?? 0) == 100);
$playlist_ids = $client->get_playlists($show_all);
$browse       = new Browse();
$browse->set_type('playlist');
$browse->set_use_filters(false);
$browse->set_simple_browse(false);
$browse->show_objects($playlist_ids);
$browse->store(); ?>
        </div>
<?php if (AmpConfig::get('sociable')) { ?>
        <div id="following" class="tab_content">
    <?php $browse = new Browse();
    $browse->set_type('user');
    $browse->set_use_filters(false);
    $browse->set_simple_browse(false);
    $browse->show_objects($following);
    $browse->store(); ?>
        </div>
        <div id="followers" class="tab_content">
    <?php $browse = new Browse();
    $browse->set_type('user');
    $browse->set_use_filters(false);
    $browse->set_simple_browse(false);
    $browse->show_objects($followers);
    $browse->store(); ?>
        </div>
            <div id="timeline" class="tab_content">
    <?php if (Preference::get_by_user($client->id, 'allow_personal_info_recent')) {
        Useractivity::build_cache($activities);
        foreach ($activities as $activity_id) {
            echo $userActivityRenderer->show(new Useractivity($activity_id));
        }
    } ?>
            </div>
<?php } ?>
    </div>
</div>

