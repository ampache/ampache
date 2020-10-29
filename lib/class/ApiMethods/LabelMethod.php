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

namespace Lib\ApiMethods;

use AmpConfig;
use Api;
use JSON_Data;
use Label;
use Session;
use XML_Data;

/**
 * Class LabelMethod
 * @package Lib\ApiMethods
 */
final class LabelMethod
{
    private const ACTION = 'label';

    /**
     * label
     * MINIMUM_API_VERSION=420000
     *
     * This returns a single label based on UID
     *
     * @param array $input
     * filter = (string) UID of label
     * @return boolean
     */
    public static function label(array $input)
    {
        if (!AmpConfig::get('label')) {
            Api::error(T_('Enable: label'), '4703', self::ACTION, 'system', $input['api_format']);

            return false;
        }
        if (!Api::check_parameter($input, array('filter'), self::ACTION)) {
            return false;
        }
        $object_id = (int) $input['filter'];
        $label     = new Label($object_id);
        if (!$label->id) {
            /* HINT: Requested object string/id/type ("album", "myusername", "some song title", 1298376) */
            Api::error(sprintf(T_('Not Found: %s'), $object_id), '4704', self::ACTION, 'filter', $input['api_format']);

            return false;
        }

        ob_end_clean();
        switch ($input['api_format']) {
            case 'json':
                echo JSON_Data::labels(array($object_id));
                break;
            default:
                echo XML_Data::labels(array($object_id));
        }
        Session::extend($input['auth']);

        return true;
    }
}
