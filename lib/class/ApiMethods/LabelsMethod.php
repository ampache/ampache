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
use Session;
use XML_Data;

/**
 * Class LabelsMethod
 * @package Lib\ApiMethods
 */
final class LabelsMethod
{
    private const ACTION = 'labels';

    /**
     * labels
     * MINIMUM_API_VERSION=420000
     *
     * This returns the labels  based on the specified filter
     *
     * @param array $input
     * filter = (string) Alpha-numeric search term //optional
     * exact  = (integer) 0,1, if true filter is exact rather then fuzzy //optional
     * offset = (integer) //optional
     * limit  = (integer) //optional
     * @return boolean
     */
    public static function labels(array $input)
    {
        if (!AmpConfig::get('label')) {
            Api::error(T_('Enable: label'), '4703', self::ACTION, 'system', $input['api_format']);

            return false;
        }

        Api::$browse->reset_filters();
        Api::$browse->set_type('label');
        Api::$browse->set_sort('name', 'ASC');

        $method = $input['exact'] ? 'exact_match' : 'alpha_match';
        Api::set_filter($method, $input['filter']);
        $labels = Api::$browse->get_objects();
        if (empty($labels)) {
            Api::empty('label', $input['api_format']);

            return false;
        }

        ob_end_clean();
        switch ($input['api_format']) {
            case 'json':
                JSON_Data::set_offset($input['offset']);
                JSON_Data::set_limit($input['limit']);
                echo JSON_Data::labels($labels);
                break;
            default:
                XML_Data::set_offset($input['offset']);
                XML_Data::set_limit($input['limit']);
                echo XML_Data::labels($labels);
        }
        Session::extend($input['auth']);

        return true;
    }
}
