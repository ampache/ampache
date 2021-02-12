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
use User;
use XML_Data;

/**
 * Class LabelArtistsMethod
 * @package Lib\ApiMethods
 */
final class LabelArtistsMethod
{
    private const ACTION = 'label_artists';

    /**
     * label_artists
     * MINIMUM_API_VERSION=420000
     *
     * This returns all artists attached to a label ID
     *
     * @param array $input
     * filter = (string) UID of label
     * include = (array|string) 'albums', 'songs' //optional
     * @return boolean
     */
    public static function label_artists(array $input)
    {
        if (!AmpConfig::get('label')) {
            Api::error(T_('Enable: label'), '4703', self::ACTION, 'system', $input['api_format']);

            return false;
        }
        if (!Api::check_parameter($input, array('filter'), self::ACTION)) {
            return false;
        }
        $user    = User::get_from_username(Session::username($input['auth']));
        $include = (is_array($input['include'])) ? $input['include'] : explode(',', (string) $input['include']);
        $label   = new Label((int) scrub_in($input['filter']));
        $artists = $label->get_artists();
        if (empty($artists)) {
            Api::empty('artist', $input['api_format']);

            return false;
        }

        ob_end_clean();
        switch ($input['api_format']) {
            case 'json':
                echo JSON_Data::artists($artists, $include, $user->id);
                break;
            default:
                echo XML_Data::artists($artists, $include, $user->id);
        }
        Session::extend($input['auth']);

        return true;
    }
}
