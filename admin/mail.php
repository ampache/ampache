<?php
/* vim:set tabstop=8 softtabstop=8 shiftwidth=8 noexpandtab: */
/**
 * Admin Mail
 *
 *
 * LICENSE: GNU General Public License, version 2 (GPLv2)
 * Copyright (c) 2001 - 2011 Ampache.org All Rights Reserved
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License v2
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 *
 * @package	Ampache
 * @copyright	2001 - 2011 Ampache.org
 * @license	http://opensource.org/licenses/gpl-2.0 GPLv2
 * @link	http://www.ampache.org/
 */

require_once '../lib/init.php';

if (!Access::check('interface','75')) {
	access_denied();
	exit();
}

show_header();

// Action switch
switch ($_REQUEST['action']) {
	case 'send_mail':
		if (Config::get('demo_mode')) {
			access_denied();
			exit;
		}

		// Multi-byte Character Mail
		if(function_exists('mb_language')) {
			ini_set("mbstring.internal_encoding","UTF-8");
			mb_language("uni");
		}

		$mailer = new AmpacheMail();

		// Set the vars on the object
		$mailer->subject = scrub_in($_REQUEST['subject']);
		$mailer->message = scrub_in($_REQUEST['message']);

		if ($_REQUEST['from'] == 'system') {
			$mailer->set_default_sender();
		}
		else {
			$mailer->sender = $GLOBALS['user']->email;
			$mailer->sender_name = $GLOBALS['user']->fullname;
		}

		if($mailer->send_to_group($_REQUEST['to'])) {
			$title  = _('E-mail Sent');
			$body   = _('Your E-mail was successfully sent.');
		}
		else {
			$title 	= _('E-mail Not Sent');
			$body 	= _('Your E-mail was not sent.');
		}
		$url = Config::get('web_path') . '/admin/mail.php';
		show_confirmation($title,$body,$url);

	break;
	default:
		require_once Config::get('prefix') . '/templates/show_mail_users.inc.php';
	break;
} // end switch

show_footer();


?>
