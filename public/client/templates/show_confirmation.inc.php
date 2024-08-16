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
use Ampache\Module\System\Core;
use Ampache\Module\Util\Ui;

/** @var string $title */
/** @var string $text */
/** @var string $path */
/** @var string $form_name */
/** @var int $cancel */

$confirmation = Core::form_register($form_name); ?>
<?php Ui::show_box_top(scrub_out($title), 'box box_confirmation'); ?>
<?php echo $text; ?>
    <br />
    <form method="post" action="<?php echo $path; ?>" style="display:inline;">
        <input type="submit" value="<?php echo T_('Continue'); ?>" />
        <?php echo $confirmation; ?>
    </form>
<?php if ($cancel) { ?>
    <form method="post" action="<?php echo AmpConfig::get_web_path('/client') . '/' . return_referer(); ?>" style="display:inline;">
        <input type="submit" value="<?php echo T_('Cancel'); ?>" />
        <?php echo $confirmation; ?>
    </form>
<?php } ?>
<?php Ui::show_box_bottom(); ?>
