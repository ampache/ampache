<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright 2001 - 2020 Ampache.org
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

use Lib\Cron;

?>
<?php UI::show_box_top(T_('Ampache Debug'), 'box box_debug_tools'); ?>
    <div id="information_actions">
        <ul>
            <li>
                <a class="nohtml" href="<?php echo AmpConfig::get('web_path'); ?>/admin/system.php?action=generate_config"><?php echo UI::get_icon('cog', T_('Generate Configuration File')) . ' ' . T_('Generate Configuration File'); ?></a>
            </li>
            <li>
                <a href="<?php echo AmpConfig::get('web_path'); ?>/admin/system.php?action=write_config"><?php echo UI::get_icon('cog', T_('Write New Config')) . ' ' . T_('Write New Config'); ?></a>
            </li>
            <li>
                <a href="<?php echo AmpConfig::get('web_path'); ?>/admin/system.php?action=reset_db_charset"><?php echo UI::get_icon('server_lightning', T_('Set Database Charset')) . ' ' . T_('Set Database Charset'); ?></a>
            </li>
            <li>
                <a href="<?php echo AmpConfig::get('web_path'); ?>/admin/system.php?action=clear_cache&type=song"><?php echo UI::get_icon('cog', T_('Clear Songs Cache')) . ' ' . T_('Clear Songs Cache'); ?></a>
            </li>
            <li>
                <a href="<?php echo AmpConfig::get('web_path'); ?>/admin/system.php?action=clear_cache&type=artist"><?php echo UI::get_icon('cog', T_('Clear Artists Cache')) . ' ' . T_('Clear Artists Cache'); ?></a>
            </li>
            <li>
                <a href="<?php echo AmpConfig::get('web_path'); ?>/admin/system.php?action=clear_cache&type=album"><?php echo UI::get_icon('cog', T_('Clear Albums Cache')) . ' ' . T_('Clear Albums Cache'); ?></a>
            </li>
        </ul>
    </div>

    <?php UI::show_box_top(T_('Ampache Update'), 'box'); ?>
        <div><?php echo T_('Installed Ampache version'); ?>: <?php echo AutoUpdate::get_current_version(); ?>.</div>
        <div><?php echo T_('Latest Ampache version'); ?>: <?php echo AutoUpdate::get_latest_version(); ?>.</div>
        <?php if ((string) AmpConfig::get('github_force_branch') !== '') {
    ?><?php echo "<div>" . T_('GitHub Branch') . ': "' . (string) AmpConfig::get('github_force_branch') . '"</div>';
} ?>
        <div><a class="nohtml" href="<?php echo AmpConfig::get('web_path'); ?>/admin/system.php?action=show_debug&autoupdate=force"><?php echo T_('Force check'); ?>...</a></div>
        <?php
        if (AutoUpdate::is_update_available()) {
            AutoUpdate::show_new_version();
        } ?>
        <br />
    <?php UI::show_box_bottom(); ?>

<?php if ((string) AmpConfig::get('cron_cache') !== '') { ?>
    <?php UI::show_box_top(T_('Ampache Cron'), 'box'); ?>
        <div><?php echo T_('The last cron was completed'); ?>: <?php echo get_datetime($time_format, Cron::get_cron_date()); ?></div>
        <br />
    <?php UI::show_box_bottom();
} ?>
    <?php UI::show_box_top(T_('PHP Settings'), 'box box_php_settings'); ?>
        <table class="tabledata">
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
            <tr class="<?php echo UI::flip_class(); ?>">
                <td><?php echo T_('Memory Limit'); ?></td>
                <td><?php echo ini_get('memory_limit'); ?></td>
            </tr>
            <tr class="<?php echo UI::flip_class(); ?>">
                <td><?php echo T_('Maximum Execution Time'); ?></td>
                <td><?php echo ini_get('max_execution_time'); ?></td>
            </tr>
            <tr class="<?php echo UI::flip_class(); ?>">
                <td><?php echo T_('Override Execution Time'); ?></td>
                <td><?php set_time_limit(0); echo ini_get('max_execution_time') ? T_('Failed') : T_('Succeeded'); ?></td>
            </tr>
            <tr class="<?php echo UI::flip_class(); ?>">
                <td><?php echo T_('Open Basedir'); ?></td>
                <td><?php echo ini_get('open_basedir'); ?></td>
            </tr>
            <tr class="<?php echo UI::flip_class(); ?>">
                <td><?php echo T_('Zlib Support'); ?></td>
                <td><?php echo print_bool(function_exists('gzcompress')); ?></td>
            </tr>
            <tr class="<?php echo UI::flip_class(); ?>">
                <td><?php echo T_('GD Support'); ?></td>
                <td><?php echo print_bool(function_exists('ImageCreateFromString')); ?></td>
            </tr>
            <tr class="<?php echo UI::flip_class(); ?>">
                <td><?php echo T_('Iconv Support'); ?></td>
                <td><?php echo print_bool(function_exists('iconv')); ?></td>
            </tr>
            <tr class="<?php echo UI::flip_class(); ?>">
                <td><?php echo T_('Gettext Support'); ?></td>
                <td><?php echo print_bool(function_exists('bindtextdomain')); ?></td>
            </tr>
            </tbody>
        </table>
    <?php UI::show_box_bottom(); ?>

    <?php UI::show_box_top(T_('Current Configuration'), 'box box_current_configuration'); ?>
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
    if ($key == 'database_password' || $key == 'mysql_password') {
        $value = '*********';
    }
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
        $value = print_bool($value);
    }

    // Be sure to print only scalar values
    if ($value === null || is_scalar($value)) { ?>
            <tr class="<?php echo UI::flip_class(); ?>">
                <td><strong><?php echo $key; ?></strong></td>
                <td><?php echo $value; ?></td>
            </tr>
<?php
            }
} ?>
            </tbody>
        </table>
    <?php UI::show_box_bottom(); ?>

<?php UI::show_box_bottom(); ?>
