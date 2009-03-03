<?php
/*

 Copyright (c) Ampache.org
 All rights reserved.

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
?>
<?php if (INSTALL != '1') { exit; } ?>
<?php $results = 0; ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?php echo $htmllang; ?>" lang="<?php echo $htmllang; ?>">
<head>
<title>Ampache :: Pour l'Amour de la Musique - Install</title>
<link rel="stylesheet" href="templates/install.css" type="text/css" media="screen" />
<meta http-equiv="Content-Type" content="text/html; Charset=<?php echo $charset; ?>" />
</head>
<body>
<script src="lib/javascript-base.js" language="javascript" type="text/javascript"></script>
<div id="header"> 
<h1><?php echo _('Ampache Installation'); ?></h1>
<p>For the love of Music</p>
</div>
<div id="text-box">
	<div class="notify">
		<strong><?php echo _('Requirements'); ?></strong>
		<p>
		<?php echo _('This Page handles the installation of the Ampache database and the creation of the ampache.cfg.php file. Before you continue please make sure that you have the following pre-requisites'); ?>
		</p>
		<ul>
			<li><?php echo _('A MySQL Server with a username and password that can create/modify databases'); ?></li>
			<li><?php echo _('Your webserver has read access to the /sql/ampache.sql file and the /config/ampache.cfg.php.dist file'); ?></li>
		</ul>
		<p>
<?php echo _("Once you have ensured that you have the above requirements please fill out the information below. You will only be asked for the required config values. If you would like to make changes to your ampache install at a later date simply edit /config/ampache.cfg.php"); ?>
		</p>
	</div>
	<div class="content">
		<h3><?php echo _('System Checks'); ?></h3>
		<h4><?php echo _('Requirements'); ?></h4>
		<p><?php echo _('PHP Version'); ?>:
		<?php
			if(!check_php_ver()) {
				echo " <font color=\"red\">ERROR</font> " . phpversion() . " " . _('Hash Function Exists') . " "
					 . print_boolean(function_exists('hash_algos')) . " " . _('SHA256 Support') . " " . print_boolean(in_array('sha256',$algos)); 
				$results = $results + 1;
			} else {
				echo " <font color=\"green\">&nbsp;&nbsp;&nbsp;OK&nbsp;&nbsp;&nbsp;&nbsp;</font><i>" . phpversion() . "</i>"; 
			}
		?>
		</p>
		<p><?php echo _('Mysql for PHP'); ?>:
		<?php
			if (!check_php_mysql()) {
				echo " <font color=\"red\">ERROR</font> ";
				$results = $results + 1;
			} else {
				if(strcmp('5.0.0',mysql_get_client_info()) > 0) {
					echo " <font color=\"#FF6600\">&nbsp;&nbsp;&nbsp;WARNING&nbsp;&nbsp;&nbsp;&nbsp;</font> " . mysql_get_client_info() . "<strong>We recommend MySQL version more than 5.0.0</strong>";
				} else {
					echo " <font color=\"green\">&nbsp;&nbsp;&nbsp;OK&nbsp;&nbsp;&nbsp;&nbsp;</font>";
					echo "<i>" . mysql_get_client_info() . "</i>"; 
				}
			}
		?>
		</p>
		<p><?php echo _('PHP Session Support'); ?>:
		<?php
			if (!check_php_session()) {
				echo " <font color=\"red\">ERROR</font> ";
				$results = $results + 1;
			} else {
				echo " <font color=\"green\">&nbsp;&nbsp;&nbsp;OK&nbsp;&nbsp;&nbsp;&nbsp;</font> ";
			}
		?>
		</p>
		<p><?php echo _('PHP ICONV Support'); ?>:
		<?php
			if (!check_php_iconv()) {
				echo " <font color=\"red\">ERROR</font> ";
				$results = $results + 1;
			} else {
				echo " <font color=\"green\">&nbsp;&nbsp;&nbsp;OK&nbsp;&nbsp;&nbsp;&nbsp;</font> ";
			}
		?>
		</p>
		<p><?php echo _('PHP PCRE Support'); ?>:
		<?php
			if (!check_php_pcre()) {
				echo " <font color=\"red\">ERROR</font> ";
				$results = $results + 1;
			} else {
				echo " <font color=\"green\">&nbsp;&nbsp;&nbsp;OK&nbsp;&nbsp;&nbsp;&nbsp;</font> ";
			}
		?>
		</p>
		<p><?php echo _('PHP PutENV Support'); ?>:
		<?php
			if (!check_putenv()) {
				echo " <font color=\"red\">ERROR</font> ";
				$results = $results + 1;
			} else {
				echo " <font color=\"green\">&nbsp;&nbsp;&nbsp;OK&nbsp;&nbsp;&nbsp;&nbsp;</font> ";
			}
		?>
		</p>
		<hr />
		<h4><?php echo _('Optional'); ?></h4>
		<p><?php echo _('Gettext Support'); ?>:
		<?php
			if (!function_exists('gettext')) {
				echo " <font color=\"#FF6600\">" . _('WARNING: This server will use gettext emulator.') . "</font> ";
			} else {
				echo " <font color=\"green\">&nbsp;&nbsp;&nbsp;OK&nbsp;&nbsp;&nbsp;&nbsp;</font> ";
			}
		?>
		</p>
		<p><?php echo _('Mbstring Support'); ?>:
		<?php
			if (!function_exists('mb_check_encoding')) {
				echo " <font color=\"#FF6600\">WARNING</font> ";
			} else {
				echo " <font color=\"green\">&nbsp;&nbsp;&nbsp;OK&nbsp;&nbsp;&nbsp;&nbsp;</font> ";
			}
		?>
		</p>
	</div>
	<?php if($results == 0) { ?>
	<div class="content">
		<strong><?php echo _('Choose installation language.'); ?></strong>
		<p>
		<?php Error::display('general'); ?>
		</p>
<form method="post" action="<?php echo WEB_PATH . "?action=init"; ?>" enctype="multipart/form-data" >

<?php
$languages = get_languages();
$var_name = $value . "_lang";
${$var_name} = "selected=\"selected\"";

echo "<select name=\"htmllang\">\n";

foreach ($languages as $lang=>$name) {
	$var_name = $lang . "_lang";

	echo "\t<option value=\"$lang\" " . ${$var_name} . ">$name</option>\n";
} // end foreach
echo "</select>\n";
?>

<input type="submit" value="<?php echo _('Start configuration'); ?>" />

	</form>
 	</div>
	<?php } else { /* if results */ ?>
	<div class="content">
	<?php echo _('Ampache does not <strong>run</strong> correctly by this server.'); ?><br />
	<?php echo _('Please contact your server administrator, and fix them.'); ?>
	</div>
	<?php } /* if results */ ?>
	<div id="bottom">
    	<p><strong>Ampache Installation.</strong><br />
    	For the love of Music.</p>
   </div>
</div>

</body>
</html>
