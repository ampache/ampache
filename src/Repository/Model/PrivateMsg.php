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

namespace Ampache\Repository\Model;

use Ampache\Config\AmpConfig;
use Ampache\Repository\PrivateMessageRepositoryInterface;

/**
 * This is the class responsible for handling the PrivateMsg object
 * it is related to the user_pvmsg table in the database.
 */
final class PrivateMsg implements PrivateMessageInterface
{
    protected const DB_TABLENAME = 'user_pvmsg';

    private int $id;

    private PrivateMessageRepositoryInterface $privateMessageRepository;

    public function __construct(
        PrivateMessageRepositoryInterface $privateMessageRepository,
        int $id
    ) {
        $this->privateMessageRepository = $privateMessageRepository;
        $this->id                       = $id;
    }

    private ?array $dbData = null;

    private function getDbData(): array
    {
        if ($this->dbData === null) {
            $this->dbData = $this->privateMessageRepository->getDataById($this->id);
        }

        return $this->dbData;
    }

    public function getId(): int
    {
        return (int) ($this->getDbData()['id'] ?? 0);
    }

    public function isNew(): bool
    {
        return $this->getId() === 0;
    }

    public function getSenderUserLink(): string
    {
        $from_user = new User($this->getSenderUserId());
        $from_user->format();

        return $from_user->f_link;
    }

    public function getRecipientUserLink(): string
    {
        $to_user = new User($this->getRecipientUserId());
        $to_user->format();

        return $to_user->f_link;
    }

    public function getCreationDate(): int
    {
        return (int) ($this->getDbData()['creation_date'] ?? 0);
    }

    public function getCreationDateFormatted(): string
    {
        return get_datetime($this->getCreationDate());
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
        return scrub_out($this->getSubject());
    }

    public function isRead(): bool
    {
        return (int) ($this->getDbData()['is_read'] ?? 0);
    }

    public function getRecipientUserId(): int
    {
        return (int) ($this->getDbData()['to_user'] ?? 0);
    }

    public function getSenderUserId(): int
    {
        return (int) ($this->getDbData()['from_user'] ?? 0);
    }

    public function getMessage(): string
    {
        return $this->getDbData()['message'] ?? '';
    }

    public function getSubject(): string
    {
        return $this->getDbData()['subject'] ?? '';
    }
}
