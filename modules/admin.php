<?php
/*

 This contains all of the subroutines for handling any
   administration function such as prefereneces, etc.

*/

/*              
 * show_access_list
 *
 *   Used in the access.inc template for getting information about
 *    remote servers that have access to use this Ampache server's
 *    catalog.
 */                                                                                                
function show_access_list () {
	$dbh = dbh();
        
	$sql = "SELECT * FROM access_list";
	$db_result = mysql_query($sql, $dbh);

	if ( mysql_num_rows($db_result) ) {
		while ($host = mysql_fetch_object($db_result) ) {

			$ip = int2ip($host->ip);

			print("\t<tr><td bgcolor=\"" . conf('secondary_color') . "\">$host->name</td>".
				"<td bgcolor=\"" . conf('secondary_color') . "\">$ip</td>".
				"<td bgcolor=\"" . conf('secondary_color') . "\"><a href=\"" . conf('web_path') . "/access.php?action=delete_host&id=$host->id\">Delete</td></tr>\n");
		}
	}
	else {
		print("\t<tr><td bgcolor=\"" . conf('secondary_color') . "\"colspan=\"3\">You don't have any hosts in your access list.</td></tr>\n");
	}
} // show_access_list()  


/*
 * show_manage_users
 *
 */

function show_manage_users () {

	echo "<table class=\"text-box\">\n<tr><td>\n";
	echo "<span class=\"header2\">" . _("Manage Users") . "</span><br />\n";
	echo "<p>Use the following tools to manage the users that access your site.</p>\n";
	echo "<ul>\n\t<li><a href=\"".conf('web_path') . "/admin/users.php?action=show_add_user\">" . _("Add a new user") . "</a></li\n</dl>\n";
	echo "</td></tr></table>";
	
	show_users();
} // show_manage_users()


/*!
	@function show_user_form
	@discussion shows the user form
*/
function show_user_form ($id, $username, $fullname, $email, $access, $type, $error) {

	require(conf('prefix').'/templates/userform.inc');
    
} // show_user_form()


/*
 * show_change_password
 *
 */

function show_change_password ($username) {

	$user = get_user($username);

        print("<form name=\"change_password\" method=\"post\" action=\"user.php\">");

	print("<p style=\"font-size: 10px; font-weight: bold;\">Changing User Password</p>\n");

        print("<table width=\"90%\">");

        print("<tr>\n");
        print("<td>Enter password:</td>");
        print("<td><input type=password name=new_password_1 size=30 value=\"\"></td>");
        print("</tr>\n");

        print("<tr>\n");
        print("<td>Enter password again:</td>");
        print("<td><input type=password name=new_password_2 size=30 value=\"\"></td>");
        print("</tr>\n");

        print("</table>\n");
        print("<input type=submit name=\"action\" value=\"Change Password\">");
        print("</form>");
} // show_change_password

/*
 * show_update_user_info
 *
 */

function show_update_user_info ($username) {
        
	$user = get_user($username);

	$user->offset_limit = abs($user->offset_limit);

        print("<form name=\"change_password\" method=\"post\" action=\"user.php\">");

	print("<p style=\"font-size: 10px; font-weight: bold;\">Changing User Information for $user->fullname</p>\n");

        print("<table width=\"90%\">");

        print("<tr>\n");
        print("<td>Fullname:</td>");
        print("<td><input type=text name=new_fullname size=30 value=\"$user->fullname\"></td>");
        print("</tr>\n");

        print("<tr>\n");
        print("<td>Email:</td>");
        print("<td><input type=text name=new_email size=30 value=\"$user->email\"></td>");
        print("</tr>\n");

	print("<tr>\n");
	print("<td>View Limit:</td>");
	print("<td><input type=text name=new_offset size=5 value=\"$user->offset_limit\"></td>");
	print("</tr>\n");

        print("</table>\n");
        print("<input type=submit name=\"action\" value=\"Update Profile\">");
        print("</form>");
} // show_update_user_info()

/*
 * show_delete_stats
 *
 */

function show_delete_stats($username) {
        print("<form name=\"clear_statistics\" method=\"post\" action=\"user.php\">");
	print("<br>");

	if ( $username == 'all') {
		print("<p style=\"font-size: 10px; font-weight: bold;\">Delete Your Personal Statistics</p>\n");
	}
	else {
		print("<p style=\"font-size: 10px; font-weight: bold;\">Delete Your Personal Statistics</p>\n");
	}

        print("<input type=submit name=\"action\" value=\"Clear Stats\">");
        print("</form>");
} // show_delete_stats()


/*
 * clear_catalog_stats()
 *
 * Use this to clear the stats for the entire Ampache server.
 *
 */
 
function clear_catalog_stats() {
    $dbh = dbh();
    $sql = "DELETE FROM object_count";
    $result = mysql_query($sql, $dbh);
    $sql = "UPDATE song SET played = 'false'";
    $result = mysql_query($sql, $dbh);
} // clear_catalog_stats


/*
 * check_user_form
 *
 */

function check_user_form ($username, $fullname, $email, $pass1, $pass2, $type) {
	global $dbh;

	$sql = "SELECT * FROM user WHERE username='$username'";
	$db_result = mysql_query($sql, $dbh);

	if ( mysql_num_rows($db_result) ) {
		return "That username is already taken, please choose another.";
	}

	if ( $type == 'new_user' ) {
		if ( empty($username) ) {
			return "Please fill in a username.";
		}
		elseif ( ($pass1 != $pass2) || (empty($pass1) || empty($pass2)) ) {
	                return "Sorry, your passwords do no match.";
        	}
	}
	elseif ( empty($fullname) ) {
		return "Please fill in a full name.";
	}
	elseif ( empty($email) ) {
		return "Please fill in an email address.";
	}
	elseif ( ($pass1 != $pass2) || (empty($pass1) || empty($pass2)) ) {
		if ( $type == 'new_user' ) {
			return "Sorry, your passwords do no match.";
		}
	}

	return false;
} // check_user_form()

/*
 * get_user
 *
 */
function get_user_byid ($id) {


	$sql = "SELECT * FROM user WHERE id='$id'";
	$db_result = mysql_query($sql, dbh());
	return (mysql_fetch_object($db_result));
} // get_user_byid()

function get_user ($username) {


	$sql = "SELECT * FROM user WHERE username='$username'";
	$db_result = mysql_query($sql, dbh());

	return (mysql_fetch_object($db_result));
} // get_user()

/*
 * delete_user
 *
 */

function delete_user ($username) {

	// delete from the user table
        $sql = "DELETE FROM user WHERE username='$username'";
        $db_result = mysql_query($sql, dbh());

	// also delete playlists for user
	$sql = "DELETE FROM playlist WHERE owner='$username'";
	$db_result = mysql_query($sql, dbh());

	delete_user_stats('all');

} // delete_user()

/*
 * update_user
 *
 */

function update_user ($username, $fullname, $email, $access) 
{
    $dbh = libglue_param(libglue_param('dbh_name'));
    if(!$username || !$fullname || !$email || !$access) return 0; 
    $sql = "UPDATE user ".
           "SET fullname='$fullname',".
           "email='$email',".
           "access='$access'".
           "WHERE username='$username'";
    $db_result = mysql_query($sql, $dbh);
    if($db_result) return 1;
    else return 0;
} // update_user()

/*
 * update_user_info
 *
 * this for use by 'user' to update limited amounts of info
 *
 */

function update_user_info ($username, $fullname, $email,$offset) {

    $dbh = libglue_param(libglue_param('dbh_name'));

        $sql = "UPDATE user SET fullname='$fullname', email='$email', offset_limit='$offset' WHERE username='$username'";
        $db_result = mysql_query($sql, $dbh);

	// Update current session (so the views are updated)
	$_SESSION['offset_limit'] = $offset;

    return ($db_result)?1:0;

} // update_user_info()


/*
 * set_user_password
 *
 */

function set_user_password ($username, $password1, $password2) {

    $dbh = libglue_param(libglue_param('dbh_name'));
    if($password1 !== $password2) return 0;

	$sql = "UPDATE user SET password=PASSWORD('$password1') WHERE username='$username' LIMIT 1";
	$db_result = mysql_query($sql, $dbh);
    return ($db_result)?1:0;
} // set_user_password()

?>
