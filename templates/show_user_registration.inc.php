<?php
/*

 Copyright (c) 2001 - 2007 Ampache.org
 All rights reserved.

 This program is free software; you can redistribute it and/or
 modify it under the terms of the GNU General Public License
 as published by the Free Software Foundation; version 2
 of the License.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with this program; if not, write to the Free Software
 Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.

*/
$htmllang = str_replace("_","-",Config::get('lang'));
$web_path = Config::get('web_path'); 
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?php echo $htmllang; ?>" lang="<?php echo $htmllang; ?>">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo Config::get('site_charset'); ?>" />
<title><?php echo Config::get('site_title'); ?> - <?php echo _('Registration'); ?></title>
<link rel="stylesheet" href="<?php echo Config::get('web_path'); ?>/templates/install.css" type="text/css" media="screen" />
<link rel="shortcut icon" href="<?php echo Config::get('web_path'); ?>/favicon.ico" />
</head>
<body>
<div id="header">
<h1><?php echo scrub_out(Config::get('site_title')); ?></h1>
<?php echo _('Registration'); ?>...
</div>
<script src="<?php echo $web_path; ?>/lib/javascript-base.js" language="javascript" type="text/javascript"></script>
<script src="<?php echo $web_path; ?>/modules/kajax/ajax.js" language="javascript" type="text/javascript"></script>
<script src="<?php echo $web_path; ?>/modules/prototype/prototype.js" language="javascript" type="text/javascript"></script>

<div id="maincontainer">
<?php

$action = scrub_in($_REQUEST['action']);
$fullname = scrub_in($_REQUEST['fullname']);
$username = scrub_in($_REQUEST['username']);
$email = scrub_in($_REQUEST['email']);
?>
<form name="update_user" method="post" action="<?php echo $web_path; ?>/register.php" enctype="multipart/form-data">
<?php
/*  If we should show the user agreement */
if (Config::get('user_agreement')) { ?>
<h3><?php echo _('User Agreement'); ?></h3>
<table cellpadding="2" cellspacing="0">
<tr>
	<td>
		<?php Registration::show_agreement(); ?>
	</td>
</tr>
<tr>
	<td>
		<input type='checkbox' name='accept_agreement' /> <?php echo _('I Accept'); ?>
		<?php Error::display('user_agreement'); ?>
	</td>
</tr>
</table>
<?php } // end if(conf('user_agreement')) ?>
<h3><?php echo _('User Information'); ?></h3>
<table cellpadding="0" cellspacing="0">
<tr>
	<td align='right'>
		<?php echo _('Username'); ?>:
	</td>
	<td>
		<font color='red'>*</font> <input type='text' name='username' id='username' value='<?php echo scrub_out($username); ?>' />
		<?php Error::display('username'); ?>
		<?php Error::display('duplicate_user'); ?>
	</td>
</tr>
<tr>
	<td align='right'>
		<?php echo _('Full Name'); ?>:
	</td>
	<td>
		<font color='red'>*</font> <input type='text' name='fullname' id='fullname' value='<?php echo scrub_out($fullname); ?>' />
		<?php Error::display('fullname'); ?>
	</td>
</tr>
<tr>
	<td align='right'>
		<?php echo _('E-mail'); ?>:
	</td>
	<td>
		<font color='red'>*</font> <input type='text' name='email' id='email' value='<?php echo scrub_out($email); ?>' />
		<?php Error::display('email'); ?>
	</td>
</tr>
<tr>
	<td align='right'>
		<?php echo _('Password'); ?>:
	</td>
	<td>
		<font color='red'>*</font> <input type='password' name='password_1' id='password_1' />
		<?php Error::display('password'); ?>
	</td>
</tr>
<tr>
	<td align='right'>
		<?php echo _('Confirm Password'); ?>:
	</td>
	<td>
		<font color='red'>*</font> <input type='password' name='password_2' id='password_2' />
	</td>
</tr>
<tr>
	<td align='center' height='20'>
		<span style="color:red;">* Required fields</span>
	</td>
	<td>&nbsp;</td>
</tr>
</table>
<?php if (Config::get('captcha_public_reg')) { ?>
			<?php  echo captcha::form("&rarr;&nbsp;"); ?>
			<?php Error::display('captcha'); ?>
<?php } ?>
<table>
<tr>
	<td align='center' height='50'>
		<input type="hidden" name="action" value="add_user" />
		<input type='submit' name='submit_registration' id='submit_registration' value='<?php echo _('Register User'); ?>' />
	</td>
</tr>
</table>
</form>
</div><!--end <div>id="maincontainer-->
<div id="bottom">
<p><strong>Ampache</strong><br />
Pour l'Amour de la Musique.</p>
</div>
</body>
</html>
