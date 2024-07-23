<?php

declare(strict_types=1);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2024
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

namespace Ampache\Module\Authorization;

use Ampache\Module\Authorization\Exception\AclItemDuplicationException;
use Ampache\Module\Authorization\Exception\InvalidEndIpException;
use Ampache\Module\Authorization\Exception\InvalidIpRangeException;
use Ampache\Module\Authorization\Exception\InvalidStartIpException;
use Ampache\Repository\AccessRepositoryInterface;

/**
 * Manages the creation and update of acl items
 */
final class AccessListManager implements AccessListManagerInterface
{
    private AccessRepositoryInterface $accessRepository;

    public function __construct(
        AccessRepositoryInterface $accessRepository
    ) {
        $this->accessRepository = $accessRepository;
    }

    /**
     * Updates an existing acl item
     *
     * @throws InvalidEndIpException
     * @throws InvalidIpRangeException
     * @throws InvalidStartIpException
     */
    public function update(
        int $accessId,
        string $startIp,
        string $endIp,
        string $name,
        int $userId,
        AccessLevelEnum $level,
        AccessTypeEnum $type
    ): void {
        $startIp = (string)@inet_pton($startIp);
        $endIp   = (string)@inet_pton($endIp);

        $this->verifyRange($startIp, $endIp);

        $this->accessRepository->update(
            $accessId,
            $startIp,
            $endIp,
            $name,
            $userId,
            $level,
            in_array($type, AccessTypeEnum::CONFIGURABLE_TYPE_LIST) ? $type : AccessTypeEnum::STREAM
        );
    }

    /**
     * Creates a new acl item
     * Also creates further items on special type configs
     *
     * @throws AclItemDuplicationException
     * @throws InvalidEndIpException
     * @throws InvalidIpRangeException
     * @throws InvalidStartIpException
     */
    public function create(
        string $startIp,
        string $endIp,
        string $name,
        int $userId,
        AccessLevelEnum $level,
        AccessTypeEnum $type,
        AccessTypeEnum $additionalType
    ): void {
        $startIp = (string)@inet_pton($startIp);
        $endIp   = (string)@inet_pton($endIp);
        $type    = in_array($type, AccessTypeEnum::CONFIGURABLE_TYPE_LIST) ? $type : AccessTypeEnum::STREAM;

        $this->verifyRange($startIp, $endIp);

        // Check existing ACLs to make sure we're not duplicating values here
        if ($this->accessRepository->exists($startIp, $endIp, $type, $userId) === true) {
            throw new AclItemDuplicationException();
        } else {
            $this->accessRepository->create(
                $startIp,
                $endIp,
                $name,
                $userId,
                $level,
                $type
            );

            // Create Additional stuff based on the type
            if (in_array($additionalType, [AccessTypeEnum::STREAM, AccessTypeEnum::ALL])) {
                if ($this->accessRepository->exists($startIp, $endIp, AccessTypeEnum::STREAM, $userId) === false) {
                    $this->accessRepository->create(
                        $startIp,
                        $endIp,
                        $name,
                        $userId,
                        $level,
                        AccessTypeEnum::STREAM
                    );
                }
            }
            if ($additionalType === AccessTypeEnum::ALL) {
                if ($this->accessRepository->exists($startIp, $endIp, AccessTypeEnum::INTERFACE, $userId) === false) {
                    $this->accessRepository->create(
                        $startIp,
                        $endIp,
                        $name,
                        $userId,
                        $level,
                        AccessTypeEnum::INTERFACE
                    );
                }
            }
        }
    }

    /**
     * Verifies the entered ip addresses
     *
     * @param string|bool $startIp
     * @param string|bool $endIp
     *
     * @throws InvalidEndIpException
     * @throws InvalidIpRangeException
     * @throws InvalidStartIpException
     */
    private function verifyRange($startIp, $endIp): void
    {
        if (!$startIp && $startIp != '0.0.0.0' && $startIp != '::') {
            throw new InvalidStartIpException();
        }
        if (!$endIp) {
            throw new InvalidEndIpException();
        }

        if (strlen(bin2hex((string)$startIp)) != strlen(bin2hex((string)$endIp))) {
            throw new InvalidIpRangeException();
        }
    }
}
