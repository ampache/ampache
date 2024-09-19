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

$web_path   = AmpConfig::get_web_path('/client');
$admin_path = AmpConfig::get_web_path('/admin'); ?>
<div id="information_actions" style="height: 200px; width: 600px;">
    <ul style="float: left;">
        <li>
            <a class="option-list" href="<?php echo $admin_path; ?>/catalog.php?action=gather_media_art"><?php echo T_('Gather All Art'); ?></a>
        </li>
        <li>
            <a class="option-list" href="<?php echo $admin_path; ?>/catalog.php?action=update_all_file_tags"><?php echo T_('Update All File Tags'); ?></a>
        </li>
        <li>
            <a class="option-list" href="<?php echo $admin_path; ?>/catalog.php?action=show_disabled"><?php echo T_('Show Disabled Songs'); ?></a>
        </li>
        <li>
            <a class="option-list" href="<?php echo $admin_path; ?>/catalog.php?action=add_to_all_catalogs"><?php echo T_('Add to All'); ?></a>
        </li>
        <li>
            <a class="option-list" href="<?php echo $admin_path; ?>/catalog.php?action=update_all_catalogs"><?php echo T_('Verify All'); ?></a>
        </li>
        <li>
            <a class="option-list" href="<?php echo $admin_path; ?>/catalog.php?action=clean_all_catalogs"><?php echo T_('Clean All'); ?></a>
        </li>
        <li>
            <a class="option-list" href="<?php echo $admin_path; ?>/catalog.php?action=full_service"><?php echo T_('Update All'); ?></a>
        </li>
        <li>
            <a class="option-list" href="javascript:NavigateTo('<?php echo $admin_path; ?>/catalog.php?action=clear_stats');" onclick="return confirm('<?php echo T_('Are you sure?'); ?>');"><?php echo T_('Clear Stats'); ?></a>
        </li>
    </ul>
    <form style="padding-left: 250px;" method="post" action="<?php echo $admin_path; ?>/catalog.php?action=update_from">
        <table class="tabledata2" style="width: 100%;">
            <tr>
                <td><?php echo T_('Add new files from:'); ?></td>
                <td><input type="text" name="add_path" value="/" /></td>
            </tr>
            <tr>
                <td><?php echo T_('Clean deleted files in:'); ?></td>
                <td><input type="text" name="clean_path" value="/" /></td>
            </tr>
            <tr>
                <td><?php echo T_('Update existing files in:'); ?></td>
                <td><input type="text" name="update_path" value="/" /></td>
            </tr>
            <tr>
                <td><?php /* HINT: example path /media/music */ ?><?php echo T_('Example path:') ?></td>
                <td style="padding-left: 10px;">/media/music</td>
            </tr>
            <tr>
                <td colspan="2" style="text-align:right;"><input type="submit" value="<?php echo T_('Update'); ?>" /></td>
            </tr>
        </table>
    </form>
</div>
