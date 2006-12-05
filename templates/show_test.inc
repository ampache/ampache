<?php
/*

 Copyright (c) 2001 - 2006 Ampache.org
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

$row_classes = array('even','odd');
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "DTD/xhtml1-transitional.dtd">
<html lang="en-US">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
<title>Ampache -- Debug Page</title>
</head>
<body bgcolor="#f0f0f0">
<?php require_once('templates/install.css'); ?>
<div id="header">
<h1><?php echo _('Ampache Debug'); ?></h1>
<p><?php echo _('You\'ve reached this page because a configuration error has occured. Debug Information below'); ?></p>
</div>
<table border="0" cellpadding="3" cellspacing="0">
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
			$status['php_ver'] = 'false';
			echo " <font color=\"red\">ERROR</font> ";
		}
		else {
			$status['php_ver'] = 'true';
			echo " <font color=\"green\">&nbsp;&nbsp;&nbsp;OK&nbsp;&nbsp;&nbsp;&nbsp;</font> ";
		}
	?>
	]
	</td>
	<td>
	<?php echo _('This tests to make sure that you are running a version of PHP that is known to work with Ampache.'); ?>
	</td>
</tr>
<tr>
        <td valign="top"><?php echo _('Mysql for PHP'); ?></td>
        <td valign="top">[
        <?php
                if (!check_php_mysql()) {
                        $status['mysql_php'] = 'false';
                        echo " <font color=\"red\">ERROR</font> ";
                }
                else {
                        $status['mysql_php'] = 'true';
                        echo " <font color=\"green\">&nbsp;&nbsp;&nbsp;OK&nbsp;&nbsp;&nbsp;&nbsp;</font> ";
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
			$status['session_php'] = 'false';
			echo " <font color=\"red\">ERROR</font> ";
		}
		else {
			$status['session_php'] = 'true';
			echo " <font color=\"green\">&nbsp;&nbsp;&nbsp;OK&nbsp;&nbsp;&nbsp;&nbsp;</font> ";
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
			$status['iconv_php'] = 'false';
			echo " <font color=\"red\">ERROR</font> ";
		}
		else {
			$status['iconv_php'] = 'true';
			echo "<font color=\"green\">&nbsp;&nbsp;&nbsp;OK&nbsp;&nbsp;&nbsp;&nbsp;</font> ";
		}
	?>]
	</td>
	<td>
	<?php echo _('This test checks to make sure you have Iconv support installed. Iconv support is not required for Ampache, but it is highly recommended'); ?>
	</td>
</tr>
<tr>
	<td valign="top"><?php echo _('PHP PCRE Support'); ?></td>
	<td valign="top">[
	<?php
		if (!check_php_pcre()) { 
			$status['pcre_php'] = 'false';
			echo " <font color=\"red\">ERROR</font> ";
		}
		else { 
			echo "<font color=\"green\">&nbsp;&nbsp;&nbsp;OK&nbsp;&nbsp;&nbsp;&nbsp;</font> ";
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
			$status['putevn_php'] = false;
			echo " <font color=\"red\">ERROR</font> ";
		}
		else { 
			echo "<font color=\"green\">&nbsp;&nbsp;&nbsp;OK&nbsp;&nbsp;&nbsp;&nbsp;</font> ";
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
		if (!read_config_file($configfile)) {
			$status['read_config'] = 'false';
			echo " <font color=\"red\">ERROR</font> "; 
		}
		else {
			$status['read_config'] = 'true';
			echo " <font color=\"green\">&nbsp;&nbsp;&nbsp;OK&nbsp;&nbsp&nbsp;&nbsp;</font> ";
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
		$results = read_config($configfile, 0, 0);
		if (!check_config_values($results)) { 
			$status['parse_config'] = 'false';
			echo " <font color=\"red\">ERROR</font> ";
		}
		else {
			$status['parse_config'] = 'true';
			echo " <font color=\"green\">&nbsp;&nbsp;&nbsp;OK&nbsp;&nbsp;&nbsp;&nbsp;</font> ";
		}
	?>
	]
	</td>
	<td>
	<?php echo _("This test makes sure that you have set all of the required configuration variables and that we are able to 
	completely parse your config file");
	echo " <font color=\"red\">";
	$error->print_error('log_path');
	echo " </font> ";
	?>
	</td>
</tr>
<tr>
	<td valign="top">
		<?php echo _("Ampache.cfg.php Up to Date?"); ?>
	</td>
	<td valign="top">[
	<?php
		$difference 	= debug_compare_configs($configfile,$configfile . ".dist");
		if (count($difference)) { 
			$status['check_config_uptodate'] = 'false';
			echo " <font color=\"red\">ERROR</font> ";
		}
		else { 
			$status['check_config_uptodate'] = 'true';
			echo " <font color=\"green\">&nbsp;&nbsp;&nbsp;OK&nbsp;&nbsp;&nbsp;&nbsp;</font> ";
		}
	?>
	]
	</td>
	<td>
	<?php if (count($difference)) {
		echo _('Ampache.cfg.php is missing the following:');
		echo "<br /><dl style=\"text-align:left;\">\n";
		foreach ($difference as $key=>$value) { 
			if (is_array($value)) { 
				foreach ($value as $element) { 
					echo "\t<dd>$key = \"$element\"</dd>\n";
				}
			}
			else { 
				echo "\t<dd>$key = \"$value\"</dd>\n";
			}
		}
		echo "</dl><br />\n";
	 } else { ?>
		
	<?php } ?>
	</td>
</tr>
<tr>
	<td valign="top"><?php echo _("DB Connection"); ?></td>
	<td valign="top">[
	<?php
		$db = check_database($results['local_host'], $results['local_username'], $results['local_pass'],$results['local_db']);
		if (!$db) { 
			$status['check_db'] = 'false';
			echo " <font color=\"red\">ERROR</font> ";
		}
		else {
			$status['check_db'] = 'true';
			echo " <font color=\"green\">&nbsp;&nbsp;&nbsp;OK&nbsp;&nbsp;&nbsp;&nbsp;</font> ";
		}		
	?>
	]
	</td>
	<td>
	<?php echo _("This attempts to connect to your database using the values from your ampache.cfg.php"); ?>
	</td>
</tr>
<tr>
	<td valign="top">DB Inserted</td>
	<td valign="top">[
	<?php
		$db_inserted = check_database_inserted($db,$results['local_db']);
		if (!$db_inserted) { 
			$status['check_db_insert'] = 'false';
			echo " <font color=\"red\">ERROR</font> ";
		}
		else {
			$status['check_db_insert'] = 'true';
			echo " <font color=\"green\">&nbsp;&nbsp;&nbsp;OK&nbsp;&nbsp;&nbsp;&nbsp;</font> ";
		}
	?>
	]
	</td>
	<td>
	This checks a few key tables to make sure that you have successfully inserted the ampache database and 
	that the user has access to the database
	</td>
</tr>
<tr>

	<td valign="top">Web Path</td>
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
		$results['web_path'] = $http_type . $_SERVER['SERVER_NAME'] . ":" . $_SERVER['SERVER_PORT'] . $results['web_path'];
		if ($status['parse_config']) { 
			echo "<img src=\"" . $results['web_path'] ."/images/ampache.gif\" width=\"80\" height=\"15\"/>";
		}
		else {
			$status['check_webpath'] = false;
			echo "<font color=\"red\">ERROR</font>";
		}

	?>
	]
	</td>
	<td>
	This test makes sure that your web_path variable is set correctly and that we are able to get to the index page. If you do not see the ampache
	logo here then your web_path is not set correctly. 
	</td>
</tr>
</table>
</div>
<div id="bottom">
<p><b>Ampache Debug.</b><br />
For the love of Music.</p>
</div>
</body>
</html>
