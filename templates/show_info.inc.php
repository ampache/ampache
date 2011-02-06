<?php
/* vim:set tabstop=8 softtabstop=8 shiftwidth=8 noexpandtab: */
/**
 * Show Information
 *
 * PHP version 5
 *
 * LICENSE: GNU General Public License, version 2 (GPLv2)
 * Copyright (c) 2001 - 2011 Ampache.org All Rights Reserved
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
 * @category	Template
 * @package	Template
 * @author	Karl Vollmer <vollmer@ampache.org>
 * @copyright	2001 - 2011 Ampache.org
 * @license	http://opensource.org/licenses/gpl-2.0 GPLv2
 * @version	PHP 5.2
 * @link	http://www.ampache.org/
 * @since	File available since Release 1.0
 */

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "DTD/xhtml1-transitional.dtd">
<html lang="en-US">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Ampache -- Debug Page</title>
<link rel="stylesheet" href="templates/install.css" type="text/css" media="screen" />
</head>
<body bgcolor="#f0f0f0">
<div id="header">
<h1><?php echo _('Ampache Security Information'); ?></h1>
<p><?php echo _('This page shows security information and ampache update information.'); ?></p>
</div>
<div>
<button onclick="window.close()"><?php echo _('Close this window'); ?></button>
<table align="center" cellpadding="3" cellspacing="0">
<tr>
	<td><font size="+1"><?php echo _('CHECK'); ?></font></td>
	<td>
		<font size="+1"><?php echo _('STATUS'); ?></font>
	</td>
	<td><font size="+1"><?php echo _('DESCRIPTION'); ?></font></td>
</tr>
<tr>
	<td valign="top"><?php echo _('Ampache Version'); ?></td>
	<td valign="top">[<?php echo check_ampache_version(); ?>]</td>
	<td>
	<?php echo _('Compare that you are running a version of Ampache and currently a version of Ampache.'); ?>
	</td>
</tr>
<tr>
	<td valign="top"><?php echo _('PHP Version'); ?></td>
	<td valign="top">[<?php echo check_php_version(); ?>]</td>
	<td>
	<?php echo _('This test checks for vulnerable PHP whether to use version.'); ?>
	</td>
</tr>
<tr>
	<td valign="top"><?php echo _('PHP recommendation settings'); ?></td>
	<td valign="top">[]</td>
	<td>
	<?php echo _('This test checks whether the recommended security settings.'); ?></td>
</tr>
<tr>
	<td valign="top"><?php echo _('PHP Info'); ?></td>
	<td valign="top">-</td>
	<td>
	<?php echo _('This is the phpinfo() to display information.'); ?>
	</td>
</tr>
<tr>
	<td colspan="3" valign="top">
		<?php phpinfo(INFO_GENERAL|INFO_CONFIGURATION|INFO_MODULES); ?>
	</td>
</tr>
</table>
</div>
<div id="bottom">
<button onclick="window.close()"><?php echo _('Close this window'); ?></button>
<p><strong>Ampache Security Center.</strong><br />
Pour l'Amour de la Musique.</p>
</div>
</body>
</html>
