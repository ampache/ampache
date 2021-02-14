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
use Ampache\Repository\Model\Playlist;
use Ampache\Repository\Model\Search;
use Ampache\Module\Authorization\Access;
use Ampache\Module\Api\Ajax;
use Ampache\Repository\Model\Browse;
use Ampache\Module\Util\Ui;
use Ampache\Module\Util\ZipHandlerInterface;

/** @var Playlist $playlist */

?>
<?php
ob_start();
require Ui::find_template('show_search_title.inc.php');
$title = ob_get_contents();
ob_end_clean();
Ui::show_box_top('<div id="smartplaylist_row_' . $playlist->id . '">' . $title . '</div>', 'box box_smartplaylist'); ?>
<div id="information_actions">
    <ul>
        <?php
        // @todo remove after refactoring
        global $dic;
        $zipHandler = $dic->get(ZipHandlerInterface::class);
        if (Access::check_function('batch_download') && $zipHandler->isZipable('search')) { ?>
        <li>
            <a class="nohtml" href="<?php echo AmpConfig::get('web_path'); ?>/batch.php?action=search&amp;id=<?php echo $playlist->id; ?>"><?php echo Ui::get_icon('batch_download', T_('Batch Download')); ?></a>
            <?php echo T_('Batch Download'); ?>
        </li>
            <?php
} ?>
        <li>
            <?php echo Ajax::button('?action=basket&type=search&id=' . $playlist->id, 'add', T_('Add All'), 'play_playlist'); ?>
            <?php echo T_('Add All'); ?>
        </li>
        <?php if ($playlist->has_access()) { ?>
        <li>
            <a href="<?php echo AmpConfig::get('web_path'); ?>/smartplaylist.php?action=delete_playlist&playlist_id=<?php echo $playlist->id; ?>">
                <?php echo Ui::get_icon('delete'); ?>
            </a>
            <?php echo T_('Delete'); ?>
        </li>
        <?php
    } ?>
    </ul>
</div>

<form id="editplaylist" name="editplaylist" method="post" action="<?php echo AmpConfig::get('web_path'); ?>/smartplaylist.php?action=update_playlist&playlist_id=<?php echo $playlist->id; ?>" enctype="multipart/form-data" style="Display:inline">
    <?php require Ui::find_template('show_rules.inc.php'); ?>
    <div class="formValidation">
        <input class="button" type="submit" value="<?php echo T_('Save Changes'); ?>" />
    </div>
</form>

<?php Ui::show_box_bottom(); ?>

<div>
<?php
    $browse = new Browse();
    $browse->set_type('playlist_media');
    $browse->add_supplemental_object('search', $playlist->id);
    $browse->set_static_content(false);
    $browse->duration = Search::get_total_duration($object_ids);
    $browse->show_objects($object_ids);
    $browse->store(); ?>
</div>
