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

/**
 * This page has a few tabs, as such we need to figure out which tab we are on
 * and display the information accordingly
 */

use Ampache\Config\AmpConfig;
use Ampache\Module\Authorization\Access;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Module\System\Core;
use Ampache\Module\Util\Ui;
use Ampache\Module\Util\UiInterface;

/** @var UiInterface $ui */
/** @var array<string, mixed> $preferences */
/** @var string $fullname */

$tab = Core::get_request('tab');
if (!empty($tab)) {
    /* HINT: Username FullName */
    Ui::show_box_top(sprintf(T_('Editing %s Preferences'), $fullname), 'box box_preferences');
    if ($tab !== 'account' && $tab !== 'modules') {
        debug_event('show_preferences.inc', (string) $tab, 5); ?>
<form method="post" name="preferences" action="<?php echo AmpConfig::get('web_path'); ?>/preferences.php?action=update_preferences" enctype="multipart/form-data">
<?php $ui->showPreferenceBox(($preferences[$tab] ?? [])); ?>
<div class="formValidation">
    <input class="button" type="submit" value="<?php echo T_('Update Preferences'); ?>" />
    <?php echo Core::form_register('update_preference'); ?>
    <input type="hidden" name="tab" value="<?php echo scrub_out($tab); ?>" />
    <input type="hidden" name="method" value="<?php echo scrub_out(Core::get_request('action')); ?>" />
    <?php if (Access::check(AccessTypeEnum::INTERFACE, AccessLevelEnum::ADMIN)) { ?>
        <input type="hidden" name="user_id" value="<?php echo scrub_out(Core::get_request('user_id')); ?>" />
    <?php } ?>
</div>
<?php
    }  // end if not account
    if ($tab === 'account') {
        $client   = Core::get_global('user');
        $template = (AmpConfig::get('simple_user_mode') && !Access::check(AccessTypeEnum::INTERFACE, AccessLevelEnum::ADMIN)) ? 'show_account_simple.inc.php' : 'show_account.inc.php';
        require Ui::find_template($template);
    }
}?>
</form>

<?php Ui::show_box_bottom(); ?>
