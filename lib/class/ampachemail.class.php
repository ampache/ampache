<?php
/* vim:set tabstop=8 softtabstop=8 shiftwidth=8 noexpandtab: */
/*

 Copyright (c) 2001 - 2007 Ampache.org
 All rights reserved.

 This program is free software; you can redistribute it and/or
 modify it under the terms of the GNU General Public License v2
 as published by the Free Software Foundation.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with this program; if not, write to the Free Software
 Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.

*/

class AmpacheMail {

	// The message, recipient and from
	public static $message;
	public static $recipient;
	public static $fromname;
	public static $subject;
	public static $to;
	public static $fullname;
	public static $sender;

	/**
	 * Constructor
	 * This isn't used
	 */
	private function __construct($name) {

		// Rien a faire

	} // Constructor

	/**
 	 * get_users
	 * This returns an array of userid's for people who have e-mail addresses
	 * based on the passed filter
	 */
	public static function get_users($filter) {

		switch ($filter) {
			default:
			case 'all':
				$sql = "SELECT * FROM `user` WHERE `email` IS NOT NULL";
			break;
			case 'users':
				$sql = "SELECT * FROM `user` WHERE `access`='25' AND `email` IS NOT NULL";
			break;
			case 'admins':
				$sql = "SELECT * FROM `user` WHERE `access`='100' AND `email` IS NOT NULL";
			break ;
			case 'inactive':
				$inactive = time() - (30*86400);
				$sql = "SELECT * FROM `user` WHERE `last_seen` <= '$inactive' AND `email` IS NOT NULL";
			break;
		} // end filter switch

		$db_results = Dba::read($sql);

		$results = array();

		while ($row = Dba::fetch_assoc($db_results)) {
			$results[] = array('id'=>$row['id'],'fullname'=>$row['fullname'],'email'=>$row['email']);
		}

		return $results;

	} // get_users

	/**
	 * add_statistics
	 * This should be run if we want to add some statistics to this e-mail, appends to self::$message
	 */
	public static function add_statistics($methods) {



	} // add_statistics

	/**
	 * send
	 * This actually sends the mail, how amazing
	 */
	public static function send() {

		$mailtype = Config::get('mail_type');
		$mail = new PHPMailer();

		$mail->AddAddress(self::$to, self::$fullname);
		$mail->CharSet	= Config::get('site_charset');
		$mail->Encoding	= "base64";
		$mail->From		= self::$sender;
		$mail->Sender	= self::$sender;
		$mail->FromName	= self::$fromname;
		$mail->Subject	= self::$subject;
		$mail->Body		= self::$message;
		$mailhost		= Config::get('mail_host');
		$mailport		= Config::get('mail_port');
		$mailauth		= Config::get('mail_auth');
		switch($mailtype) {
			case 'smtp':
				$mail->IsSMTP();
				isset($mailhost) ? $mail->Host = $mailhost : $mail->Host = "localhost";
				isset($mailport) ? $mail->Port = $mailport : $mail->Port = 25;
				if($mailauth == true) {
					$mail->SMTPAuth(true);
					$mailuser	= Config::get('mail_auth_user');
					$mailpass	= Config::get('mail_auth_pass');
					isset($mailuser) ? $mail->Username = $mailuser : $mail->Username = "";
					isset($mailpass) ? $mail->Password = $mailpass : $mail->Password = "";
				}
			break;
			case 'sendmail':
				$mail->IsSendmail();
				$sendmail	= Config::get('sendmail_path');
				isset($sendmail) ? $mail->Sendmail = $sendmail : $mail->Sendmail = "/usr/sbin/sendmail";
			break;
			case 'php':
			default:
				$mail->IsMail();
			break;
		}

		$retval = $mail->send();
		if( $retval == true ) {
			return true;
		} else {
			return false;
		}
	} // send

} // AmpacheMail class
?>
