<?php
/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
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
use Ampache\Module\System\AmpError;
use Ampache\Module\Util\Ui;
use Ampache\Module\Util\UiInterface;

/** @var UiInterface $ui */
/** @var string $feedUrl */
/** @var int $catalogId */
/** @var string $verificationSnippet */

?>
<?php Ui::show_box_top(T_('Subscribe to Podcast'), 'box box_add_podcast'); ?>
<form name="podcast" method="post" action="<?php echo AmpConfig::get('web_path'); ?>/podcast.php?action=create">
<table class="tabledata">
<tr>
    <td><?php echo T_('Podcast Feed URL'); ?></td>
    <td><input type="text" name="feed" value="<?php echo scrub_out($feedUrl); ?>" />
        <?php echo AmpError::display('feed'); ?>
    </td>
</tr>
<tr>
    <td><?php echo T_('Catalog'); ?></td>
    <td>
        <?php echo $ui->showCatalogSelect('catalog', $catalogId, '', false, 'podcast'); ?>
        <?php echo AmpError::display('catalog'); ?>
    </td>
</tr>
</table>
<div class="formValidation">
    <?php echo $verificationSnippet; ?>
    <input class="button" type="submit" value="<?php echo T_('Subscribe'); ?>" />
</div>
</form>
<?php Ui::show_box_bottom(); ?>