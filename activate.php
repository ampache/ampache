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

$no_session = true;
require_once( "modules/init.php" );
if(conf('demo_mode'))  {
	access_denied();
}

// Access Control
echo "<html><head>";
show_template('style');
echo "<head><body>";


$username = $_GET['u'];
$validation  = $_GET['act_key'];
$user = new User($username);
$val1 = $GLOBALS['user']->get_user_validation($username,$validation);
if (!$val1){
    $GLOBALS['error']->add_error('no_such_user',_("No user with this name registered"));    
    $GLOBALS['error']->print_error('no_such_user');    
    echo "</body></html>";
    break;
    }
$activate = $GLOBALS['user']->activate_user($username);
show_confirmation('User activated','This User ID is activated and can be used','/login.php');
echo "</body></html>";

?>
