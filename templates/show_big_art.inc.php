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

$htmllang = str_replace("_","-",AmpConfig::get('lang'));
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?php echo $htmllang; ?>" lang="<?php echo $htmllang; ?>">
<head>
<link rel="shortcut icon" href="<?php echo AmpConfig::get('web_path'); ?>/favicon.ico" />
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo AmpConfig::get('site_charset'); ?>" />
<title><?php echo AmpConfig::get('site_title'); ?> - <?php echo T_("Album Art"); ?></title>
</head>
<body onload="self.resizeTo(document.images[0].width+30, document.images[0].height+70)">
<?php
echo "<a href=\"javascript:window.close()\" title=\"" . T_('Click to close window') . "\">";
echo "<img src=\"" . AmpConfig::get('web_path') . "/image.php?object_id=" . scrub_out($_GET['id']) . "&object_type=album&auth=" . session_id() . "\" border=\"0\" alt=\"\" />";
echo "</a>";
?>
</body>
</html>
