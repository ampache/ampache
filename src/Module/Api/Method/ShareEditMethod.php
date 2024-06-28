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

namespace Ampache\Module\Api\Method;

use Ampache\Config\AmpConfig;
use Ampache\Module\Api\Exception\ErrorCodeEnum;
use Ampache\Repository\Model\User;
use Ampache\Module\Api\Api;
use Ampache\Repository\ShareRepositoryInterface;

/**
 * Class ShareEditMethod
 * @package Lib\ApiMethods
 */
final class ShareEditMethod
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
     */
    public static function share_edit(array $input, User $user): bool
    {
        if (!AmpConfig::get('share')) {
            Api::error('Enable: share', ErrorCodeEnum::ACCESS_DENIED, self::ACTION, 'system', $input['api_format']);

            return false;
        }
        if (!Api::check_parameter($input, array('filter'), self::ACTION)) {
            return false;
        }
        $share_id = $input['filter'];

        $share = self::getShareRepository()->findById((int) $share_id);

        if (
            $share === null ||
            !$share->isAccessible($user)
        ) {
            /* HINT: Requested object string/id/type ("album", "myusername", "some song title", 1298376) */
            Api::error(sprintf('Not Found: %s', $share_id), ErrorCodeEnum::NOT_FOUND, self::ACTION, 'filter', $input['api_format']);

            return true;
        }

        $description = (isset($input['description'])) ? htmlspecialchars($input['description']) : $share->description;
        $stream      = (isset($input['stream'])) ? filter_var($input['stream'], FILTER_SANITIZE_NUMBER_INT) : $share->allow_stream;
        $download    = (isset($input['download'])) ? filter_var($input['download'], FILTER_SANITIZE_NUMBER_INT) : $share->allow_download;
        $expires     = (isset($input['expires'])) ? filter_var($input['expires'], FILTER_SANITIZE_NUMBER_INT) : $share->expire_days;

        $data = array(
            'max_counter' => $share->max_counter,
            'expire' => $expires,
            'allow_stream' => $stream,
            'allow_download' => $download,
            'description' => $description
        );
        if ($share->update($data, $user)) {
            Api::message('share ' . $share_id . ' updated', $input['api_format']);
        } else {
            /* HINT: Requested object string/id/type ("album", "myusername", "some song title", 1298376) */
            Api::error(sprintf('Bad Request: %s', $share_id), ErrorCodeEnum::BAD_REQUEST, self::ACTION, 'system', $input['api_format']);
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
