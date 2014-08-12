<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU General Public License, version 2 (GPLv2)
 * Copyright 2001 - 2014 Ampache.org
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License v2
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 *
 */

$web_path = AmpConfig::get('web_path');
$theme_path = AmpConfig::get('theme_path') . '/templates';
?>
<link rel="stylesheet" href="<?php echo $web_path; ?>/templates/base.css" type="text/css" media="screen" />
<link rel="stylesheet" href="<?php echo $web_path . $theme_path; ?>/default.css" type="text/css" media="screen" />
<link rel="stylesheet" href="<?php echo $web_path . $theme_path; ?>/dark.css" type="text/css" media="screen" />
<?php
if (file_exists(AmpConfig::get('prefix') . $theme_path . '/icons.sprite.css')) {
?>
<link rel="stylesheet" href="<?php echo $web_path . $theme_path; ?>/icons.sprite.css" type="text/css" media="screen" />
<?php
}
?>
<?php
if (is_rtl(AmpConfig::get('lang'))
    && is_file(AmpConfig::get('prefix') . '/themes' . $theme_path . '/rtl.css')) {
?>
<link rel="stylesheet" href="<?php echo $web_path . $theme_path; ?>/rtl.css type="text/css" media="screen" />
<?php
}
?>
<link rel="stylesheet" href="<?php echo $web_path; ?>/modules/prettyPhoto/css/prettyPhoto.css" type="text/css" media="screen" />
<link rel="stylesheet" href="<?php echo $web_path; ?>/templates/jquery-ui.custom.css" type="text/css" media="screen" />
<link rel="stylesheet" href="<?php echo $web_path; ?>/templates/print.css" type="text/css" media="print" />
