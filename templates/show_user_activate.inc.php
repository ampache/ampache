<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU General Public License, version 2 (GPLv2)
 * Copyright 2001 - 2015 Ampache.org
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
$web_path = AmpConfig::get('web_path');
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?php echo $htmllang; ?>" lang="<?php echo $htmllang; ?>">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo AmpConfig::get('site_charset'); ?>" />
<title><?php echo AmpConfig::get('site_title'); ?> - <?php echo T_('Registration'); ?></title>
<link rel="stylesheet" href="<?php echo AmpConfig::get('web_path'); ?>/templates/install.css" type="text/css" media="screen" />
<link rel="shortcut icon" href="<?php echo AmpConfig::get('web_path'); ?>/favicon.ico" />
</head>
<body>
<div id="header">
<h1><?php echo AmpConfig::get('site_title'); ?></h1>
<?php echo T_('Registration'); ?>...
</div>

<script src="<?php echo $web_path; ?>/modules/jquery/jquery.min.js" language="javascript" type="text/javascript"></script>
<script src="<?php echo $web_path; ?>/lib/javascript/base.js" language="javascript" type="text/javascript"></script>
<script src="<?php echo $web_path; ?>/lib/javascript/ajax.js" language="javascript" type="text/javascript"></script>

<div id="maincontainer">
<?php
    if ($validation == User::get_validation($username) AND strlen($validation)) {
    User::activate_user($username);
?>
<h3><?php echo T_('User Activated'); ?></h3>
<p>
    <?php
    /* HINT: Start A tag, End A tag */
     printf(T_('This User ID is activated and can be used %sLogin%s'), '<a href="' . AmpConfig::get('web_path'). '/login.php">', '</a>'); ?>
</p>
<?php } else { ?>
<h3><?php echo T_('Validation Failed'); ?></h3>
<p><?php echo T_("The validation key used isn't correct"); ?></p>
<?php } ?>
</div><!--end <div>id="maincontainer-->
<div id="bottom">
<p><strong>Ampache</strong><br />
For the love of Music.</p>
</div>
</body>
</html>
