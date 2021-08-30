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

use Ampache\Config\AmpConfig;
use Ampache\Repository\Model\Catalog;
use Ampache\Repository\Model\Playlist;
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

$last_seen   = $client->last_seen ? get_datetime((int) $client->last_seen) : T_('Never');
$create_date = $client->create_date ? get_datetime((int) $client->create_date) : T_('Unknown');
$web_path    = AmpConfig::get('web_path');
$client->format(); ?>
<?php Ui::show_box_top($client->f_name); ?>
<div class="user_avatar">
<?php
if ($client->f_avatar) {
    echo $client->f_avatar . "<br /><br />";
} ?>
<?php if (AmpConfig::get('sociable')) {
    echo $userFollowStateRenderer->render(
        $client->getId(),
        Core::get_global('user')->getId()
    );

    $plugins = Plugin::get_plugins('display_user_field'); ?>
    <ul id="plugins_user_field">
<?php
    foreach ($plugins as $plugin_name) {
        $plugin = new Plugin($plugin_name);
        if ($plugin->load($client)) { ?>
        <li><?php $plugin->_plugin->display_user_field(); ?> </li>
<?php
        }
    } ?>
    </ul>
<?php
} ?>
</div>
<dl class="media_details">
    <dt><?php echo T_('Display Name'); ?></dt>
    <dd>
        <?php echo $client->f_name; ?>
        <?php if (Access::check('interface', 25) && AmpConfig::get('sociable')) { ?>
            <a id="<?php echo 'reply_pvmsg_' . $client->id ?>" href="<?php echo $web_path; ?>/pvmsg.php?action=show_add_message&to_user=<?php echo $client->username; ?>">
                <?php echo Ui::get_icon('mail', T_('Send private message')); ?>
            </a>
        <?php
    } ?>
        <?php if (Access::check('interface', 100)) { ?>
            <a href="<?php echo $web_path; ?>/admin/users.php?action=show_edit&user_id=<?php echo $client->id; ?>"><?php echo Ui::get_icon('edit', T_('Edit')); ?></a>
            <a href="<?php echo $web_path; ?>/admin/users.php?action=show_preferences&user_id=<?php echo $client->id; ?>"><?php echo Ui::get_icon('preferences', T_('Preferences')); ?></a>
        <?php
    } elseif ($client->id == Core::get_global('user')->id) { ?>
            <a href="<?php echo $web_path; ?>/preferences.php?tab=account"><?php echo Ui::get_icon('edit', T_('Edit')); ?></a>

        <?php
    }
    if (AmpConfig::get('use_now_playing_embedded')) { ?>
        <a href="<?php echo $web_path; ?>/now_playing.php?user_id=<?php echo $client->id; ?>" target="_blank"><?php echo Ui::get_icon('play_preview', T_('Now Playing')); ?></a>
 <?php
    } ?>

    </dd>
    <dt><?php echo T_('Member Since'); ?></dt>
    <dd><?php echo $create_date; ?></dd>
    <dt><?php echo T_('Last Seen'); ?></dt>
    <dd><?php echo $last_seen; ?></dd>
    <?php if (Access::check('interface', 50)) { ?>
    <dt><?php echo T_('Activity'); ?></dt>
    <dd><?php echo $client->f_usage; ?></dd>
        <?php if (AmpConfig::get('statistical_graphs') && is_dir(__DIR__ . '/../../vendor/szymach/c-pchart/src/Chart/')) {
        ?>
            <a href="<?php echo $web_path; ?>/stats.php?action=graph&user_id=<?php echo $client->id; ?>"><?php echo Ui::get_icon('statistics', T_('Graphs')); ?></a>
        <?php
    } ?>
    </dd>
    <?php
} ?>
    <dt><?php echo T_('Status'); ?></dt>
    <dd>
    <?php if ($client->is_logged_in() && $client->is_online()) { ?>
        <i style="color:green;"><?php echo T_('User is Online Now'); ?></i>
    <?php
    } else { ?>
        <i style="color:red;"><?php echo T_('User is Offline Now'); ?></i>
    <?php
    } ?>
    </dd>
</dl><br />
<?php Ui::show_box_bottom(); ?>

<div class="tabs_wrapper">
    <div id="tabs_container">
        <ul id="tabs">
            <li class="tab_active"><a href="#recentlyplayed"><?php echo T_('Recently Played'); ?></a></li>
            <?php if (AmpConfig::get('allow_upload')) { ?>
            <li><a href="#artists"><?php echo T_('Artists'); ?></a></li>
            <?php
    } ?>
            <li><a href="#playlists"><?php echo T_('Playlists'); ?></a></li>
            <?php if (AmpConfig::get('sociable')) { ?>
            <li><a href="#following"><?php echo T_('Following'); ?></a></li>
            <li><a href="#followers"><?php echo T_('Followers'); ?></a></li>
            <li><a href="#timeline"><?php echo T_('Timeline'); ?></a></li>
            <?php
    } ?>
        </ul>
    </div>
    <div id="tabs_content">
        <div id="recentlyplayed" class="tab_content" style="display: block;">
        <?php
        $tmp_playlist = new Tmp_Playlist(Tmp_Playlist::get_from_userid($client->id));
        $object_ids   = $tmp_playlist->get_items();
        if (count($object_ids) > 0) {
            Ui::show_box_top(T_('Active Playlist')); ?>
        <table>
            <tr>
                <td>
                    <?php
                        foreach ($object_ids as $object_data) {
                            $type       = array_shift($object_data);
                            $class_name = ObjectTypeToClassNameMapper::map($type);
                            $object     = new $class_name(array_shift($object_data));
                            $object->format();
                            echo $object->f_link; ?>
                        <br />
                    <?php
                        } ?>
                </td>
            </tr>
        </table><br />
        <?php Ui::show_box_bottom(); ?>
        <?php
        } ?>
        <?php
            $data = Song::get_recently_played($client->id);
            Song::build_cache(array_keys($data));
            $user_id = $client->id;
            require Ui::find_template('show_recently_played.inc.php'); ?>
        </div>
        <?php if (AmpConfig::get('allow_upload')) { ?>
        <div id="artists" class="tab_content">
        <?php
            $sql         = Catalog::get_uploads_sql('artist', $client->id);
            $browse      = new Browse();
            $browse->set_type('artist', $sql);
            $browse->set_simple_browse(true);
            $browse->show_objects();
            $browse->store(); ?>
        </div>
        <?php
        } ?>
        <div id="playlists" class="tab_content">
        <?php
            $playlist_ids = Playlist::get_playlists($client->id);
            $browse       = new Browse();
            $browse->set_type('playlist');
            $browse->set_simple_browse(false);
            $browse->show_objects($playlist_ids);
            $browse->store(); ?>
        </div>
        <?php if (AmpConfig::get('sociable')) { ?>
        <div id="following" class="tab_content">
        <?php
            $browse                = new Browse();
            $browse->set_type('user');
            $browse->set_simple_browse(false);
            $browse->show_objects($following);
            $browse->store(); ?>
        </div>
        <div id="followers" class="tab_content">
        <?php
            $browse               = new Browse();
            $browse->set_type('user');
            $browse->set_simple_browse(false);
            $browse->show_objects($followers);
            $browse->store(); ?>
        </div>
            <div id="timeline" class="tab_content">
                <?php
                if (Preference::get_by_user($client->id, 'allow_personal_info_recent')) {
                    Useractivity::build_cache($activities);
                    foreach ($activities as $activity_id) {
                        echo $userActivityRenderer->show(
                            new Useractivity($activity_id)
                        );
                    }
                } ?>
            </div>
        <?php
        } ?>
    </div>
</div>

