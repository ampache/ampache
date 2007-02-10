<?php
/*
 Copyright (c) 2001 - 2006 Ampache.org
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
if (conf('local_length') >= conf('remember_length')) { 
	$remember_disabled = 'disabled="disabled"';
}

?>

<div id="container">
	<h1><span><?php echo conf('site_title'); ?></span></h1>
	<div>
		<p id="loginp_0"><?php echo conf('site_title'); ?></p>
		<form name="login" method="post" enctype="multipart/form-data" action="<?php echo  conf('web_path'); ; ?>/login.php" style="Display:inline">
			<p class="loginp_1"><span><?php echo  _('Login'); ; ?>:</span> 
			<input class="text_input" type="text" name="username" value="<?php echo  $_REQUEST['username']; ; ?>" /></p>
			<p class="loginp_1"><span><?php echo  _('Password'); ; ?>:</span> 
			<input class="text_input" type="password" name="password" value="" /></p>
			<p class="loginp_1"><?php echo _('Remember Me'); ?><input class="check_input" type="checkbox" name="rememberme" <?php echo $remember_disabled; ?> /></p>
			<?php echo  conf('login_message'); ; ?>
			<?php $GLOBALS['error']->print_error('general'); ?>
			<p><input class="button" type="submit" value="<?php echo _('Login'); ?>" /></p>
			<input type="hidden" name="referrer" value="<?php echo scrub_out($_SERVER['HTTP_REFERRER']); ?>" />
			<input type="hidden" name="action" value="login" />
		</form>
		<?php if (conf('allow_public_registration')) { ?>
		<span class="text-action">
			<a href="<?php echo conf('web_path'); ?>/register.php"><?php echo _('Register'); ?></a>
		</span>
		<?php } // end if (conf('allow_public_registration')) ?>
	</div>
</div>
