<?php

/*
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public Label, version 3 (AGPL-3.0-or-later)
 * Copyright 2001 - 2020 Ampache.org
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

namespace Ampache\Module\Api\Method;

use Ampache\Config\AmpConfig;
use Ampache\Repository\Model\Live_Stream;
use Ampache\Module\Api\Api;
use Ampache\Module\Api\Json_Data;
use Ampache\Module\Api\Xml_Data;

/**
 * Class LiveStreamMethod
 * @package Lib\ApiMethods
 */
final class LiveStreamMethod
{
    public const ACTION = 'live_stream';

    /**
     * live_stream
     * MINIMUM_API_VERSION=5.1.0
     *
     * This returns a single live_stream based on UID
     *
     * @param array $input
     * filter = (string) UID of live_stream
     * @return boolean
     */
    public static function live_stream(array $input): bool
    {
        if (!AmpConfig::get('live_stream')) {
            Api::error(T_('Enable: live_stream'), '4703', self::ACTION, 'system', $input['api_format']);

            return false;
        }
        if (!Api::check_parameter($input, array('filter'), self::ACTION)) {
            return false;
        }
        $object_id   = (int) $input['filter'];
        $live_stream = new Live_Stream($object_id);
        if (!$live_stream->id) {
            /* HINT: Requested object string/id/type ("album", "myusername", "some song title", 1298376) */
            Api::error(sprintf(T_('Not Found: %s'), $object_id), '4704', self::ACTION, 'filter', $input['api_format']);

            return false;
        }

        ob_end_clean();
        switch ($input['api_format']) {
            case 'json':
                echo Json_Data::live_streams(array($object_id), false);
                break;
            default:
                echo Xml_Data::live_streams(array($object_id));
        }

        return true;
    }
}
