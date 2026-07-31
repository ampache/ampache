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
use Ampache\Module\Api\Method\Exception\ResultEmptyException;
use Ampache\Module\Api\Output\ApiOutputInterface;
use Ampache\Repository\Model\Preference;
use Ampache\Repository\Model\User;
use Psr\Http\Message\ResponseInterface;

/**
 * Returns one of the calling user's preferences by name
 */
final class UserPreferenceMethod implements MethodInterface
{
    public const string ACTION = 'user_preference';

    private PreferenceItemBuilder $preferenceItemBuilder;

    public function __construct(
        PreferenceItemBuilder $preferenceItemBuilder,
    ) {
        $this->preferenceItemBuilder = $preferenceItemBuilder;
    }

    /**
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
        Preference::fix_user_preferences($user->getId());

        $prefName   = (string) ($input['filter'] ?? '');
        $preference = Preference::get($prefName, $user->getId());
        if (empty($preference)) {
            throw new ResultEmptyException(
                $prefName
            );
        }

        $item = $this->preferenceItemBuilder->build($preference, $user);
        if ($apiVersion >= 8) {
            $item['id'] = (string) $item['id'];
        }

        $response->getBody()->write(
            $output->objectArray($apiVersion, $item, [$item], 'preference')
        );

        return $response;
    }
}
