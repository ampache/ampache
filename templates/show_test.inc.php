<?php
/*

 Copyright (c) Ampache.org
 All Rights Reserved

 this program is free software; you can redistribute it and/or
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
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "DTD/xhtml1-transitional.dtd">
<html lang="en-US">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Ampache -- Debug Page</title>
<link rel="stylesheet" href="templates/install.css" type="text/css" media="screen" />
</head>
<body bgcolor="#f0f0f0">
<div id="header">
<h1><?php echo _('Ampache Debug'); ?></h1>
<p><?php echo _('You\'ve reached this page because a configuration error has occured. Debug Information below'); ?></p>
</div>
<div>
<table align="center" cellpadding="3" cellspacing="0">
<tr>
	<td><font size="+1"><?php echo _('CHECK'); ?></font></td>
	<td>
		<font size="+1"><?php echo _('STATUS'); ?></font>	
	</td>
	<td><font size="+1"><?php echo _('DESCRIPTION'); ?></font></td>
</tr>
<tr>
	<td valign="top"><?php echo _('PHP Version'); ?></td>
	<td valign="top">[
	<?php
		if (!check_php_ver()) { 
			echo debug_result('',false);
			if (function_exists('hash_algos')) { $algos = hash_algos(); } 
			$string = "<strong>" .  phpversion() . " " . _('Hash Function Exists') . " " . print_boolean(function_exists('hash_algos')) . " " . _('SHA256 Support') . " " . print_boolean(in_array('sha256',$algos)) . "</strong>"; 
		}
		else {
			echo debug_result('',true); 
		}
	?>
	]
	</td>
	<td>
	<?php echo _('This tests to make sure that you are running a version of PHP that is known to work with Ampache.'); ?>
	<?php echo $string; ?>
	</td>
</tr>
<tr>
        <td valign="top"><?php echo _('Mysql for PHP'); ?></td>
        <td valign="top">[
        <?php
                if (!check_php_mysql()) {
			echo debug_result('',false); 
                }
                else {
			echo debug_result('',true); 
                }
        ?>
        ]
        </td>
        <td>
	<?php echo _('This test checks to see if you have the mysql extensions loaded for PHP. These are required for Ampache to work.'); ?>
        </td>
</tr>
<tr>
	<td valign="top"><?php echo _('PHP Session Support'); ?></td>
	<td valign="top">[
	<?php
		if (!check_php_session()) { 
			echo debug_result('',false); 
		}
		else {
			echo debug_result('',true); 
		}
	?>
	]
	</td>
	<td>
	<?php echo _('This test checks to make sure that you have PHP session support enabled. Sessions are required for Ampache to work.'); ?>
	</td>
</tr>
<tr>
	<td valign="top"><?php echo _('PHP ICONV Support'); ?></td>
	<td valign="top">[
	<?php
		if (!check_php_iconv()) { 
			echo debug_result('',false); 
		}
		else {
			echo debug_result('',true); 
		}
	?>]
	</td>
	<td>
	<?php echo _('This test checks to make sure you have Iconv support installed. Iconv support is required for Ampache'); ?>
	</td>
</tr>
<tr>
	<td valign="top"><?php echo _('PHP PCRE Support'); ?></td>
	<td valign="top">[
	<?php
		if (!check_php_pcre()) { 
			echo debug_result('',false); 
		}
		else { 
			echo debug_result('',true); 
		}
	?>]
	</td>
	<td>
	<?php echo _('This test makes sure you have PCRE support compiled into your version of PHP, this is required for Ampache.'); ?>
	</td>
</tr>
<tr>
	<td valign="top"><?php echo _('PHP PutENV Support'); ?></td>
	<td valign="top">[
	<?php
		if (!check_putenv()) { 
			echo debug_result('',false); 
		}
		else { 
			echo debug_result('',true); 
		} 
	?>]
	</td>
	<td>
	<?php echo _('This test makes sure that PHP isn\'t running in SafeMode and that we are able to modify the memory limits. While not required, without these abilities some features of ampache may not work correctly'); ?>
	</td>
</tr> 
<tr>
	<td valign="top"><?php echo _('Ampache.cfg.php Exists'); ?></td>
	<td valign="top">[ 
	<?php
		if (!is_readable($configfile)) { 
			echo debug_result('',false); 
		}
		else {
			echo debug_result('',true); 
		}
	?>
	]
	</td>
	<td width="350px">
	<?php echo _('This attempts to read /config/ampache.cfg.php If this fails either the ampache.cfg.php is not in the correct locations or
	it is not currently readable by your webserver.'); ?>
	</td>
</tr>
<tr>
	<td valign="top">
		<?php echo _('Ampache.cfg.php Configured?'); ?>
	</td>
	<td valign="top">[ 
	<?php
		$results = @parse_ini_file($configfile);
		Config::set_by_array($results);
		if (!check_config_values($results)) { 
			echo debug_result('',false); 
		}
		else {
			echo debug_result('',true); 
		}
	?>
	]
	</td>
	<td>
	<?php echo _("This test makes sure that you have set all of the required configuration variables and that we are able to completely parse your config file"); ?>
	</td>
</tr>
<tr>
	<td valign="top"><?php echo _("DB Connection"); ?></td>
	<td valign="top">[
	<?php
		$db = check_database($results['database_hostname'], $results['database_username'], $results['database_password'],$results['database_name']);
		if (!$db) { 
			echo debug_result('',false); 
		}
		else {
			echo debug_result('',true); 
		}		
	?>
	]
	</td>
	<td>
	<?php echo _("This attempts to connect to your database using the values from your ampache.cfg.php"); ?>
	</td>
</tr>
<tr>
	<td valign="top"><?php echo _('DB Inserted'); ?></td>
	<td valign="top">[
	<?php
		$db_inserted = check_database_inserted($db,$results['local_db']);
		if (!$db_inserted) { 
			echo debug_result('',false); 
		}
		else {
			echo debug_result('',true); 
		}
	?>
	]
	</td>
	<td>
	<?php echo _('This checks a few key tables to make sure that you have successfully inserted the ampache database and that the user has access to the database'); ?>
	</td>
</tr>
<tr>

	<td valign="top"><?php echo _('Web Path'); ?></td>
	<td valign="top">[
	<?php
		/*
		 Check to see if this is Http or https
		 */
		if ($_SERVER['HTTPS'] == 'on') { 
         		$http_type = "https://";
	 	}
	 	else { 
	        	$http_type = "http://";
		}
		$results['web_path'] = $http_type . $_SERVER['SERVER_NAME'] . ":" . $_SERVER['SERVER_PORT'] . Config::get('web_path');
		if (check_config_values($results)) { 
			echo "&nbsp;&nbsp;&nbsp;<img src=\"" . $results['web_path'] ."/images/icon_enable.png\" />&nbsp;&nbsp;&nbsp;";
		}
		else {
			echo debug_result('',false); 
		}

	?>
	]
	</td>
	<td>
	<?php echo _('This test makes sure that your web_path variable is set correctly and that we are able to get to the index page. If you do not see a check mark here then your web_path is not set correctly.'); ?>
	</td>
</tr>
</table>
</div>
<div id="bottom">
<p><strong>Ampache Debug.</strong><br />
Pour l'Amour de la Musique.</p>
</div>
</body>
</html>
