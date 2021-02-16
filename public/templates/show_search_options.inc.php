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
use Ampache\Module\Authorization\Access;
use Ampache\Module\Api\Ajax;
use Ampache\Module\Util\Ui;
use Ampache\Module\Util\ZipHandlerInterface;

?>
<?php Ui::show_box_top(T_('Options'), 'info-box'); ?>
<div id="information_actions">
<ul>
<li>
    <?php echo Ajax::button_with_text('?action=basket&type=browse_set&browse_id=' . $browse->id, 'add', T_('Add Search Results'), 'add_search_results'); ?>
</li>
    <?php
    // @todo remove after refactoring
    global $dic;
    $zipHandler = $dic->get(ZipHandlerInterface::class);
    if (Access::check_function('batch_download') && $zipHandler->isZipable((string) filter_input(INPUT_GET, 'type', FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES))) { ?>
<li>
    <a class="nohtml" href="<?php echo AmpConfig::get('web_path'); ?>/batch.php?action=browse&amp;type=<?php echo scrub_out((string) filter_input(INPUT_GET, 'type', FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES)); ?>&amp;browse_id=<?php echo $browse->id; ?>">
        <?php echo Ui::get_icon('batch_download', T_('Batch Download')); ?>
        <?php echo T_('Batch Download'); ?>
    </a>
</li>
    <?php
} ?>
</ul>
</div>
<?php Ui::show_box_bottom(); ?>
