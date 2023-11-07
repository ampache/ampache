<?php
/*
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

declare(strict_types=0);

namespace Ampache\Repository\Model;

use Ampache\Config\AmpConfig;

/**
 * This is the class responsible for handling the PrivateMsg object
 * it is related to the user_pvmsg table in the database.
 */
class PrivateMsg extends database_object implements PrivateMessageInterface
{
    protected const DB_TABLENAME = 'user_pvmsg';

    /* Variables from DB */
    /**
     * @var int $id
     */
    private $id;

    /**
     * @var string $subject
     */
    private $subject;

    /**
     * @var string $message
     */
    private $message;

    /**
     * @var int $from_user
     */
    private $from_user;

    /**
     * @var int $to_user
     */
    private $to_user;

    /**
     * @var int $creation_date
     */
    private $creation_date;

    /**
     * @var bool $is_read
     */
    private $is_read;

    /**
     * @param int $pm_id
     */
    public function __construct($pm_id)
    {
        $info = $this->get_info($pm_id, static::DB_TABLENAME);
        foreach ($info as $key => $value) {
            $this->$key = $value;
        }

        return true;
    }

    public function getId(): int
    {
        return (int)($this->id ?? 0);
    }

    public function isNew(): bool
    {
        return $this->getId() === 0;
    }

    public function getSenderUserLink(): string
    {
        $from_user = new User((int) $this->from_user);
        $from_user->format();

        return $from_user->f_link;
    }

    public function getRecipientUserLink(): string
    {
        $to_user = new User((int) $this->to_user);
        $to_user->format();

        return $to_user->f_link;
    }

    public function getCreationDate(): int
    {
        return (int) $this->creation_date;
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
        return scrub_out((string) $this->subject);
    }

    public function isRead(): bool
    {
        return (int) $this->is_read === 1;
    }

    public function getRecipientUserId(): int
    {
        return (int) $this->to_user;
    }

    public function getSenderUserId(): int
    {
        return (int) $this->from_user;
    }

    public function getMessage(): string
    {
        return (string) $this->message;
    }

    public function getSubject(): string
    {
        return (string) $this->subject;
    }
}
