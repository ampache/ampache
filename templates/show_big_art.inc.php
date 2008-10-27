<?php
/*

 Copyright (c) 2001 - 2006 Ampache.org
 All rights reserved.

 This program is free software; you can redistribute it and/or
 modify it under the terms of the GNU General Public License v2
 as published by the Free Software Foundation.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with this program; if not, write to the Free Software
 Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.

*/

$htmllang = str_replace("_","-",Config::get('lang'));
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?php echo $htmllang; ?>" lang="<?php echo $htmllang; ?>">
<head>
<link rel="shortcut icon" href="<?php echo Config::get('web_path'); ?>/favicon.ico" />
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo Config::get('site_charset'); ?>" />
<title><?php echo Config::get('site_title'); ?> - <?php echo _("Album Art"); ?></title>
</head>
<body onload="self.resizeTo(document.images[0].width+30, document.images[0].height+70)">
<?php
echo "<a href=\"javascript:window.close()\" title=\"" . _('Click to close window') . "\">";
echo "<img src=\"" . Config::get('web_path') . "/image.php?id=" . scrub_out($_GET['id']) . "&amp;sid=" . session_id() . "\" border=\"0\" alt=\"\" />";
echo "</a>";
?>
</body>
</html>

