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
use Ampache\Module\Authorization\AccessFunctionEnum;
use Ampache\Repository\Model\Rating;
use Ampache\Repository\Model\Search;
use Ampache\Module\Authorization\Access;
use Ampache\Module\Api\Ajax;
use Ampache\Repository\Model\Browse;
use Ampache\Module\Util\Ui;
use Ampache\Module\Util\ZipHandlerInterface;
use Ampache\Repository\Model\User;
use Ampache\Repository\Model\Userflag;

/** @var Search $playlist */
/** @var list<int> $object_ids */

ob_start();
echo $playlist->getFullname();
$title    = ob_get_contents();
$web_path = AmpConfig::get_web_path('/client');
$browse   = new Browse();
$browse->set_type('playlist_media');
$browse->set_use_filters(false);
$browse->add_supplemental_object('search', $playlist->id);
$browse->set_static_content(false);
ob_end_clean();
Ui::show_box_top('<div id="smartplaylist_row_' . $playlist->id . '">' . $title . '</div>', 'box box_smartplaylist'); ?>
<?php if (User::is_registered() && AmpConfig::get('ratings')) { ?>
    <span id="rating_<?php echo $playlist->id; ?>_search">
    <?php echo Rating::show($playlist->id, 'search'); ?>
</span>
    <span id="userflag_<?php echo $playlist->id; ?>_search">
    <?php echo Userflag::show($playlist->id, 'search'); ?>
</span>
<?php } ?>
<div id="information_actions">
    <ul>
<?php global $dic; // @todo remove after refactoring
$zipHandler = $dic->get(ZipHandlerInterface::class);
if (Access::check_function(AccessFunctionEnum::FUNCTION_BATCH_DOWNLOAD) && $zipHandler->isZipable('search')) { ?>
        <li>
            <a class="nohtml" href="<?php echo $web_path; ?>/batch.php?action=search&id=<?php echo $playlist->id; ?>">
                <?php echo Ui::get_material_symbol('folder_zip', T_('Batch download')); ?>
                <?php echo T_('Batch download'); ?>
            </a>
        </li>
<?php } ?>
        <li>
            <?php echo Ajax::button_with_text('?page=random&action=send_playlist&random_type=search&random_id=' . $playlist->id, 'shuffle', T_('Random Play'), 'play_random_' . $playlist->id); ?>
        </li>
        <li>
            <?php echo Ajax::button_with_text('?action=basket&type=search&id=' . $playlist->id, 'new_window', T_('Add All To Temporary Playlist'), 'play_playlist'); ?>
        </li>
<?php if ($playlist->has_access()) { ?>
        <li>
            <a id="<?php echo 'edit_playlist_' . $playlist->id; ?>" onclick="showEditDialog('search_row', '<?php echo $playlist->id; ?>', '<?php echo 'edit_playlist_' . $playlist->id; ?>', '<?php echo addslashes(T_('Smart Playlist Edit')); ?>', '')">
                <?php echo Ui::get_material_symbol('edit', T_('Edit')); ?>
                <?php echo T_('Edit'); ?>
            </a>
        </li>
        <li>
            <a href="<?php echo $web_path; ?>/smartplaylist.php?action=delete_playlist&playlist_id=<?php echo $playlist->id; ?>">
                <?php echo Ui::get_material_symbol('close'); ?>
                <?php echo T_('Delete'); ?>
            </a>
        </li>
<?php } ?>
    </ul>
</div>

<form id="editplaylist" name="editplaylist" method="post" enctype="multipart/form-data" action="<?php echo $web_path; ?>/smartplaylist.php?action=show&playlist_id=<?php echo $playlist->id; ?>" enctype="multipart/form-data" style="Display:inline">
    <?php require Ui::find_template('show_rules.inc.php'); ?>
    <div class="formValidation">
        <input class="button" type="submit" value="<?php echo T_('Refresh'); ?>" onClick="$('#hiddenaction').val('refresh_playlist');" />&nbsp;&nbsp;
        <input class="button" type="submit" value="<?php echo T_('Save Changes'); ?>" onClick="$('#hiddenaction').val('update_playlist');" />&nbsp;&nbsp;
        <input class="button" type="submit" value="<?php echo T_('Save as Playlist'); ?>" onClick="$('#hiddenaction').val('save_as_playlist');" />&nbsp;&nbsp;
        <input type="hidden" id="hiddenaction" name="action" value="search" />
        <input type="hidden" name="browse_id" value="<?php echo $browse->id; ?>" />
        <input type="hidden" name="playlist_id" value="<?php echo $playlist->id; ?>" />
        <input type="hidden" name="playlist_type" value="<?php echo $playlist->type; ?>" />
        <input type="hidden" name="playlist_name" value="<?php echo $playlist->name; ?>" />
    </div>
</form>

<?php Ui::show_box_bottom(); ?>

<div>
<?php $browse->duration = Search::get_total_duration($object_ids);
$browse->show_objects($object_ids);
$browse->store(); ?>
</div>
