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

/**
 * PrivateMsg class
 *
 * This is the class responsible for handling the PrivateMsg object
 * it is related to the user_pvmsg table in the database.
 */
class PrivateMsg extends database_object
{
    /* Variables from DB */
    /**
     *  @var integer $id
     */
    public $id;

    /**
     *  @var string $subject
     */
    public $subject;

    /**
     *  @var string $message
     */
    public $message;

    /**
     *  @var integer $from_user
     */
    public $from_user;

    /**
     *  @var integer $to_user
     */
    public $to_user;

    /**
     *  @var integer $creation_date
     */
    public $creation_date;

    /**
     *  @var boolean $is_read
     */
    public $is_read;

    /**
     *  @var string $f_subject
     */
    public $f_subject;

    /**
     *  @var string $f_message
     */
    public $f_message;

    /**
     *  @var string $link
     */
    public $link;

    /**
     *  @var string $f_link
     */
    public $f_link;

    /**
     *  @var string $f_from_user_link
     */
    public $f_from_user_link;

    /**
     *  @var string $f_to_user_link
     */
    public $f_to_user_link;

    /**
     *  @var string $f_creation_date
     */
    public $f_creation_date;

    /**
     * __construct
     * @param integer $pm_id
     */
    public function __construct($pm_id)
    {
        $info = $this->get_info($pm_id, 'user_pvmsg');
        foreach ($info as $key => $value) {
            $this->$key = $value;
        }

        return true;
    }

    /**
     * @param boolean $details
     */
    public function format($details = true)
    {
        unset($details); //dead code but called from other format calls
        $this->f_subject       = scrub_out($this->subject);
        $this->f_message       = scrub_out($this->message);
        $time_format           = AmpConfig::get('custom_datetime') ? (string) AmpConfig::get('custom_datetime') : 'm/d/Y H:i:s';
        $this->f_creation_date = get_datetime($time_format, (int) $this->creation_date);
        $from_user             = new User($this->from_user);
        $from_user->format();
        $this->f_from_user_link = $from_user->f_link;
        $to_user                = new User($this->to_user);
        $to_user->format();
        $this->f_to_user_link = $to_user->f_link;
        $this->link           = AmpConfig::get('web_path') . '/pvmsg.php?pvmsg_id=' . $this->id;
        $this->f_link         = "<a href=\"" . $this->link . "\">" . $this->f_subject . "</a>";
    }

    /**
     * set_is_read
     * @param integer $read
     * @return PDOStatement|boolean
     */
    public function set_is_read($read)
    {
        $sql = "UPDATE `user_pvmsg` SET `is_read` = ? WHERE `id` = ?";

        return Dba::write($sql, array($read, $this->id));
    }

    /**
     * delete
     * @return PDOStatement|boolean
     */
    public function delete()
    {
        $sql = "DELETE FROM `user_pvmsg` WHERE `id` = ?";

        return Dba::write($sql, array($this->id));
    }

    /**
     * @param array $data
     * @return boolean|string|null
     */
    public static function create(array $data)
    {
        $subject = trim(strip_tags(filter_var($data['subject'], FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES)));
        $message = trim(strip_tags(filter_var($data['message'], FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES)));

        if (empty($subject)) {
            AmpError::add('subject', T_('Subject is required'));
        }

        $to_user = User::get_from_username($data['to_user']);
        if (!$to_user->id) {
            AmpError::add('to_user', T_('Unknown user'));
        }

        if (!AmpError::occurred()) {
            $from_user     = $data['from_user'] ?: Core::get_global('user')->id;
            $creation_date = $data['creation_date'] ?: time();
            $is_read       = $data['is_read'] ?: 0;
            $sql           = "INSERT INTO `user_pvmsg` (`subject`, `message`, `from_user`, `to_user`, `creation_date`, `is_read`) " .
                    "VALUES (?, ?, ?, ?, ?, ?)";
            if (Dba::write($sql, array($subject, $message, $from_user, $to_user->id, $creation_date, $is_read))) {
                $insert_id = Dba::insert_id();

                // Never send email in case of user impersonation
                if (!isset($data['from_user']) && $insert_id) {
                    if (Preference::get_by_user($to_user->id, 'notify_email')) {
                        if (!empty($to_user->email) && Mailer::is_mail_enabled()) {
                            $mailer = new Mailer();
                            $mailer->set_default_sender();
                            $mailer->recipient      = $to_user->email;
                            $mailer->recipient_name = $to_user->fullname;
                            $mailer->subject        = "[" . T_('Private Message') . "] " . $subject;
                            /* HINT: User fullname */
                            $mailer->message = sprintf(T_("You received a new private message from %s."), Core::get_global('user')->fullname);
                            $mailer->message .= "\n\n----------------------\n\n";
                            $mailer->message .= $message;
                            $mailer->message .= "\n\n----------------------\n\n";
                            $mailer->message .= AmpConfig::get('web_path') . "/pvmsg.php?action=show&pvmsg_id=" . $insert_id;
                            $mailer->send();
                        }
                    }
                }

                return $insert_id;
            }
        }

        return false;
    }

    /**
     * get_private_msgs
     * Get the user received private messages.
     * @param integer $to_user
     * @param boolean $unread_only
     * @param integer $from_user
     * @return integer[]
     */
    public static function get_private_msgs($to_user, $unread_only = false, $from_user = 0)
    {
        $sql    = "SELECT `id` FROM `user_pvmsg` WHERE `to_user` = ?";
        $params = array($to_user);
        if ($unread_only) {
            $sql .= " AND `is_read` = '0'";
        }
        if ($from_user > 0) {
            $sql .= " AND `from_user` = ?";
            $params[] = $from_user;
        }

        $db_results = Dba::read($sql, $params);
        $results    = array();
        while ($row = Dba::fetch_assoc($db_results)) {
            $results[] = $row['id'];
        }

        return $results;
    }
    /**
     * send_chat_msgs
     * Get the subsonic chat messages.
     * @param string message
     * @param integer $user_id
     * @return string|null
     */
    public static function send_chat_msg($message, $user_id)
    {
        if (!AmpConfig::get('sociable')) {
            return null;
        }
        $message = trim(strip_tags(filter_var($message, FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES)));

        $sql = "INSERT INTO `user_pvmsg` (`subject`, `message`, `from_user`, `to_user`, `creation_date`, `is_read`) ";
        $sql .= "VALUES (?, ?, ?, ?, ?, ?)";
        if (Dba::write($sql, array(null, $message, $user_id, 0, time(), 0))) {
            return Dba::insert_id();
        }

        return null;
    }

    /**
     * get_chat_msgs
     * Get the subsonic chat messages.
     * @param integer $since
     * @return array
     */
    public static function get_chat_msgs($since = 0)
    {
        if (!AmpConfig::get('sociable')) {
            return array();
        }
        self::clean_chat_msgs();

        $sql = "SELECT `id` FROM `user_pvmsg` WHERE `to_user` = 0 ";
        $sql .= " AND `user_pvmsg`.`creation_date` > " . (string) $since;
        $sql .= " ORDER BY `user_pvmsg`.`creation_date` DESC";

        $db_results = Dba::read($sql);
        $results    = array();
        while ($row = Dba::fetch_assoc($db_results)) {
            $results[] = $row['id'];
        }

        return $results;
    }

    /**
     * clean_chat_msgs
     * Clear old messages from the subsonic chat message list.
     * @param integer $days
     */
    public static function clean_chat_msgs($days = 30)
    {
        $sql = "DELETE FROM `user_pvmsg` WHERE `to_user` = 0 AND ";
        $sql .= "`creation_date` <= UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL " . (string) $days . " day))";
        Dba::write($sql);
    }
} // end privatemsg.class
