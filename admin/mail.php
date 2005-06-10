<?php
/*

 Copyright (c) 2001 - 2005 Ampache.org
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

/*!
	@header Mail Admin Page
	Means to mail your users or give updates about the server

*/

require('../modules/init.php');

if (!$user->has_access(100)) {
	access_denied();
}


$action = scrub_in($_POST['action']);
$to = scrub_in($_REQUEST['to']);
$subject = stripslashes(scrub_in($_POST['subject']));
$message = stripslashes(scrub_in($_POST['message']));

if ( $action == 'send_mail' && !conf('demo_mode')) {
  $user = new User(0,$_SESSION['userdata']['id']);
  // do the mail mojo here
  if ( $to == 'all' ) {
    $sql = "SELECT * FROM user WHERE email IS NOT NULL";
  }
  elseif ( $to == 'users' ) {
    $sql = "SELECT * FROM user WHERE access='users' AND email IS NOT NULL";
  }
  elseif ( $to == 'admins' ) {
    $sql = "SELECT * FROM user WHERE access='admin' AND email IS NOT NULL";
  }
  
  $db_result = mysql_query($sql, dbh());
  
	$recipient = '';

	while ( $u = mysql_fetch_object($db_result) ) {
		$recipient .= "$u->fullname <$u->email>, ";
	}

	// Remove the last , from the recipient
	$recipient = rtrim($recipient,",");

  $from    = $user->fullname."<".$user->email.">";

  // woohoo!!
  mail ($from, $subject, $message,
	"From: $from\r\n".
	"Bcc: $recipient\r\n");

  // tell them that it was sent
  $complete_text = "Your message was successfully sent.";
}

if ( empty($to) ) {
	$to = 'all';
}

if ( empty($subject) ) {
	$site_title = conf('site_title');
	$subject = "[$site_title] ";
}

show_template('header');

show_menu_items('Admin');
show_admin_menu('Mail Users');
show_clear();
?>

<form name="mail" method="post" action="<?php echo conf('web_path'); ?>/admin/mail.php" enctype="multipart/form-data">

<p><font color="<?php echo $error_color; ?>"><?php echo $complete_text; ?></font></p>

<table>
  <tr>
    <td><?php echo _("Mail to"); ?>:</td>
    <td> 
	<select name="to">
		<option value="all" <?php if ($to == 'all') { echo "SELECTED"; } ?>>All</option>
		<option value="users" <?php if ($to == 'user') { echo "SELECTED"; } ?>>Users</option>
		<option value="admins" <?php if ($to == 'admin') { echo "SELECTED"; } ?>>Admins</option>
	</select>
    </td>
  </tr>

  <tr>
    <td><?php echo _("Subject"); ?>:</td>
    <td> 
	<input name="subject" value="<?php echo $_POST['subject']; ?>" size="50"></input>
    </td>
  </tr>

  <tr>
    <td valign="top"><?php echo _("Message"); ?>:</td>
    <td>
        <textarea class="input" name="message" rows="20" cols="70"><?php echo $message; ?></textarea>
    </td>
  </tr>

  <tr>
    <td>&nbsp;</td>
    <td>
    	<input type="hidden" name="action" value="send_mail" />
	<input type="submit" value="<?php echo _("Send Mail"); ?>" />
    </td>
  </tr>
</table>

</form>
<br /><br />
<?php  
show_page_footer ('Admin', 'Mail Users',$user->prefs['display_menu']);
?>
