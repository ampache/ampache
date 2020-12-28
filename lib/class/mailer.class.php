<?php
declare(strict_types=0);
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright 2001 - 2020 Ampache.org
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 */

use PHPMailer\PHPMailer\PHPMailer;

/**
 * Mailer Class
 *
 * This class handles the Mail
 *
 */
class Mailer
{
    // The message, recipient and from
    public $message;
    public $subject;
    public $recipient;
    public $recipient_name;
    public $sender;
    public $sender_name;

    /**
     * Constructor
     *
     * This does nothing. Much like goggles.
     */
    public function __construct()
    {
        // Eh bien.
    } // Constructor

    /**
     * is_mail_enabled
     *
     * Check that the mail feature is enabled
     * @return boolean
     */
    public static function is_mail_enabled()
    {
        if (AmpConfig::get('mail_enable') && !AmpConfig::get('demo_mode')) {
            return true;
        }

        // by default you actually want people to set up mail first
        return false;
    }

    /**
     * validate_address
     *
     * Checks whether what we have looks like a valid address.
     * @param string $address
     * @return boolean
     */
    public static function validate_address($address)
    {
        return PHPMailer::ValidateAddress($address);
    }

    /**
     * set_default_sender
     *
     * Does the config magic to figure out the "system" email sender and
     * sets it as the sender.
     */
    public function set_default_sender()
    {
        $user = AmpConfig::get('mail_user');
        if (!$user) {
            $user = 'info';
        }

        $domain = AmpConfig::get('mail_domain');
        if (!$domain) {
            $domain = 'example.com';
        }

        $fromname = AmpConfig::get('mail_name');
        if (!$fromname) {
            $fromname = 'Ampache';
        }

        $this->sender      = $user . '@' . $domain;
        $this->sender_name = $fromname;
    } // set_default_sender

    /**
     * get_users
     * This returns an array of userids for people who have e-mail
     * addresses based on the passed filter
     * @param $filter
     * @return array
     */
    public static function get_users($filter)
    {
        switch ($filter) {
            case 'users':
                $sql = "SELECT * FROM `user` WHERE `access`='25' AND `email` IS NOT NULL";
                break;
            case 'admins':
                $sql = "SELECT * FROM `user` WHERE `access`='100' AND `email` IS NOT NULL";
                break;
            case 'inactive':
                $inactive = time() - (30 * 86400);
                $sql      = 'SELECT * FROM `user` WHERE `last_seen` <= ? AND `email` IS NOT NULL';
                break;
            case 'all':
            default:
                $sql = "SELECT * FROM `user` WHERE `email` IS NOT NULL";
                break;
        } // end filter switch

        $db_results = Dba::read($sql, isset($inactive) ? array($inactive) : array());

        $results = array();

        while ($row = Dba::fetch_assoc($db_results)) {
            $results[] = array('id' => $row['id'], 'fullname' => $row['fullname'], 'email' => $row['email']);
        }

        return $results;
    } // get_users

    /**
     * send
     * This actually sends the mail, how amazing
     * @param PHPMailer $phpmailer
     * @return boolean
     * @throws \PHPMailer\PHPMailer\Exception
     */
    public function send($phpmailer = null)
    {
        $mailtype = AmpConfig::get('mail_type');

        if ($phpmailer == null) {
            $mail = new PHPMailer();

            $recipient_name = $this->recipient_name;
            if (function_exists('mb_encode_mimeheader')) {
                $recipient_name = mb_encode_mimeheader($recipient_name);
            }
            $mail->AddAddress($this->recipient, $recipient_name);
        } else {
            $mail = $phpmailer;
        }

        $mail->CharSet     = AmpConfig::get('site_charset');
        $mail->Encoding    = 'base64';
        $mail->From        = $this->sender;
        $mail->Sender      = $this->sender;
        $mail->FromName    = $this->sender_name;
        $mail->Subject     = $this->subject;

        if (function_exists('mb_eregi_replace')) {
            $this->message = mb_eregi_replace("\r\n", "\n", $this->message);
        }
        $mail->Body    = $this->message;

        $sendmail    = AmpConfig::get('sendmail_path');
        $sendmail    = $sendmail ? $sendmail : '/usr/sbin/sendmail';
        $mailhost    = AmpConfig::get('mail_host');
        $mailhost    = $mailhost ? $mailhost : 'localhost';
        $mailport    = AmpConfig::get('mail_port');
        $mailport    = $mailport ? $mailport : 25;
        $mailauth    = AmpConfig::get('mail_auth');
        $mailuser    = AmpConfig::get('mail_auth_user');
        $mailuser    = $mailuser ? $mailuser : '';
        $mailpass    = AmpConfig::get('mail_auth_pass');
        $mailpass    = $mailpass ? $mailpass : '';

        switch ($mailtype) {
            case 'smtp':
                $mail->IsSMTP();
                $mail->Host = $mailhost;
                $mail->Port = $mailport;
                if ($mailauth) {
                    $mail->SMTPAuth = true;
                    $mail->Username = $mailuser;
                    $mail->Password = $mailpass;
                }
                if ($mailsecure = AmpConfig::get('mail_secure_smtp')) {
                    $mail->SMTPSecure = ($mailsecure == 'ssl') ? 'ssl' : 'tls';
                }
                break;
            case 'sendmail':
                $mail->IsSendmail();
                $mail->Sendmail = $sendmail;
                break;
            case 'php':
            default:
                $mail->IsMail();
                break;
        }

        $retval = $mail->send();
        if ($retval === true) {
            return true;
        } else {
            return false;
        }
    } // send

    /**
     * @param $group_name
     * @return boolean
     * @throws \PHPMailer\PHPMailer\Exception
     */
    public function send_to_group($group_name)
    {
        $mail = new PHPMailer();

        foreach (self::get_users($group_name) as $member) {
            if (function_exists('mb_encode_mimeheader')) {
                $member['fullname'] = mb_encode_mimeheader($member['fullname']);
            }
            $mail->AddBCC($member['email'], $member['fullname']);
        }

        return $this->send($mail);
    }
} // end mailer.class
