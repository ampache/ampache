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
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Module\System\Core;
use Ampache\Module\Util\AjaxUriRetrieverInterface;
use Ampache\Module\Util\Ui;
use Ampache\Module\Util\UiInterface;
use Ampache\Repository\Model\Browse;

global $dic;
$ui = $dic->get(UiInterface::class);

/** @var UiInterface $ui */
/** @var Browse $browse */
/** @var array<int, array{id: int, name: string, is_hidden: int, count: int}> $object_ids */
/** @var string $browse_type */
/** @var string $countOrder */

$webPath = AmpConfig::get_web_path();

$ui->show(
    'show_form_genre.inc.php',
    ['type' => $browse_type]
); ?>
<div id="information_actions">
    <h3><?php echo T_('Order'); ?></h3>
    <ul>
        <li>
            <?php if ($countOrder === 'name') { ?>
                <a href="<?php echo $webPath; ?>/browse.php?action=tag&type=<?php echo $browse->get_type(); ?>&sort=count">
                    <?php echo Ui::get_material_symbol('sort', T_('# Items')); ?>
                    <?php echo T_('# Items'); ?>
                </a>
            <?php } else { ?>
                <a href="<?php echo $webPath; ?>/browse.php?action=tag&type=<?php echo $browse->get_type(); ?>">
                    <?php echo Ui::get_material_symbol('sort_by_alpha', T_('Name')); ?>
                    <?php echo T_('Name'); ?>
                </a>
            <?php } ?>
</div>
<?php Ajax::start_container('tag_filter'); ?>
<div class="tag_container">
    <div class="tag_button">
        <span id="click_tag_no_genre"><?php echo scrub_out('[' . T_('No Genre') . ']'); ?></span>
        <?php echo Ajax::observe('click_tag_no_genre', 'click', Ajax::action('?page=browse&action=browse&browse_id=' . $browse->id . '&key=no_genre&tag=-1', '')); ?>
    </div>
</div>
<?php foreach ($object_ids as $data) { ?>
    <div class="tag_container">
        <div class="tag_button">
            <span id="click_tag_<?php echo $data['id']; ?>"><?php echo scrub_out($data['name'] . ' (' . $data['count'] . ')'); ?></span>
            <?php echo Ajax::observe('click_tag_' . $data['id'], 'click', Ajax::action('?page=browse&action=browse&browse_id=' . $browse->id . '&key=tag&tag=' . $data['id'], '')); ?>
        </div>
        <?php if (Access::check(AccessTypeEnum::INTERFACE, AccessLevelEnum::CONTENT_MANAGER)) { ?>
        <div class="tag_actions">
            <ul>
                <li>
                    <a class="tag_edit" id="<?php echo 'edit_tag_' . $data['id']; ?>" onclick="showEditDialog('tag_row', '<?php echo $data['id']; ?>', '<?php echo 'edit_tag_' . $data['id']; ?>', '<?php echo addslashes(T_('Edit')); ?>', 'click_tag_', '<?php echo '&browse_id=' . $browse->getId(); ?>')">
                        <?php echo Ui::get_material_symbol('edit', T_('Edit')); ?>
                    </a>
                </li>
                <li>
                    <a class="tag_delete" href="<?php echo $dic->get(AjaxUriRetrieverInterface::class)->getAjaxUri(); ?>?page=tag&action=delete&tag_id=<?php echo $data['id']; ?>" onclick="return confirm('<?php echo T_('Do you really want to delete this Tag?'); ?>');"><?php echo Ui::get_material_symbol('close', T_('Delete')); ?></a>
                </li>
            </ul>
        </div>
    <?php } ?>
    </div>
<?php } ?>

<br /><br /><br />
<?php
if (isset($_GET['show_tag'])) {
    $show_tag = (int) (Core::get_get('show_tag')); ?>
<script>
$(document).ready(function () {
    <?php echo Ajax::action('?page=browse&action=browse&browse_id=' . $browse->id . '&key=tag&tag=' . $show_tag, ''); ?>
});
</script>
<?php
} ?>
<?php if (!count($object_ids)) { ?>
<span class="fatalerror"><?php echo T_('Not Enough Data'); ?></span>
<?php } ?>
<?php Ajax::end_container(); ?>
