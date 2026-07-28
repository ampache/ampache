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

namespace Ampache\Module\Api\Method;

use Ampache\Module\Api\Authentication\GatekeeperInterface;
use Ampache\Module\Api\Output\ApiOutputInterface;
use Ampache\Repository\Model\Preference;
use Ampache\Repository\Model\User;
use Ampache\Repository\PreferenceRepositoryInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Returns the calling user's preferences
 */
final class UserPreferencesMethod implements MethodInterface
{
    public const string ACTION = 'user_preferences';

    public const string REST_ACTION = 'preferences';

    private PreferenceItemBuilder $preferenceItemBuilder;
    private PreferenceRepositoryInterface $preferenceRepository;

    public function __construct(
        PreferenceRepositoryInterface $preferenceRepository,
        PreferenceItemBuilder $preferenceItemBuilder,
    ) {
        $this->preferenceRepository  = $preferenceRepository;
        $this->preferenceItemBuilder = $preferenceItemBuilder;
    }

    /**
     * MINIMUM_API_VERSION=5.0.0
     *
     * Get your user preferences
     *
     * @param array{
     *     api_format: string,
     *     auth: string,
     * } $input
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
        Preference::fix_user_preferences($user->getId());
        $user->get_preferences();

        $preferences = $this->preferenceRepository->getAll($user, true);
        if ($apiVersion >= 8) {
            $preferences = $this->preferenceItemBuilder->buildList($preferences);
        }

        $response->getBody()->write(
            $output->objectArray(
                $apiVersion,
                ['preference' => $preferences],
                $preferences,
                'preference'
            )
        );

        return $response;
    }
}
