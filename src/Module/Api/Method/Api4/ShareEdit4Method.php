<?php

declare(strict_types=0);

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

namespace Ampache\Module\Api\Method\Api4;

use Ampache\Config\AmpConfig;
use Ampache\Repository\Model\User;
use Ampache\Module\Api\Api4;
use Ampache\Repository\ShareRepositoryInterface;

/**
 * Class ShareEdit4Method
 */
final class ShareEdit4Method
{
    public const ACTION = 'share_edit';

    /**
     * share_edit
     * MINIMUM_API_VERSION=420000
     * Update the description and/or expiration date for an existing share.
     * Takes the share id to update with optional description and expires parameters.
     *
     * filter      = (string) Alpha-numeric search term
     * stream      = (boolean) 0,1 //optional
     * download    = (boolean) 0,1 //optional
     * expires     = (integer) number of whole days before expiry //optional
     * description = (string) update description //optional
     *
     * @param array{
     *     filter: string,
     *     stream?: int,
     *     download?: int,
     *     expires?: int,
     *     description?: string,
     *     api_format: string,
     *     auth: string,
     * } $input
     * @param User $user
     * @return bool
     */
    public static function share_edit(array $input, User $user): bool
    {
        if (!AmpConfig::get('share')) {
            Api4::message('error', T_('Access Denied: sharing features are not enabled.'), '400', $input['api_format']);

            return false;
        }
        if (!Api4::check_parameter($input, ['filter'], self::ACTION)) {
            return false;
        }
        $share_id = $input['filter'];

        $share = self::getShareRepository()->findById((int) $share_id);

        if (
            $share === null ||
            !$share->isAccessible($user)
        ) {
            Api4::message('error', 'share ' . $share_id . ' was not found', '404', $input['api_format']);

            return true;
        }

        $description = (isset($input['description'])) ? htmlspecialchars($input['description']) : $share->description;
        $stream      = (isset($input['stream'])) ? filter_var($input['stream'], FILTER_SANITIZE_NUMBER_INT) : $share->allow_stream;
        $download    = (isset($input['download'])) ? filter_var($input['download'], FILTER_SANITIZE_NUMBER_INT) : $share->allow_download;
        $expires     = (isset($input['expires'])) ? filter_var($input['expires'], FILTER_SANITIZE_NUMBER_INT) : $share->expire_days;

        $data = [
            'max_counter' => $share->max_counter,
            'expire' => $expires,
            'allow_stream' => $stream,
            'allow_download' => $download,
            'description' => $description,
        ];
        if ($share->update($data, $user)) {
            Api4::message('success', 'share ' . $share_id . ' updated', null, $input['api_format']);
        } else {
            Api4::message('error', 'share ' . $share_id . ' was not updated', '401', $input['api_format']);
        }

        return true;
    }

    /**
     * @deprecated Inject dependency
     */
    private static function getShareRepository(): ShareRepositoryInterface
    {
        global $dic;

        return $dic->get(ShareRepositoryInterface::class);
    }
}
