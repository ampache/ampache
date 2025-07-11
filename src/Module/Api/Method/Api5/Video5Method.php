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

namespace Ampache\Module\Api\Method\Api5;

use Ampache\Config\AmpConfig;
use Ampache\Module\Api\Exception\ErrorCodeEnum;
use Ampache\Repository\Model\User;
use Ampache\Repository\Model\Video;
use Ampache\Module\Api\Api5;
use Ampache\Module\Api\Json5_Data;
use Ampache\Module\Api\Xml5_Data;

/**
 * Class Video5Method
 */
final class Video5Method
{
    public const ACTION = 'video';

    /**
     * video
     * This returns a single video
     *
     * filter = (string) UID of video
     *
     * @param array{
     *     filter: string,
     *     api_format: string,
     *     auth: string,
     * } $input
     * @param User $user
     * @return bool
     */
    public static function video(array $input, User $user): bool
    {
        if (!AmpConfig::get('allow_video')) {
            Api5::error(T_('Enable: video'), ErrorCodeEnum::ACCESS_DENIED, self::ACTION, 'system', $input['api_format']);

            return false;
        }
        if (!Api5::check_parameter($input, ['filter'], self::ACTION)) {
            return false;
        }
        $object_id = (int) $input['filter'];
        $video     = new Video($object_id);
        if ($video->isNew()) {
            /* HINT: Requested object string/id/type ("album", "myusername", "some song title", 1298376) */
            Api5::error(sprintf(T_('Not Found: %s'), $object_id), ErrorCodeEnum::NOT_FOUND, self::ACTION, 'filter', $input['api_format']);

            return false;
        }

        switch ($input['api_format']) {
            case 'json':
                echo Json5_Data::videos([$object_id], $user, $input['auth'], false);
                break;
            default:
                echo Xml5_Data::videos([$object_id], $user, $input['auth']);
        }

        return true;
    }
}
