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
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Module\Util\Ui;

$admin_path = AmpConfig::get_web_path('/admin');
/** @var array $filter */
/** @var int $num_users */
/** @var int $num_catalogs */
/** @var string $t_edit */
/** @var string $t_delete */
?>
<td class="cel_name"><?php echo scrub_out($filter['name']); ?></td>
<td class="cel_num_users"><?php echo $num_users; ?></td>
<td class="cel_num_catalogs"><?php echo $num_catalogs; ?></td>
<td class="cel_action">
<?php if (Access::check(AccessTypeEnum::INTERFACE, AccessLevelEnum::ADMIN)) { ?>
        <a href="<?php echo $admin_path; ?>/filter.php?action=show_edit&filter_id=<?php echo $filter['id']; ?>&filter_name=<?php echo urlencode($filter['name']); ?>">
            <?php echo Ui::get_material_symbol('edit', $t_edit); ?>
        </a>
        <?php if ($filter['id'] > 0) { ?>
           <a href="<?php echo $admin_path; ?>/filter.php?action=delete&filter_id=<?php echo $filter['id']; ?>&filter_name=<?php echo urlencode($filter['name']); ?>">
               <?php echo Ui::get_material_symbol('close', $t_delete); ?>
           </a>
        <?php } ?>
    <?php } ?>
</td>
