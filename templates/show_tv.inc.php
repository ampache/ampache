<?php
/*

 Copyright (c) 2001 - 2006 Ampache.org
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

$htmllang = str_replace("_","-",conf('lang'));
$location = get_location();

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?php echo $htmllang; ?>" lang="<?php echo $htmllang; ?>">

<head>
<link rel="shortcut icon" href="<?php echo $web_path; ?>/favicon.ico" />
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo conf('site_charset'); ?>" />
<title><?php echo conf('site_title'); ?> - <?php echo $location['title']; ?></title>
<link rel="stylesheet" href="<?php echo $web_path; ?>/templates/default.css" type="text/css" />
<link rel="stylesheet" href="<?php echo $web_path; ?><?php echo conf('theme_path'); ?>/templates/default.css" type="text/css" />
</head>
<body>
<script src="<?php echo $web_path; ?>/lib/general.js" language="javascript" type="text/javascript"></script>
<script src="<?php echo $web_path; ?>/modules/kajax/ajax.js" language="javascript" type="text/javascript"></script>
<!-- Control DIV -->
<div id="tv_control">
<?php 
/* If they are a admin */
if ($GLOBALS['user']->has_access(100)) { 
	require_once(conf('prefix') . '/templates/show_tv_adminctl.inc.php');	
} 
/* Else normal User */
else { 

} 

?>
</div>
<!-- End Control Div -->
<div id="tv_np">
</div>
<div id="tv_playlist">
</div>
