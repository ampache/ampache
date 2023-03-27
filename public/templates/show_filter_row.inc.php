<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright 2001 - 2022 Ampache.org
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
use Ampache\Module\Util\Ui;

$web_path = AmpConfig::get('web_path');
/** @var string $filter */
/** @var int $num_users */
/** @var int $num_catalogs */
/** @var int $filter_id */
?>
<td class="cel_name"><?php echo $filter?></td>
<td class="cel_num_users"><?php echo $num_users ?></td>
<td class="cel_num_catalogs"><?php echo $num_catalogs ?></td>
<td class="cel_action">
<?php if (Access::check('interface', 100)) { ?>
        <a href="<?php echo $web_path; ?>/admin/filter.php?action=show_edit&amp;filter_id=<?php echo $filter_id; ?>&amp;filter_name=<?php echo $filter; ?>">
            <?php echo Ui::get_icon('edit', T_('Edit')); ?>
        </a>
        <?php if ($filter_id > 0) { ?>
           <a href="<?php echo $web_path; ?>/admin/filter.php?action=delete&filter_id=<?php echo $filter_id; ?>&amp;filter_name=<?php echo $filter; ?>">
               <?php echo Ui::get_icon('delete', T_('Delete')); ?>
           </a>
        <?php } ?>
    <?php } ?>
</td>