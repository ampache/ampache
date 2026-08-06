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

namespace Ampache\Module\Api\Method\Api4;

use Ampache\Config\AmpConfig;
use Ampache\Module\Api\Api4;
use Ampache\Module\Api\Authentication\GatekeeperInterface;
use Ampache\Module\Api\Method\MethodInterface;
use Ampache\Module\Api\Output\ApiOutputInterface;
use Ampache\Repository\Model\User;
use Ampache\Repository\ShareRepositoryInterface;
use Psr\Http\Message\ResponseInterface;

final class ShareDelete4Method implements MethodInterface
{
    public const string ACTION = 'share_delete';

    public function __construct(
        private ShareRepositoryInterface $shareRepository,
    ) {}

    /**
     * share_delete
     * MINIMUM_API_VERSION=420000
     *
     * Delete an existing share.
     *
     * filter = (string) UID of share to delete
     *
     * @param array{
     *     filter: string,
     *     api_format: string,
     *     auth: string,
     * } $input
     * @param 4 $apiVersion
     */
    public function handle(
        GatekeeperInterface $gatekeeper,
        ResponseInterface $response,
        ApiOutputInterface $output,
        array $input,
        User $user,
        int $apiVersion,
    ): ResponseInterface {
        if (!AmpConfig::get('share')) {
            Api4::message('error', 'Access Denied: sharing features are not enabled.', '400', $input['api_format']);

            return $response;
        }
        if (!Api4::check_parameter($input, ['filter'], self::ACTION)) {
            return $response;
        }
        $object_id = $input['filter'];

        $shareRepository = $this->shareRepository;

        $share = $shareRepository->findById((int) $object_id);

        if (
            $share === null
            || !$share->isAccessible($user)
        ) {
            Api4::message('error', 'share ' . $object_id . ' was not found', '404', $input['api_format']);

            return $response;
        }

        $shareRepository->delete($share);

        Api4::message('success', 'share ' . $object_id . ' deleted', null, $input['api_format']);

        return $response;
    }
}
