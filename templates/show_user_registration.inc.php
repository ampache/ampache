<?php
/* vim:set tabstop=8 softtabstop=8 shiftwidth=8 noexpandtab: */
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
<link rel="stylesheet" href="<?php echo Config::get('web_path'); ?><?php echo Config::get('theme_path'); ?>/templates/default.css" type="text/css" media="screen" />
<link rel="shortcut icon" href="<?php echo Config::get('web_path'); ?>/favicon.ico" />
</head>
<body id="registerPage">
<script src="<?php echo $web_path; ?>/modules/prototype/prototype.js" language="javascript" type="text/javascript"></script>
<script src="<?php echo $web_path; ?>/lib/javascript/base.js" language="javascript" type="text/javascript"></script>
<script src="<?php echo $web_path; ?>/lib/javascript/ajax.js" language="javascript" type="text/javascript"></script>

<div id="maincontainer">
<div id="header">
<h1><?php echo scrub_out(Config::get('site_title')); ?></h1>
<span><?php echo _('Registration'); ?>...</span>
</div>
<?php

$action = scrub_in($_REQUEST['action']);
$fullname = scrub_in($_REQUEST['fullname']);
$username = scrub_in($_REQUEST['username']);
$email = scrub_in($_REQUEST['email']);
?>
<div id="registerbox">
<form name="update_user" method="post" action="<?php echo $web_path; ?>/register.php" enctype="multipart/form-data">
<?php
/*  If we should show the user agreement */
if (Config::get('user_agreement')) { ?>
<h3><?php echo _('User Agreement'); ?></h3>
<div class="registrationAgreement">
    <div class="agreementContent">
		<?php Registration::show_agreement(); ?>
    </div>
    
    <div class="agreementCheckbox">
		<input type='checkbox' name='accept_agreement' /> <?php echo _('I Accept'); ?>
		<?php Error::display('user_agreement'); ?>
	</div>
</div>
<?php } // end if(conf('user_agreement')) ?>
<h3><?php echo _('User Information'); ?></h3>
<div class="registerfield require">
    <label for="username"><?php echo _('Username'); ?>: <span class="asterix">*</span></label>
    <input type='text' name='username' id='username' value='<?php echo scrub_out($username); ?>' />
	<?php Error::display('username'); ?>
	<?php Error::display('duplicate_user'); ?>
</div>
<div class="registerfield require">
	<label for="fullname"><?php echo _('Full Name'); ?>: <span class="asterix">*</span></label>
    <input type='text' name='fullname' id='fullname' value='<?php echo scrub_out($fullname); ?>' />
	<?php Error::display('fullname'); ?>
</div>

<div class="registerfield require">
    <label for="email"><?php echo _('E-mail'); ?>: <span class="asterix">*</span></label>
	<input type='text' name='email' id='email' value='<?php echo scrub_out($email); ?>' />
	<?php Error::display('email'); ?>
</div>

<div class="registerfield require">
	<label for="password"><?php echo _('Password'); ?>: <span class="asterix">*</span></label>
	<input type='password' name='password_1' id='password_1' />
	<?php Error::display('password'); ?>
</div>

<div class="registerfield require">
	<label for="confirm_passord"><?php echo _('Confirm Password'); ?>: <span class="asterix">*</span></label>
	<input type='password' name='password_2' id='password_2' />
</div>

<div class="registerInformation">
    <span><?php echo _('* Required fields'); ?></span>
</div>

<?php if (Config::get('captcha_public_reg')) { ?>
			<?php  echo captcha::form("&rarr;&nbsp;"); ?>
			<?php Error::display('captcha'); ?>
<?php } ?>

<div class="registerButtons">
	<input type="hidden" name="action" value="add_user" />
	<input type='submit' name='submit_registration' id='submit_registration' value='<?php echo _('Register User'); ?>' />
</div>
</form>
</div><!-- end <div id="registerbox-->
</div><!--end <div>id="maincontainer-->
<div id="footer">
    <a href="http://www.ampache.org/index.php">Ampache v.<?php echo Config::get('version'); ?></a><br />
    Copyright (c) 2001 - 2010 Ampache.org
    <?php echo _('Queries:'); ?><?php echo Dba::$stats['query']; ?> <?php echo _('Cache Hits:'); ?><?php echo database_object::$cache_hit; ?>
</div>
</body>
</html>
