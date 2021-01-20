<?php
/*
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
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

declare(strict_types=0);

namespace Ampache\Model;

use Ampache\Config\AmpConfig;

/**
 * This is the class responsible for handling the PrivateMsg object
 * it is related to the user_pvmsg table in the database.
 */
class PrivateMsg extends database_object
{
    protected const DB_TABLENAME = 'user_pvmsg';

    /* Variables from DB */
    /**
     * @var integer $id
     */
    public $id;

    /**
     * @var string $subject
     */
    public $subject;

    /**
     * @var string $message
     */
    public $message;

    /**
     * @var integer $from_user
     */
    public $from_user;

    /**
     * @var integer $to_user
     */
    public $to_user;

    /**
     * @var integer $creation_date
     */
    public $creation_date;

    /**
     * @var boolean $is_read
     */
    public $is_read;

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

    public function getId(): int
    {
        return (int) $this->id;
    }

    public function getSenderUserLink(): string
    {
        $from_user = new User($this->from_user);
        $from_user->format();

        return $from_user->f_link;
    }

    public function getRecipientUserLink(): string
    {
        $to_user = new User($this->to_user);
        $to_user->format();

        return $to_user->f_link;
    }

    public function getCreationDateFormatted(): string
    {
        return get_datetime((int) $this->creation_date);
    }

    public function getLinkFormatted(): string
    {
        return sprintf(
            '<a href="%s/pvmsg.php?pvmsg_id=%d">%s</a>',
            AmpConfig::get('web_path'),
            $this->id,
            $this->getSubjectFormatted()
        );
    }

    public function getSubjectFormatted(): string
    {
        return scrub_out($this->subject);
    }
}
