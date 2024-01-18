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

/** @var int $catalogId */

?>
<form name="podcast" method="post" enctype="multipart/form-data" action="<?php echo AmpConfig::get('web_path'); ?>/podcast.php?action=import_podcasts">
    <table class="tabledata">
        <tr>
            <td><?php echo T_('File'); ?> (<?php echo T_('Format: opml') ?>)</td>
            <td>
                <input type="file" id="podcast_import_file" name="import_file" value="" />
                <?php echo AmpError::display('import_file'); ?>
            </td>
        </tr>
        <tr>
            <td><?php echo T_('Catalog'); ?></td>
            <td>
                <?php show_catalog_select('catalog', $catalogId, '', false, 'podcast'); ?>
                <?php echo AmpError::display('catalog'); ?>
            </td>
        </tr>
    </table>
    <div class="formValidation">
        <?php echo Core::form_register('import_podcasts'); ?>
        <input class="button" type="submit" value="<?php echo T_('Import'); ?>" />
    </div>
</form>
