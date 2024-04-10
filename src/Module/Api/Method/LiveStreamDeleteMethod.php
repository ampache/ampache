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

use Ampache\Module\Api\Exception\ErrorCodeEnum;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Repository\LiveStreamRepositoryInterface;
use Ampache\Repository\Model\Live_Stream;
use Ampache\Repository\Model\User;
use Ampache\Module\Api\Api;

/**
 * Class LiveStreamDeleteMethod
 * @package Lib\ApiMethods
 */
final class LiveStreamDeleteMethod
{
    public const ACTION = 'live_stream_delete';

    /**
     * live_stream_delete
     * MINIMUM_API_VERSION=6.0.0
     *
     * Delete an existing live_stream (radio station). (if it exists)
     *
     * filter = (string) object_id to delete
     */
    public static function live_stream_delete(array $input, User $user): bool
    {
        if (!Api::check_access(AccessTypeEnum::INTERFACE, AccessLevelEnum::CONTENT_MANAGER, $user->id, self::ACTION, $input['api_format'])) {
            return false;
        }
        if (!Api::check_parameter($input, array('filter'), self::ACTION)) {
            return false;
        }
        unset($user);

        $liveStreamRepository = self::getLiveStreamRepository();

        $object_id = (int)$input['filter'];

        $liveStream = $liveStreamRepository->findById($object_id);
        if ($liveStream === null) {
            /* HINT: Requested object string/id/type ("album", "myusername", "some song title", 1298376) */
            Api::error(sprintf('Not Found: %s', $object_id), ErrorCodeEnum::NOT_FOUND, self::ACTION, 'filter', $input['api_format']);

            return false;
        }

        $liveStreamRepository->delete($liveStream);

        Api::message('Deleted live_stream: ' . $object_id, $input['api_format']);

        return true;
    }

    private static function getLiveStreamRepository(): LiveStreamRepositoryInterface
    {
        global $dic;

        return $dic->get(LiveStreamRepositoryInterface::class);
    }
}
