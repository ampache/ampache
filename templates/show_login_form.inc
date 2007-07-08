<?php
/*
 Copyright (c) 2001 - 2007 Ampache.org
 All Rights Reserved

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

/* Check and see if their remember me is the same or lower then local
 * if so disable the checkbox 
 */
if (Config::get('session_length') >= Config::get('remember_length')) { 
	$remember_disabled = 'disabled="disabled"';
}
$htmllang = str_replace("_","-",Config::get('lang'));

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?php echo $htmllang; ?>" lang="<?php echo $htmllang; ?>">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo Config::get('site_charset'); ?>" />
<link rel="shortcut icon" href="<?php echo Config::get('web_path'); ?>/favicon.ico" />
<link rel="stylesheet" href="<?php echo Config::get('web_path'); ?>/templates/print.css" type="text/css" media="print" />
<link rel="stylesheet" href="<?php echo Config::get('web_path'); ?>/templates/handheld.css" type="text/css" media="handheld" />
<link rel="stylesheet" href="<?php echo Config::get('web_path'); ?><?php echo Config::get('theme_path'); ?>/templates/default.css" type="text/css" media="screen" />
<title> <?php echo Config::get('site_title'); ?> </title>
<script type="text/javascript" language="javascript">
function focus(){ document.login.username.focus(); }
</script>
</head>

<body bgcolor="#D3D3D3" onload="focus();">
<div id="container">
	<h1><span><?php echo Config::get('site_title'); ?></span></h1>
	<div>
		<p id="loginp_0"><?php echo Config::get('site_title'); ?></p>
		<form name="login" method="post" enctype="multipart/form-data" action="<?php echo Config::get('web_path'); ; ?>/login.php" style="Display:inline">
			<p class="loginp_1" id="usernamebox"><label for="username"><?php echo  _('Login'); ; ?>:</label> 
			<input class="text_input" type="text" id="username" name="username" value="<?php echo  $_REQUEST['username']; ; ?>" /></p>
			<p class="loginp_1" id="passwordbox"><label for="password"><?php echo  _('Password'); ; ?>:</label> 
			<input class="text_input" type="password" id="password" name="password" value="" /></p>
			<p class="loginp_1" id="remembermebox"><label for="rememberme"><?php echo _('Remember Me'); ?>&nbsp;</label><input class="check_input" type="checkbox" id="rememberme" name="rememberme" <?php echo $remember_disabled; ?> /></p>
			<?php echo Config::get('login_message'); ?>
			<?php Error::display('general'); ?>
			<p><input class="button" type="submit" value="<?php echo _('Login'); ?>" /></p>
			<input type="hidden" name="referrer" value="<?php echo scrub_out($_SERVER['HTTP_REFERRER']); ?>" />
			<input type="hidden" name="action" value="login" />
		</form>
		<?php if (Config::get('allow_public_registration')) { ?>
		<span class="text-action">
			<a href="<?php echo conf('web_path'); ?>/register.php"><?php echo _('Register'); ?></a>
		</span>
		<?php } // end if (conf('allow_public_registration')) ?>
	</div>
</div>
