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
use Ampache\Repository\Model\Preference;
use Ampache\Module\System\AutoUpdate;
use Ampache\Module\Util\EnvironmentInterface;
use Ampache\Module\Util\Ui;

global $dic;
$environment = $dic->get(EnvironmentInterface::class);

/** @var array $configuration */
/** @var int $lastCronDate */

$web_path = AmpConfig::get_web_path();

$admin_path = AmpConfig::get_web_path('/admin');

// don't share the database password and unset additional variables
unset(
    $configuration['database_password'],
    $configuration['load_time_begin'],
    $configuration['phpversion'],
);
// check your versions
$current_version = AutoUpdate::get_current_version();
$latest_version  = AutoUpdate::get_latest_version(); ?>
<?php Ui::show_box_top(T_('Ampache Debug'), 'box box_debug_tools'); ?>
    <div id="information_actions">
        <ul>
            <li>
                <a class="nohtml" href="<?php echo $admin_path; ?>/system.php?action=generate_config"><?php echo Ui::get_material_symbol('settings', T_('Generate Configuration File')) . ' ' . T_('Generate Configuration File'); ?></a>
            </li>
            <li>
                <a href="<?php echo $admin_path; ?>/system.php?action=write_config"><?php echo Ui::get_material_symbol('settings', T_('Write New Config')) . ' ' . T_('Write New Config'); ?></a>
            </li>
            <li>
                <a href="<?php echo $admin_path; ?>/system.php?action=reset_db_charset"><?php echo Ui::get_material_symbol('dns', T_('Set Database Charset')) . ' ' . T_('Set Database Charset'); ?></a>
            </li>
            <li>
                <a href="<?php echo $admin_path; ?>/system.php?action=clear_cache&type=song"><?php echo Ui::get_material_symbol('settings', T_('Clear Songs Cache')) . ' ' . T_('Clear Songs Cache'); ?></a>
            </li>
            <li>
                <a href="<?php echo $admin_path; ?>/system.php?action=clear_cache&type=artist"><?php echo Ui::get_material_symbol('settings', T_('Clear Artists Cache')) . ' ' . T_('Clear Artists Cache'); ?></a>
            </li>
            <li>
                <a href="<?php echo $admin_path; ?>/system.php?action=clear_cache&type=album"><?php echo Ui::get_material_symbol('settings', T_('Clear Albums Cache')) . ' ' . T_('Clear Albums Cache'); ?></a>
            </li>
<?php if (AmpConfig::get('perpetual_api_session')) { ?>
            <li>
                <a href="<?php echo $admin_path; ?>/system.php?action=clear_cache&type=perpetual_api_session"><?php echo Ui::get_material_symbol('settings', T_('Clear Perpetual API Sessions')) . ' ' . T_('Clear Perpetual API Sessions'); ?></a>
            </li>
<?php  } ?>
        </ul>
    </div>
<?php Ui::show_box_top(T_('Ampache Update'), 'box'); ?>
    <div><?php echo T_('Installed Ampache version'); ?>: <?php echo ($configuration['structure'] === 'public')
            ? $current_version
            : $current_version . ' (' . $configuration['structure'] . ')'; ?></div>
<?php if (AmpConfig::get('autoupdate', false)) { ?>
    <div><?php echo T_('Latest Ampache version'); ?>: <?php echo $latest_version; ?></div>
    <?php if ((string) AutoUpdate::is_force_git_branch() !== '') { ?>
        <?php echo "<div>" . T_('GitHub Branch') . ': ' . (string)AutoUpdate::is_force_git_branch() . '</div>';
    } ?>
    <div><a class="nohtml" href="<?php echo $admin_path; ?>/system.php?action=show_debug&autoupdate=force"><?php echo T_('Force check'); ?>...</a></div>
    <?php if ($current_version !== $latest_version || AutoUpdate::is_update_available()) {
        AutoUpdate::show_new_version();
    }
} ?>
    <br />
    <?php Ui::show_box_bottom(); ?>
<?php if (AmpConfig::get('cron_cache', false)) { ?>
    <?php Ui::show_box_top(T_('Ampache Cron'), 'box'); ?>
    <div><?php echo T_('The last cron was completed'); ?>: <?php echo get_datetime($lastCronDate); ?></div>
    <br />
    <?php Ui::show_box_bottom();
    } ?>
    <?php Ui::show_box_top(T_('PHP Settings'), 'box box_php_settings'); ?>
    <table class="tabledata striped-rows">
        <colgroup>
            <col id="col_php_setting">
            <col id="col_php_value">
        </colgroup>
        <thead>
        <tr class="th-top">
            <th class="cel_php_setting"><?php echo T_('Setting'); ?></th>
            <th class="cel_php_value"><?php echo T_('Value'); ?></th>
        </tr>
        </thead>
        <tbody>
        <tr>
            <td><?php echo T_('Version'); ?></td>
            <td><?php echo (string)phpversion(); ?></td>
        </tr>
        <tr>
            <td><?php echo T_('Memory Limit'); ?></td>
            <td><?php echo ini_get('memory_limit'); ?></td>
        </tr>
        <tr>
            <td><?php echo T_('Maximum Execution Time'); ?></td>
            <td><?php echo ini_get('max_execution_time'); ?></td>
        </tr>
        <tr>
            <td><?php echo T_('Override Execution Time'); ?></td>
            <td><?php set_time_limit(0);
echo ini_get('max_execution_time') ? T_('Failed') : T_('Succeeded'); ?></td>
        </tr>
        <tr>
            <td><?php echo T_('Open Basedir'); ?></td>
            <td><?php echo ini_get('open_basedir'); ?></td>
        </tr>
        <tr>
            <td><?php echo T_('Zlib Support'); ?></td>
            <td><?php echo Ui::printBool(function_exists('gzcompress')); ?></td>
        </tr>
        <tr>
            <td><?php echo T_('GD Support'); ?></td>
            <td><?php echo Ui::printBool(function_exists('imagecreatefromstring')); ?></td>
        </tr>
        <tr>
            <td><?php echo T_('Iconv Support'); ?></td>
            <td><?php echo Ui::printBool(function_exists('iconv')); ?></td>
        </tr>
        <tr>
            <td><?php echo T_('Gettext Support'); ?></td>
            <td><?php echo Ui::printBool(function_exists('bindtextdomain')); ?></td>
        </tr>
        <tr>
            <td><?php echo T_('PHP intl extension'); ?></td>
            <td><?php echo Ui::printBool($environment->check_php_intl()); ?></td>
        </tr>
        </tbody>
    </table>
    <?php Ui::show_box_bottom(); ?>
    <?php Ui::show_box_top(T_('Current Configuration'), 'box box_current_configuration'); ?>
    <table class="tabledata">
        <colgroup>
           <col id="col_configuration">
           <col id="col_value">
        </colgroup>
        <thead>
        <tr class="th-top">
            <th class="cel_configuration"><?php echo T_('Preference'); ?></th>
            <th class="cel_value"><?php echo T_('Value'); ?></th>
        </tr>
        </thead>
        <tbody>
<?php foreach ($configuration as $key => $value) {
    if (is_array($value)) {
        $string = '';
        foreach ($value as $setting) {
            if (is_array($setting)) {
                foreach ($setting as $array_value) {
                    $string .= $array_value . '<br />';
                }
            } else {
                $string .= $setting . '<br />';
            }
        }
        $value = $string;
    }
    if (Preference::is_boolean($key)) {
        $value = Ui::printBool($value);
    }

    // Be sure to print only scalar values
    if ($value === null || is_scalar($value)) { ?>
            <tr>
                <td><strong><?php echo $key; ?></strong></td>
                <td><?php echo $value; ?></td>
            </tr>
    <?php }
    } ?>
            </tbody>
    </table>
    <?php Ui::show_box_bottom(); ?>

<?php Ui::show_box_bottom(); ?>
