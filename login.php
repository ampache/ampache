<?php
/*

 Copyright (c) 2001 - 2006 Ampache.org
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

/*

 Login our friendly users

*/

$no_session = true;
require_once("modules/init.php");
set_site_preferences();

//
// So we check for a username and password first
//
if ( $_POST['username'] && $_POST['password'] ) {

        if ($_POST['rememberme']) {
        	setcookie('amp_longsess', '1', time()+3600*24*30*120);
        } 

	/* If we are in demo mode let's force auth success */
	if (conf('demo_mode')) {
		$auth['success'] = 1;
		$auth['info']['username'] = "Admin- DEMO";
		$auth['info']['fullname'] = "Administrative User";
		$auth['info']['offset_limit']	= 25;
	}
	else {
		$username = trim($_POST['username']);
		$password = trim($_POST['password']);
		$auth = authenticate($username, $password);
		$user = new User($username); 
		if ($user->disabled === '1') { 
			$auth['success'] = false;
			$auth['error'] = "Error: User Disabled please contact Admin";
		} // if user disabled
	} // if we aren't in demo mode
}

//
// If we succeeded in authenticating, create a session
//
if ( ($auth['success'] == 1)) {

    // $auth->info are the fields specified in the config file
    //   to retrieve for each user
    make_local_session_only($auth);

	//
	// Not sure if it was me or php tripping out,
	//   but naming this 'user' didn't work at all
	//
	$_SESSION['userdata'] = $auth['info'];
	
	/* Make sure they are actually trying to get to this site and don't try to redirect them back into 
	 * an admin section
	**/
	if (strstr($_POST['referrer'], conf('web_path')) AND 
		!strstr($_POST['referrer'],"install.php") AND 
		!strstr($_POST['referrer'],"login.php") AND 
		!strstr($_POST['referrer'],"update.php") AND
		!strstr($_POST['referrer'],"admin")) { 
		
			header("Location: " . $_POST['referrer']);
			exit();
	} // if we've got a referrer
	header("Location: " . conf('web_path') . "/index.php");
	exit();
} // auth success

$htmllang = str_replace("_","-",conf('lang'));
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?php echo $htmllang; ?>" lang="<?php echo $htmllang; ?>">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo conf('site_charset'); ?>" />
<link rel="shortcut icon" href="<?php echo conf('web_path'); ?>/favicon.ico" />
<title> <?php echo conf('site_title'); ?> </title>

<?php show_template('style'); ?>

<script type="text/javascript" language="javascript">
function focus(){ document.login.username.focus(); }
</script>

</head>
<body bgcolor="<?php echo conf('bg_color1'); ?>" onload="focus();">

<?php

require(conf('prefix') . "/templates/show_login_form.inc");

if (@is_readable(conf('prefix') . '/config/motd.php')) {
	include conf('prefix') . '/config/motd.php';
}

?>
</body>
</html>
