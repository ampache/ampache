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

$web_path       = AmpConfig::get('web_path');
$theme_path     = AmpConfig::get('theme_path') . '/templates';
$theme_color    = AmpConfig::get('theme_color');
$theme_css_base = AmpConfig::get('theme_css_base');
if (!is_array($theme_css_base)) {
    $theme_css_base = array($theme_css_base);
}
foreach ($theme_css_base as $css_base) { ?>
    <link rel="stylesheet" href="<?php echo $web_path . $theme_path . '/' . $css_base[0]; ?>" type="text/css" media="<?php echo $css_base[1]; ?>" />
<?php
} ?>
<link rel="stylesheet" href="<?php echo $web_path . '/templates/base.css'; ?>" type="text/css" media="screen" />
<link rel="stylesheet" href="<?php echo $web_path . $theme_path . '/' . $theme_color . '.css'; ?>" type="text/css" media="screen" />
<link rel="stylesheet" href="<?php echo $web_path . '/templates/print.css'; ?>" type="text/css" media="print" />
<?php
if (is_rtl(AmpConfig::get('lang'))
    && is_file(AmpConfig::get('prefix') . $theme_path . '/rtl.css')) { ?>
<link rel="stylesheet" href="<?php echo $web_path . $theme_path; ?>/rtl.css" type="text/css" media="screen" />
<?php } ?>
<link rel="stylesheet" href="<?php echo $web_path; ?>/lib/components/prettyphoto/css/prettyPhoto.css" type="text/css" media="screen" />
<link rel="stylesheet" href="<?php echo $web_path . '/templates/jquery-ui.custom.css'; ?>" type="text/css" media="screen" />
<?php UI::show_custom_style(); ?>
