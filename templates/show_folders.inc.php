<?php

declare(strict_types=1);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2026
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

// show_folders.inc.php

use Ampache\Config\AmpConfig;
use Ampache\Gui\GuiFactoryInterface;
use Ampache\Gui\TalFactoryInterface;
use Ampache\Module\Api\Ajax;
use Ampache\Module\Authorization\Access;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Module\Authorization\GatekeeperFactoryInterface;
use Ampache\Module\Util\Ui;
use Ampache\Repository\Model\Folder;
use Ampache\Repository\Model\Podcast_Episode;
use Ampache\Repository\Model\Song;
use Ampache\Repository\Model\User;
use Ampache\Repository\Model\Video;

/** @var Ampache\Repository\Model\Browse $browse */
/** @var Ampache\Repository\Model\Folder|null $folder */
/** @var string[] $object_ids */

$web_path = AmpConfig::get_web_path();

// A pinned collection hands its members straight to this browse, so there is no folder being walked into
$folder = $folder ?? new Folder(-1);

$access25         = Access::check(AccessTypeEnum::INTERFACE, AccessLevelEnum::USER);
$show_direct_play = AmpConfig::get('directplay');
$directplay_limit = AmpConfig::get('direct_play_limit', 500);
// folder_row data and options
$thcount           = 9;
$show_ratings      = User::is_registered() && (AmpConfig::get('ratings'));
$original_year     = AmpConfig::get('use_original_year');
$show_played_times = AmpConfig::get('show_played_times');
// translate once
$name_text   = T_('Name');
$items_text  = T_('# Items');
$count_text  = T_('Played');
$rating_text = T_('Rating');
$action_text = T_('Actions');
// mashup and grid view need different css
$cel_cover   = "cel_cover";
$cel_folder  = "cel_folder";
$cel_counter = "cel_counter";
$css_class   = '';
$folder_link = Ajax::text('?page=browse&action=set_sort&browse_id=' . $browse->id . '&sort=name', $name_text, 'folder_sort_name');
$rating_link = Ajax::text('?page=browse&action=set_sort&browse_id=' . $browse->id . '&sort=rating', $rating_text, 'folder_sort_rating');

if ($browse->is_show_header()) {
    require Ui::find_template('list_header.inc.php');
} ?>
<form method="post" id="reorder_folder_<?php echo $folder->id; ?>">
    <table class="tabledata striped-rows<?php echo $css_class; ?>" data-objecttype="folder">
        <thead>
            <tr class="th-top">
                <th class="cel_play essential"></th>
                <th class="<?php echo $cel_cover; ?> optional"><?php echo T_('Art'); ?></th>
                <th class="<?php echo $cel_folder; ?> essential persist"><?php echo $folder_link; ?></th>
                <th class="cel_add essential"></th>
                <th class="cel_songs optional"><?php echo $items_text; ?></th>
<?php if ($show_played_times) { ?>
                <th class="<?php echo $cel_counter; ?> optional"><?php echo $count_text; ?></th>
<?php } ?>
<?php if ($show_ratings) {
    ++$thcount; ?>
                <th class="cel_ratings optional"><?php echo $rating_link; ?></th>
<?php } ?>
                <th class="cel_action essential"><?php echo $action_text; ?></th>
            </tr>
        </thead>
        <tbody>
<?php global $dic;
$talFactory = $dic->get(TalFactoryInterface::class);
$guiFactory = $dic->get(GuiFactoryInterface::class);
$gatekeeper = $dic->get(GatekeeperFactoryInterface::class)->createGuiGatekeeper();

/* Foreach through the objects e.g. folder-12 song-125 podcast_episode-233 */
foreach ($object_ids as $object) {
    // A bare id is a folder: that is what a collection pinned to folders hands over, having no types to send
    if (preg_match('/^[0-9]+$/', (string) $object)) {
        $object_type = 'folder';
        $object_id   = (int) $object;
    } else {
        preg_match('/([a-z_]+)-([0-9]+)/', (string) $object, $matches);
        $object_type = $matches[1] ?? null;
        $object_id   = (int) ($matches[2] ?? 0);
    }

    $libitem = null;
    switch ($object_type) {
        case 'folder':
            $libitem = new Folder($object_id);
            break;
        case 'podcast_episode':
            $libitem = new Podcast_Episode($object_id);
            break;
        case 'song':
            $libitem = new Song($object_id);
            break;
        case 'video':
            $libitem = new Video($object_id);
            break;
    }

    if ($libitem === null || $libitem->isNew()) {
        continue;
    }

    // The temporary playlist queues the item itself, so a folder holding nothing playable has nothing to offer;
    // the add-to-list dialog is a different question, because a collection curates the folder rather than plays it
    $show_temp_add = $access25;
    if ($libitem instanceof Folder && $directplay_limit > 0) {
        $show_temp_add = $access25 && $libitem->playable && ($libitem->object_count > 0 && $libitem->object_count <= $directplay_limit);
    } ?>
            <tr id="<?php echo $object_type . '_' . $libitem->getId(); ?>" class="libitem_menu" data-object-type="<?php echo $object_type; ?>" data-object-id="<?php echo $libitem->getId(); ?>">
    <?php $content = $talFactory->createTalView()
            ->setContext('USER_IS_REGISTERED', User::is_registered())
            ->setContext('USING_RATINGS', User::is_registered() && (AmpConfig::get('ratings')))
            ->setContext('FOLDER', $guiFactory->createFolderViewAdapter($gatekeeper, $folder, $libitem, $object_type))
            ->setContext('IS_SHOW_PLAYED_TIMES', $show_played_times)
            ->setContext('IS_SHOW_PLAYLIST_ADD', $show_temp_add)
            ->setContext('IS_SHOW_LIST_ADD', $access25)
            ->setContext('CLASS_COVER', $cel_cover)
            ->setContext('CLASS_FOLDER', $cel_folder)
            ->setContext('CLASS_COUNTER', $cel_counter)
            ->setTemplate('folder_row.xhtml')
            ->render();
    echo $content; ?>
            </tr>
<?php } ?>
<?php if (!count($object_ids)) { ?>
            <tr>
                <td colspan="<?php echo $thcount; ?>"><span class="nodata"></span></td>
            </tr>
<?php } ?>
        </tbody>
        <tfoot>
            <tr class="th-bottom">
                <th class="cel_play"></th>
                <th class="<?php echo $cel_cover; ?>"><?php echo T_('Art'); ?></th>
                <th class="<?php echo $cel_folder; ?>"><?php echo $name_text; ?></th>
                <th class="cel_add"></th>
                <th class="cel_songs"><?php echo $items_text; ?></th>
<?php if ($show_played_times) { ?>
                <th class="<?php echo $cel_counter; ?> optional"><?php echo $count_text; ?></th>
<?php } ?>
<?php if ($show_ratings) { ?>
                    <th class="cel_ratings optional"><?php echo $rating_text; ?></th>
<?php } ?>
                <th class="cel_action"><?php echo $action_text; ?></th>
            </tr>
        </tfoot>
    </table>
</form>

<?php show_table_render(); ?>
<?php if ($browse->is_show_header()) {
    require Ui::find_template('list_header.inc.php');
} ?>