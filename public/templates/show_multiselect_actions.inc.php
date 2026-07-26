<?php

declare(strict_types=1);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2026
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

// show_multiselect_actions.inc.php

use Ampache\Module\Util\Ui;

/**
 * The action bar for a multi-select browse. Include it inside a [data-multiselect-scope] element together with
 * the table whose rows carry the checkboxes; src/js/multiselect.js fills the placeholders in each url.
 *
 * @var list<array{action: string, url: string, icon: string, text: string, confirm?: string}> $multiselect_actions
 */

if (empty($multiselect_actions)) {
    return;
} ?>
<div class="multiselect-actions multiselect-empty" data-multiselect-bar>
    <span class="multiselect-summary">
        <?php printf(T_('%s selected'), '<span data-multiselect-count>0</span>'); ?>
    </span>
    <?php foreach ($multiselect_actions as $multiselect_action) { ?>
    <a href="javascript:void(0);" aria-disabled="true"
       data-multiselect-action="<?php echo scrub_out($multiselect_action['action']); ?>"
       data-multiselect-url="<?php echo scrub_out($multiselect_action['url']); ?>"
        <?php if (!empty($multiselect_action['confirm'])) { ?>
       data-multiselect-confirm="<?php echo scrub_out($multiselect_action['confirm']); ?>"
        <?php } ?>
    >
        <?php echo Ui::get_material_symbol($multiselect_action['icon'], $multiselect_action['text']); ?>
        <span class="multiselect-label"><?php echo scrub_out($multiselect_action['text']); ?></span>
    </a>
    <?php } ?>
</div>
