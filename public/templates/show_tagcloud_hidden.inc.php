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

use Ampache\Module\Authorization\Access;
use Ampache\Module\Api\Ajax;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Module\Util\AjaxUriRetrieverInterface;
use Ampache\Module\Util\Ui;
use Ampache\Module\Util\UiInterface;
use Ampache\Repository\Model\Browse;

$tag_types = array(
    'artist' => T_('Artist'),
    'album' => T_('Album'),
    'song' => T_('Song'),
    'video' => T_('Video'),
    'tag_hidden' => T_('Hidden'),
);

global $dic;
$ui = $dic->get(UiInterface::class);

/** @var UiInterface $ui */
/** @var Browse $browse2 */
/** @var list<array{id: int, name: string}> $object_ids */
/** @var string $browse_type */

$ui->show(
    'show_form_genre.inc.php',
    [
        'type' => 'tag_hidden'
    ]
); ?>
<?php Ajax::start_container('tag_filter'); ?>
<?php foreach ($object_ids as $data) { ?>
    <div class="tag_container">
        <div class="tag_button">
            <span id="click_tag_hidden_<?php echo $data['id']; ?>"><?php echo scrub_out($data['name']); ?></span>
        </div>
        <?php if (Access::check(AccessTypeEnum::INTERFACE, AccessLevelEnum::CONTENT_MANAGER)) { ?>
        <div class="tag_actions">
            <ul>
                <li>
                    <a class="tag_hidden_edit" id="<?php echo 'tag_hidden_row' . $data['id']; ?>" onclick="showEditDialog('tag_hidden_row', '<?php echo $data['id']; ?>', '<?php echo 'edit_tag_hidden_' . $data['id']; ?>', '<?php echo addslashes(T_('Edit')); ?>', 'click_tag_')">
                        <?php echo Ui::get_icon('edit', T_('Edit')); ?>
                    </a>
                </li>
                <li>
                    <a class="tag_hidden_delete" href="<?php echo $dic->get(AjaxUriRetrieverInterface::class)->getAjaxUri(); ?>?page=tag&action=delete&tag_id=<?php echo $data['id']; ?>" onclick="return confirm('<?php echo T_('Do you really want to delete this Tag?'); ?>');"><?php echo Ui::get_icon('delete', T_('Delete')); ?></a>
                </li>
            </ul>
        </div>
    <?php } ?>
    </div>
<?php } ?>

<br /><br /><br />
<?php Ajax::end_container(); ?>
