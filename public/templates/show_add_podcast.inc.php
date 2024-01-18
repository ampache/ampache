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
use Ampache\Module\System\AmpError;
use Ampache\Module\System\Core;
use Ampache\Module\Util\Ui;

/** @var int $catalog_id */
/** @var string $feed */

Ui::show_box_top(T_('Subscribe to Podcast'), 'box box_add_podcast'); ?>
<form name="podcast" method="post" action="<?php echo AmpConfig::get('web_path'); ?>/podcast.php?action=create">
<table class="tabledata">
<tr>
    <td><?php echo T_('Podcast Feed URL'); ?></td>
    <td><input type="text" name="feed" value="<?php echo scrub_out($feed); ?>" />
        <?php echo AmpError::display('feed'); ?>
    </td>
</tr>
<tr>
    <td><?php echo T_('Catalog'); ?></td>
    <td>
        <?php show_catalog_select('catalog', $catalog_id, '', false, 'podcast'); ?>
        <?php echo AmpError::display('catalog'); ?>
    </td>
</tr>
</table>
<div class="formValidation">
    <?php echo Core::form_register('add_podcast'); ?>
    <input class="button" type="submit" value="<?php echo T_('Subscribe'); ?>" />
</div>
</form>
<?php Ui::show_box_bottom(); ?>