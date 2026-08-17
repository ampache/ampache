<?php

declare(strict_types=1);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2026
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

namespace Ampache\Repository\Model;

use Ampache\Config\AmpConfig;
use Ampache\Module\Database\database_object;
use Ampache\Repository\PrivateMessageRepositoryInterface;

/**
 * This is the class responsible for handling the PrivateMsg object
 * it is related to the user_pvmsg table in the database.
 */
class PrivateMsg extends database_object implements PrivateMessageInterface
{
    protected const string DB_TABLENAME = 'user_pvmsg';

    private ?int $creation_date = null;
    private int $from_user;
    private int $id = 0;
    private bool $is_read;
    private ?string $message = null;
    private ?string $subject = null;
    private int $to_user;

    public function __construct(?int $pm_id = 0)
    {
        if (!$pm_id) {
            return;
        }

        $info                = $this->get_info($pm_id, static::DB_TABLENAME);
        $this->creation_date = isset($info['creation_date']) ? (int) $info['creation_date'] : null;
        $this->from_user     = (int) ($info['from_user'] ?? 0);
        $this->id            = (int) ($info['id'] ?? 0);
        $this->is_read       = (bool) ($info['is_read'] ?? false);
        $this->message       = $info['message'] ?? null;
        $this->subject       = $info['subject'] ?? null;
        $this->to_user       = (int) ($info['to_user'] ?? 0);
    }

    /**
     * Caches a set of private messages in one query rather than one per object, plus the sender/recipient
     * users their row templates link to
     *
     * @param array<int|string> $ids
     */
    public static function build_cache(array $ids): bool
    {
        if ($ids === []) {
            return false;
        }

        // with the cache off these rows are discarded and the per-object queries still run, so this is a net loss
        if (!database_object::isCacheEnabled()) {
            return false;
        }

        $userIds = [];
        foreach (self::getPrivateMessageRepository()->getRowsByIds($ids) as $row) {
            parent::add_to_cache('user_pvmsg', (int) $row['id'], $row);
            $userIds[] = (int) $row['from_user'];
            $userIds[] = (int) $row['to_user'];
        }

        User::build_cache(array_unique($userIds));

        return true;
    }

    /**
     * @deprecated inject dependency
     */
    private static function getPrivateMessageRepository(): PrivateMessageRepositoryInterface
    {
        global $dic;

        return $dic->get(PrivateMessageRepositoryInterface::class);
    }

    public function getCreationDate(): int
    {
        return (int) $this->creation_date;
    }

    public function getCreationDateFormatted(): string
    {
        return get_datetime((int) $this->creation_date);
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getLinkFormatted(): string
    {
        return sprintf(
            '<a href="%s/pvmsg.php?pvmsg_id=%d">%s</a>',
            AmpConfig::get_web_path('/client'),
            $this->id,
            $this->getSubjectFormatted()
        );
    }

    public function getMessage(): string
    {
        return (string) $this->message;
    }

    public function getRecipientUserId(): int
    {
        return $this->to_user;
    }

    public function getRecipientUserLink(): string
    {
        $to_user = new User($this->to_user);
        if ($to_user->isNew()) {
            return '';
        }

        return $to_user->get_f_link();
    }

    public function getSenderUserId(): int
    {
        return $this->from_user;
    }

    public function getSenderUserLink(): string
    {
        return new User($this->from_user)->get_f_link();
    }

    public function getSubject(): string
    {
        return (string) $this->subject;
    }

    public function getSubjectFormatted(): string
    {
        return scrub_out((string) $this->subject);
    }

    public function isNew(): bool
    {
        return $this->getId() === 0;
    }

    public function isRead(): bool
    {
        return (int) $this->is_read === 1;
    }
}
