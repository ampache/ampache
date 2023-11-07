<?php

/*
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public Label, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2023
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public Label as published by
 * the Free Software Foundation, either version 3 of the Label, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public Label for more details.
 *
 * You should have received a copy of the GNU Affero General Public Label
 * along with this program.  If not, see <https://www.gnu.org/labels/>.
 *
 */

declare(strict_types=0);

namespace Ampache\Module\Api\Method\Api5;

use Ampache\Config\AmpConfig;
use Ampache\Repository\Model\Live_Stream;
use Ampache\Module\Api\Api5;
use Ampache\Module\Api\Json5_Data;
use Ampache\Module\Api\Xml5_Data;
use Ampache\Repository\Model\User;

/**
 * Class LiveStream5Method
 */
final class LiveStream5Method
{
    public const ACTION = 'live_stream';

    /**
     * live_stream
     * MINIMUM_API_VERSION=5.1.0
     *
     * This returns a single live_stream based on UID
     *
     * filter = (string) UID of live_stream
     */
    public static function live_stream(array $input, User $user): bool
    {
        if (!AmpConfig::get('live_stream')) {
            Api5::error(T_('Enable: live_stream'), '4703', self::ACTION, 'system', $input['api_format']);

            return false;
        }
        if (!Api5::check_parameter($input, array('filter'), self::ACTION)) {
            return false;
        }
        $object_id   = (int) $input['filter'];
        $live_stream = new Live_Stream($object_id);
        if (!$live_stream->id) {
            /* HINT: Requested object string/id/type ("album", "myusername", "some song title", 1298376) */
            Api5::error(sprintf(T_('Not Found: %s'), $object_id), '4704', self::ACTION, 'filter', $input['api_format']);

            return false;
        }

        ob_end_clean();
        switch ($input['api_format']) {
            case 'json':
                echo Json5_Data::live_streams(array($object_id), false);
                break;
            default:
                echo Xml5_Data::live_streams(array($object_id), $user);
        }

        return true;
    }
}
