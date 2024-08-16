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
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Repository\Model\Art;
use Ampache\Repository\Model\Catalog;
use Ampache\Module\Authorization\Access;
use Ampache\Module\Util\Ui;

/** @var Ampache\Repository\Model\Label $libitem */
/** @var string $cel_cover */

$name     = scrub_out((string)$libitem->get_fullname());
$web_path = AmpConfig::get_web_path('/client'); ?>
<td class="<?php echo $cel_cover; ?>">
    <?php Art::display('label', $libitem->id, $name, 1, $web_path . '/labels.php?action=show&label=' . $libitem->id); ?>
</td>
<td class="cel_label"><?php echo $libitem->get_f_link(); ?></td>
<td class="cel_category"><?php echo $libitem->category; ?></td>
<td class="cel_artists"><?php echo $libitem->artist_count; ?></td>
<td class="cel_country"><?php echo $libitem->country; ?></td>
<?php if ($libitem->active) {
    echo "<td class=\"cel_active\">" . T_('Active') . "</td>";
} else {
    echo "<td class=\"cel_active\">" . T_('Inactive') . "</td>";
} ?>
<td class="cel_action">
<?php if (!AmpConfig::get('use_auth') || Access::check(AccessTypeEnum::INTERFACE, AccessLevelEnum::USER)) {
    if (AmpConfig::get('sociable')) { ?>
    <a href="<?php echo $web_path; ?>/shout.php?action=show_add_shout&type=label&id=<?php echo $libitem->id; ?>">
        <?php echo Ui::get_material_symbol('comment', T_('Post Shout')); ?>
    </a>
    <?php }
    if (Catalog::can_remove($libitem)) { ?>
        <a id="<?php echo 'delete_label_' . $libitem->id; ?>" href="<?php echo $web_path; ?>/labels.php?action=delete&label_id=<?php echo $libitem->id; ?>">
            <?php echo Ui::get_material_symbol('close', T_('Delete')); ?>
        </a>
    <?php }
    } ?>
</td>
