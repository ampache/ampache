<?php
/*
 Copyright (c) 2001 - 2005 Ampache.org
 All Rights Reserved

 This program is free software; you can redistribute it and/or
 modify it under the terms of the GNU General Public License
 as published by the Free Software Foundation; either version 2
 of the License, or (at your option) any later version.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with this program; if not, write to the Free Software
 Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.

*/

/*!
	@header Login Template
Login Template

*/

$referrer = $_SERVER['HTTP_REFERER'];

$subject = "/ampache.com/";

if (preg_match($subject,$_SERVER['HTTP_HOST'])) { 
	$show_copyright = 1;
}

?>
<br /><br />
<p align="center">
	<a href="http://www.ampache.org"><img src="<?php echo  conf('web_path'); ?>
		<?php echo conf('theme_path'); ?>/images/ampache.gif" title="<?php echo conf('site_title'); ?>" border="0" />
	</a>
</p>
<form name="login" method="post" enctype="multipart/form-data" action="<?php echo  conf('web_path'); ; ?>/login.php" style="Display:inline">
<table bgcolor="<?php echo conf('base_color2'); ?>" border="0" align="center">
	<tr>
		<td align="center" colspan="2"><?php echo  conf('login_message'); ; ?>&nbsp;</td>
	</tr>
	<tr>
		<td><?php echo  _("Login"); ; ?>:</td>
		<td><input type="text" name="username" value="<?php echo  $_REQUEST['username']; ; ?>" /></td>
	</tr>
	<tr>
		<td><?php echo  _("Password"); ; ?>:</td>
		<td><input type="password" name="password" value="" /></td>
	</tr>
        <tr>
		<td>&nbsp;</td>
                <td><input type="checkbox" name="rememberme" />
                <?php echo _("Remember Me"); ?></td>
        </tr>
	<tr>
		<td colspan="2" align="center">
			<input class="button" type="submit" value="<?php echo _("Login"); ?>" />
			<input type="hidden" name="referrer" value="<?php echo $referrer; ?>" />
			<input type="hidden" name="action" value="login" />
		</td>
	</tr>
</table>
</form>
<?php if ($show_copyright == 1) { ?>
<p align="center">
	<font color="red" size="+2">
		<a href="http://www.ampache.org/donations.php">Ampache.org</a><br />
		Ampache.com Domain Squatter<br />
		All Rights Reserved, Copyright &copy; 2005<br />
	</font>
</p>
<? } ?>
<?php  if (isset($auth['error'])) { ?>
<p align="center">
	<font color="red"><?php echo trim($auth['error']); ?></font>
</p>
<?php  } ?>
