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

namespace Ampache\Module\Api\Method\Api5;

use Ampache\Module\Api\Authentication\GatekeeperInterface;
use Ampache\Module\Api\Method\Exception\AccessFailedException;
use Ampache\Module\Api\Method\MethodInterface;
use Ampache\Module\Api\Output\ApiOutputInterface;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Module\Authorization\Check\PrivilegeCheckerInterface;
use Ampache\Repository\Model\User;
use Ampache\Repository\PreferenceRepositoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;

/**
 * Returns the system preferences.
 *
 * Version 5 wraps the json payload in a `preference` array, so it keeps a method of its own.
 */
final class SystemPreferences5Method implements MethodInterface
{
    public const string ACTION = 'system_preferences';

    public function __construct(
        private PreferenceRepositoryInterface $preferenceRepository,
        private PrivilegeCheckerInterface $privilegeChecker,
        private StreamFactoryInterface $streamFactory,
    ) {}

    /**
     * system_preferences
     * MINIMUM_API_VERSION=5.0.0
     *
     * Get your system preferences
     *
     * @param array{
     *     api_format: string,
     *     auth: string,
     * } $input
     * @param 5 $apiVersion
     * @throws AccessFailedException
     */
    public function handle(
        GatekeeperInterface $gatekeeper,
        ResponseInterface $response,
        ApiOutputInterface $output,
        array $input,
        User $user,
        int $apiVersion,
    ): ResponseInterface {
        if (
            !$this->privilegeChecker->check(
                AccessTypeEnum::INTERFACE,
                AccessLevelEnum::ADMIN,
                $user->id
            )
        ) {
            throw new AccessFailedException(
                sprintf('Require: %s', AccessLevelEnum::ADMIN->value)
            );
        }

        $results = ['preference' => $this->preferenceRepository->getAll()];

        return $response->withBody(
            $this->streamFactory->createStream(
                $output->objectArray($apiVersion, $results, $results['preference'], 'preference')
            )
        );
    }
}
