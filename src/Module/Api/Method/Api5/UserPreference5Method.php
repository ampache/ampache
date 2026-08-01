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
use Ampache\Module\Api\Method\Exception\ResultEmptyException;
use Ampache\Module\Api\Method\MethodInterface;
use Ampache\Module\Api\Output\ApiOutputInterface;
use Ampache\Module\System\Preference;
use Ampache\Repository\Model\User;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;

/**
 * Returns one of the calling user's preferences by name.
 *
 * Version 5 reads the value stored for the calling user and renders a list, so it keeps a method
 * of its own.
 */
final class UserPreference5Method implements MethodInterface
{
    public const string ACTION = 'user_preference';

    public function __construct(
        private StreamFactoryInterface $streamFactory,
    ) {}

    /**
     * user_preference
     * MINIMUM_API_VERSION=5.0.0
     *
     * Get your user preference by name
     *
     * filter = (string) Preference name e.g ('notify_email', 'ajax_load')
     *
     * @param array{
     *     filter?: string,
     *     api_format: string,
     *     auth: string,
     * } $input
     * @param 5 $apiVersion
     * @throws ResultEmptyException
     */
    public function handle(
        GatekeeperInterface $gatekeeper,
        ResponseInterface $response,
        ApiOutputInterface $output,
        array $input,
        User $user,
        int $apiVersion,
    ): ResponseInterface {
        // fix preferences that are missing for user
        Preference::fix_user_preferences($user->id);

        $pref_name = (string) ($input['filter'] ?? '');
        $results   = Preference::get($pref_name, $user->id);
        if (empty($results)) {
            throw new ResultEmptyException($pref_name);
        }

        return $response->withBody(
            $this->streamFactory->createStream(
                $output->objectArray($apiVersion, $results, $results, 'preference')
            )
        );
    }
}
