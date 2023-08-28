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

declare(strict_types=1);

namespace Ampache\Module\Application\Admin\Access\Lib;

use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Module\Authorization\Access;

final class AccessListItem implements AccessListItemInterface
{
    private Access $access;

    private ModelFactoryInterface $modelFactory;

    public function __construct(
        ModelFactoryInterface $modelFactory,
        Access $access
    ) {
        $this->modelFactory = $modelFactory;
        $this->access       = $access;
    }

    /**
     * take the int level and return a named level
     */
    public function getLevelName(): string
    {
        $level = (int) $this->access->level;

        if ($level >= 75) {
            return T_('All');
        }
        if ($level == 5) {
            return T_('View');
        }
        if ($level == 25) {
            return T_('Read');
        }
        if ($level == 50) {
            return T_('Read/Write');
        }

        return '';
    }

    /**
     * Return a name for the users covered by this ACL.
     */
    public function getUserName(): string
    {
        $userId = (int) $this->access->user;

        if ($userId === -1) {
            return T_('All');
        }

        $user = $this->modelFactory->createUser($userId);

        return sprintf('%s (%s)', $user->fullname, $user->username);
    }

    /**
     * This function returns the pretty name for our current type.
     */
    public function getTypeName(): string
    {
        switch ($this->access->type) {
            case 'rpc':
                return T_('API/RPC');
            case 'network':
                return T_('Local Network Definition');
            case 'interface':
                return T_('Web Interface');
            case 'stream':
            default:
                return T_('Stream Access');
        }
    }

    /**
     * Returns a human readable representation of the start ip
     */
    public function getStartIp(): string
    {
        $result = @inet_ntop($this->access->start);
        if ($result === false) {
            return '';
        }

        return $result;
    }

    /**
     * Returns a human readable representation of the end ip
     */
    public function getEndIp(): string
    {
        $result = @inet_ntop($this->access->end);
        if ($result === false) {
            return '';
        }

        return $result;
    }

    /**
     * Returns the acl name
     */
    public function getName(): string
    {
        return $this->access->name;
    }

    /**
     * Returns the acl item id
     */
    public function getId(): int
    {
        return (int)$this->access->id;
    }

    /**
     * Returns the acl item level
     */
    public function getLevel(): int
    {
        return (int) $this->access->level;
    }

    /**
     * Returns the acl item type
     */
    public function getType(): string
    {
        return $this->access->type;
    }

    /**
     * Returns the acl item user id
     */
    public function getUserId(): int
    {
        return (int) $this->access->user;
    }
}
