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
use Ampache\Module\Authorization\Access;
use Ampache\Module\Api\Ajax;
use Ampache\Module\Authorization\AccessFunctionEnum;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Module\Playback\Stream;
use Ampache\Module\Playlist\PlaylistLoaderInterface;
use Ampache\Module\System\Core;
use Ampache\Module\Util\Ui;
use Ampache\Module\Util\ZipHandlerInterface;
use Ampache\Repository\Model\Broadcast;
use Ampache\Repository\Model\Democratic;
use Ampache\Repository\Model\LibraryItemLoaderInterface;
use Ampache\Repository\Model\Live_Stream;
use Ampache\Repository\Model\Podcast_Episode;
use Ampache\Repository\Model\Song;
use Ampache\Repository\Model\Song_Preview;
use Ampache\Repository\Model\User;
use Ampache\Repository\Model\Video;

$user_id = (Core::get_global('user') instanceof User) ? Core::get_global('user')->id : -1; ?>
<ul id="rb_action">
    <li>
        <?php echo Ajax::button('?page=stream&action=basket', 'play_circle', T_('Play'), 'rightbar_play'); ?>
    </li>
<?php if (Access::check(AccessTypeEnum::INTERFACE, AccessLevelEnum::USER)) { ?>
        <li id="pl_add">
            <?php echo Ui::get_material_symbol('playlist_add', T_('Add to playlist')); ?>
            <ul id="pl_action_additems" class="submenu">
                <li>
                    <?php echo Ajax::text('?page=playlist&action=append_item', T_('Add to New Playlist'), 'rb_create_playlist'); ?>
                </li>
            <?php global $dic;
    $playlists = $dic->get(PlaylistLoaderInterface::class)->loadByUserId(
        $user_id
    );
    foreach ($playlists as $playlist) { ?>
                <li>
                    <?php echo Ajax::text('?page=playlist&action=append_item&playlist_id=' . $playlist->id, $playlist->getFullname(), 'rb_append_playlist_' . $playlist->id); ?>
                </li>
            <?php } ?>
            </ul>
        </li>
<?php }
global $dic; // @todo remove after refactoring
$zipHandler = $dic->get(ZipHandlerInterface::class);
if (Access::check_function(AccessFunctionEnum::FUNCTION_BATCH_DOWNLOAD) && $zipHandler->isZipable('tmp_playlist')) { ?>
    <li>
        <a class="nohtml" href="<?php echo AmpConfig::get_web_path(); ?>/batch.php?action=tmp_playlist&id=<?php echo Core::get_global('user')?->playlist?->id; ?>">
            <?php echo Ui::get_material_symbol('folder_zip', T_('Batch download')); ?>
        </a>
    </li>
<?php } ?>
    <li>
    <?php echo Ajax::button('?action=basket&type=clear_all', 'close', T_('Clear Playlist'), 'rb_clear_playlist'); ?>
    </li>
    <li id="rb_add">
      <?php echo Ui::get_material_symbol('add_circle', T_('Add dynamic items')); ?>
        <ul id="rb_action_additems" class="submenu">
            <li>
                <?php echo Ajax::text('?page=random&action=song', T_('Random song'), 'rb_add_random_song'); ?>
            </li>
            <li>
                <?php echo Ajax::text('?page=random&action=artist', T_('Random artist'), 'rb_add_random_artist'); ?>
            </li>
            <li>
                <?php echo Ajax::text('?page=random&action=album', T_('Random album'), 'rb_add_random_album'); ?>
            </li>
            <li>
                <?php echo Ajax::text('?page=random&action=playlist', T_('Random playlist'), 'rb_add_random_playlist'); ?>
            </li>
        </ul>
    </li>
    <li id="rb_reload">
        <?php echo Ajax::button('?action=basket_refresh', 'refresh', T_('Refresh'), 'rb_refresh'); ?>
    </li>
</ul>
<?php if (AmpConfig::get('play_type') == 'localplay') {
    require_once Ui::find_template('show_localplay_control.inc.php');
} ?>
<ul id="rb_current_playlist" class="striped-rows">

<?php $objects = [];
// FIXME :: this is kludgy
if (!defined('NO_SONGS') && Core::get_global('user') instanceof User && Core::get_global('user')->playlist) {
    $objects = Core::get_global('user')->playlist->get_items();
}
// Limit the number of objects we show here
if (count($objects) > 100) {
    $truncated = (count($objects) - 100);
    $objects   = array_slice($objects, 0, 100, true);
}

global $dic;
$libraryItemLoader = $dic->get(LibraryItemLoaderInterface::class);

foreach ($objects as $object_data) {
    $uid = $object_data['track_id'];

    $object = $libraryItemLoader->load(
        $object_data['object_type'],
        $object_data['object_id'],
        [Broadcast::class, Democratic::class, Live_Stream::class, Podcast_Episode::class, Song::class, Song_Preview::class, Video::class,]
    );
    if ($object !== null) {
        ?>
    <li>
      <?php echo $object->get_f_link();
        echo Ajax::button('?action=current_playlist&type=delete&id=' . $uid, 'close', T_('Delete'), 'rightbar_delete_' . $uid, '', 'delitem'); ?>
    </li>
<?php
    }
} if (!count($objects)) { ?>
    <li><span class="nodata"><?php echo T_('No items'); ?></span></li>
<?php }
if (isset($truncated)) { ?>
    <li>
        <?php echo $truncated . ' ' . T_('More'); ?>...
    </li>
<?php } ?>
</ul>
<?php
// We do a little magic here to force a reload depending on preference
// We do this last because we want it to load, and we want to know if there is anything
// to even pass
if (count($objects)) {
    Stream::run_playlist_method();
} ?>

<script>
    $(document).ready(function() {
        // necessary evils for time being
        jsAmpConfigPlayType = "<?php echo AmpConfig::get('play_type'); ?>";
        jsBasketCount = <?php echo (Core::get_global('user') instanceof User && Core::get_global('user')->playlist) ? count(Core::get_global('user')->playlist->get_items()) : 0; ?>;
        RightbarInit();
    });
</script>