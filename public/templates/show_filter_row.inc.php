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
use Ampache\Module\Authorization\Access;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Module\Util\Ui;

$web_path = (string)AmpConfig::get('web_path', '');
/** @var array $filter */
/** @var int $num_users */
/** @var int $num_catalogs */
?>
<td class="cel_name"><?php echo $filter['name']; ?></td>
<td class="cel_num_users"><?php echo $num_users; ?></td>
<td class="cel_num_catalogs"><?php echo $num_catalogs; ?></td>
<td class="cel_action">
<?php if (Access::check(AccessTypeEnum::INTERFACE, AccessLevelEnum::ADMIN)) { ?>
        <a href="<?php echo $web_path; ?>/admin/filter.php?action=show_edit&amp;filter_id=<?php echo $filter['id']; ?>&amp;filter_name=<?php echo $filter['name']; ?>">
            <?php echo Ui::get_icon('edit', T_('Edit')); ?>
        </a>
        <?php if ($filter['id'] > 0) { ?>
           <a href="<?php echo $web_path; ?>/admin/filter.php?action=delete&filter_id=<?php echo $filter['id']; ?>&amp;filter_name=<?php echo $filter['name']; ?>">
               <?php echo Ui::get_icon('delete', T_('Delete')); ?>
           </a>
        <?php } ?>
    <?php } ?>
</td>
