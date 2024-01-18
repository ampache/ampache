<?php

declare(strict_types=0);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2023
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

namespace Ampache\Module\Util;

use Ampache\Config\AmpConfig;
use Ampache\Module\System\Dba;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;

/**
 * This class handles the Mail
 */
final class Mailer implements MailerInterface
{
    private ?string $message = null;

    private ?string $subject = null;

    private ?string $recipient = null;

    private ?string $recipient_name = null;

    private ?string $sender = null;

    private ?string $sender_name = null;

    /**
     * Set the actual mail body/message
     */
    public function setMessage(string $message): MailerInterface
    {
        $this->message = $message;

        return $this;
    }

    /**
     * Set the mail subject
     */
    public function setSubject(string $subject): MailerInterface
    {
        $this->subject = $subject;

        return $this;
    }

    /**
     * Set recipient email and -name
     */
    public function setRecipient(string $recipientEmail, string $recipientName = ''): MailerInterface
    {
        $this->recipient      = $recipientEmail;
        $this->recipient_name = $recipientName;

        return $this;
    }

    /**
     * Set sender email and -name
     */
    public function setSender(string $senderEmail, string $senderName = ''): MailerInterface
    {
        $this->sender      = $senderEmail;
        $this->sender_name = $senderName;

        return $this;
    }

    /**
     * is_mail_enabled
     *
     * Check that the mail feature is enabled. By default, you people to configure their mail settings first
     */
    public static function is_mail_enabled(): bool
    {
        if (AmpConfig::get('mail_enable') && !AmpConfig::get('demo_mode')) {
            return true;
        }

        return false;
    }

    /**
     * Check that the mail feature is enabled
     */
    public function isMailEnabled(): bool
    {
        return static::is_mail_enabled();
    }

    /**
     * validate_address
     *
     * Checks whether what we have looks like a valid address.
     * @param string $address
     */
    public static function validate_address($address): bool
    {
        return PHPMailer::ValidateAddress($address);
    }

    /**
     * set_default_sender
     *
     * Does the config magic to figure out the "system" email sender and
     * sets it as the sender.
     */
    public function set_default_sender(): MailerInterface
    {
        $user     = AmpConfig::get('mail_user', 'info');
        $domain   = AmpConfig::get('mail_domain', 'example.com');
        $fromname = AmpConfig::get('mail_name', 'Ampache');

        $this->sender      = $user . '@' . $domain;
        $this->sender_name = $fromname;

        return $this;
    }

    /**
     * get_users
     * This returns an array of userids for people who have e-mail
     * addresses based on the passed filter
     * @param $filter
     * @return array
     */
    public static function get_users($filter): array
    {
        switch ($filter) {
            case 'users':
                $sql = "SELECT * FROM `user` WHERE `access`='25' AND `email` IS NOT NULL";
                break;
            case 'admins':
                $sql = "SELECT * FROM `user` WHERE `access`='100' AND `email` IS NOT NULL";
                break;
            case 'inactive':
                $inactive = time() - 2592000;
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
            $results[] = array(
                'id' => $row['id'],
                'fullname' => $row['fullname'],
                'email' => $row['email']
            );
        }

        return $results;
    }

    /**
     * send
     * This actually sends the mail, how amazing
     * @param PHPMailer $phpmailer
     * @return bool
     * @throws Exception
     */
    public function send($phpmailer = null): bool
    {
        $mailtype = AmpConfig::get('mail_type', 'php');

        if ($phpmailer == null) {
            $mail = new PHPMailer();

            $recipient_name = (string) $this->recipient_name;
            if (function_exists('mb_encode_mimeheader')) {
                $recipient_name = mb_encode_mimeheader($recipient_name);
            }
            $mail->AddAddress((string) $this->recipient, $recipient_name);
        } else {
            $mail = $phpmailer;
        }

        $mail->CharSet  = AmpConfig::get('site_charset');
        $mail->Encoding = 'base64';
        $mail->From     = (string) $this->sender;
        $mail->Sender   = (string) $this->sender;
        $mail->FromName = (string) $this->sender_name;
        $mail->Subject  = (string) $this->subject;

        if (function_exists('mb_eregi_replace')) {
            $this->message = (string) mb_eregi_replace("\r\n", "\n", (string) $this->message);
        }
        $mail->Body = (string) $this->message;

        $sendmail = AmpConfig::get('sendmail_path', '/usr/sbin/sendmail');
        $mailhost = AmpConfig::get('mail_host', 'localhost');
        $mailport = AmpConfig::get('mail_port', 25);
        $mailauth = AmpConfig::get('mail_auth');
        $mailuser = AmpConfig::get('mail_auth_user', '');
        $mailpass = AmpConfig::get('mail_auth_pass', '');

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
            debug_event(self::class, 'Did not send mail. ErrorInfo: ' . ($mail['ErrorInfo'] ?? ''), 5);

            return false;
        }
    }

    /**
     * @param $group_name
     * @return bool
     * @throws Exception
     */
    public function send_to_group($group_name): bool
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
}
