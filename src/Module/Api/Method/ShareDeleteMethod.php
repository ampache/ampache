<?php

declare(strict_types=0);

/**
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

namespace Ampache\Module\Api\Method;

use Ampache\Config\AmpConfig;
use Ampache\Module\Api\Exception\ErrorCodeEnum;
use Ampache\Repository\Model\Catalog;
use Ampache\Repository\Model\Share;
use Ampache\Repository\Model\User;
use Ampache\Module\Api\Api;
use Ampache\Repository\ShareRepositoryInterface;

/**
 * Class ShareDeleteMethod
 * @package Lib\ApiMethods
 */
final class ShareDeleteMethod
{
    public const ACTION = 'share_delete';

    /**
     * share_delete
     * MINIMUM_API_VERSION=420000
     *
     * Delete an existing share.
     *
     * filter = (string) UID of share to delete
     */
    public static function share_delete(array $input, User $user): bool
    {
        if (!AmpConfig::get('share')) {
            Api::error('Enable: share', ErrorCodeEnum::ACCESS_DENIED, self::ACTION, 'system', $input['api_format']);

            return false;
        }
        if (!Api::check_parameter($input, array('filter'), self::ACTION)) {
            return false;
        }
        $object_id = $input['filter'];

        $shareRepository = self::getShareRepository();

        $share = $shareRepository->findById((int) $object_id);

        if (
            $share === null ||
            !$share->isAccessible($user)
        ) {
            /* HINT: Requested object string/id/type ("album", "myusername", "some song title", 1298376) */
            Api::error(sprintf('Not Found: %s', $object_id), ErrorCodeEnum::NOT_FOUND, self::ACTION, 'filter', $input['api_format']);

            return true;
        }

        $shareRepository->delete($share);

        Api::message('share ' . $object_id . ' deleted', $input['api_format']);
        Catalog::count_table('share');

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
