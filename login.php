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
require_once('lib/init.php');

/* We have to create a cookie here because IIS
 * can't handle Cookie + Redirect 
 */
vauth_session_cookie();
init_preferences();

/**
 * If Access Control is turned on then we don't
 * even want them to be able to get to the login 
 * page if they aren't in the ACL
 */
if (conf('access_control')) { 
        $access = new Access(0);
        if (!$access->check('interface',$_SERVER['REMOTE_ADDR'],'','5')) {
                debug_event('access_denied','Access Denied:' . $_SERVER['REMOTE_ADDR'] . ' is not in the Interface Access list','3');
                access_denied();
        }
} // access_control is enabled


/* Check for posted username and password */
if ($_POST['username'] && $_POST['password']) {

        if ($_POST['rememberme']) {
		$extended = vauth_conf('remember_length');
		vauth_conf(array('cookie_life'=>$extended),1);
		$cookie_name = vauth_conf('session_name') . "_remember";
		$cookie_life = time() + $extended;
		setcookie($cookie_name, '1', $cookie_life,'/',vauth_conf('cookie_domain'));
        } 

	/* If we are in demo mode let's force auth success */
	if (conf('demo_mode')) {
		$auth['success'] = 1;
		$auth['info']['username'] = "Admin- DEMO";
		$auth['info']['fullname'] = "Administrative User";
		$auth['info']['offset_limit']	= 25;
	}
	else {
		$username = scrub_in($_POST['username']);
		$password = scrub_in($_POST['password']);
		$auth = authenticate($username, $password);
                $user = new User($username);
	
		if ($user->disabled == '1') { 	
                                $auth['success'] = false;
                                $auth['error'] = _('User Disabled please contact Admin');
                } // if user disabled
                
		elseif (!$user->username) { 
			/* This is run if we want to auto_create users who don't exist (usefull for non mysql auth) */                
			if (conf('auto_create')) {
				if (!$access = conf('auto_user')) { $access = '5'; } 
				
                        	$name = $auth['name'];
                        	$email = $auth['email'];
                        
				/* Attempt to create the user */	
				if (!$user->create($username, $name, $email,md5(time()), $access)) {
                                	$auth['success'] = false;
                                	$auth['error'] = _('Unable to create new account');
                            	}
				else { 
                        		$user = new User($username);
				}
                        } // End if auto_create

                        else {
                            $auth['success'] = false;
                            $auth['error'] = _('No local account found');
                        }
                } // else user isn't disabled

	} // if we aren't in demo mode

} // if they passed a username/password

/* If the authentication was a success */
if ($auth['success']) {
    // $auth->info are the fields specified in the config file
    //   to retrieve for each user
    vauth_session_create($auth);
	
	//
	// Not sure if it was me or php tripping out,
	//   but naming this 'user' didn't work at all
	//
	$_SESSION['userdata'] = $auth;
	
	// 
	// Record the IP of this person!
	// 
	if (conf('track_user_ip')) { 
		$user = new User($_POST['username']);
		$user->insert_ip_history();	
		unset($user);
	}

	/* Make sure they are actually trying to get to this site and don't try to redirect them back into 
	 * an admin section
	**/
	if (strstr($_POST['referrer'], conf('web_path')) AND 
		!strstr($_POST['referrer'],"install.php") AND 
		!strstr($_POST['referrer'],"login.php") AND 
		!strstr($_POST['referrer'],"update.php") AND
		!strstr($_POST['referrer'],"activate.php") AND
		!strstr($_POST['referrer'],"admin")) { 
		
			header("Location: " . $_POST['referrer']);
			exit();
	} // if we've got a referrer
	header("Location: " . conf('web_path') . "/index.php");
	exit();
} // auth success
/* If auth failed then setup the error */
else { 
	$GLOBALS['error']->add_error('general',$auth['error']);
}

$htmllang = str_replace("_","-",conf('lang'));
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?php echo $htmllang; ?>" lang="<?php echo $htmllang; ?>">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo conf('site_charset'); ?>" />
<link rel="shortcut icon" href="<?php echo conf('web_path'); ?>/favicon.ico" />
<link rel="stylesheet" href="templates/default.css" type="text/css" />
<title> <?php echo conf('site_title'); ?> </title>
<script type="text/javascript" language="javascript">
function focus(){ document.login.username.focus(); }
</script>
</head>

<body bgcolor="#D3D3D3" onload="focus();">

<?php
require(conf('prefix') . "/templates/show_login_form.inc");

if (@is_readable(conf('prefix') . '/config/motd.php')) {
	echo "<div align=\"center\">\n";
	show_box_top(_('Message of the Day')); 
        include conf('prefix') . '/config/motd.php';
	show_box_bottom();
	echo "</div>\n";
}

?>
</body>
</html>
