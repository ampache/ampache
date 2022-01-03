<?php

/*
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 *  LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright 2001 - 2020 Ampache.org
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

declare(strict_types=0);

namespace Ampache\Module\Api\Method;

use Ampache\Config\AmpConfig;
use Ampache\Repository\Model\Catalog;
use Ampache\Repository\Model\Podcast;
use Ampache\Repository\Model\User;
use Ampache\Module\Api\Api;
use Ampache\Module\Api\Json_Data;
use Ampache\Module\Api\Xml_Data;
use Ampache\Module\System\Session;

/**
 * Class PodcastCreateMethod
 * @package Lib\ApiMethods
 */
final class PodcastCreateMethod
{
    public const ACTION = 'podcast_create';

    /**
     * podcast_create
     * MINIMUM_API_VERSION=420000
     * Create a public url that can be used by anyone to stream media.
     * Takes the file id with optional description and expires parameters.
     *
     * @param array $input
     * url     = (string) rss url for podcast
     * catalog = (string) podcast catalog
     * @return boolean
     */
    public static function podcast_create(array $input): bool
    {
        if (!AmpConfig::get('podcast')) {
            Api::error(T_('Enable: podcast'), '4703', self::ACTION, 'system', $input['api_format']);

            return false;
        }
        if (!Api::check_access('interface', 75, User::get_from_username(Session::username($input['auth']))->id, self::ACTION, $input['api_format'])) {
            return false;
        }
        if (!Api::check_parameter($input, array('url', 'catalog'), self::ACTION)) {
            return false;
        }
        $data            = array();
        $data['feed']    = urldecode($input['url']);
        $data['catalog'] = $input['catalog'];
        $podcast         = Podcast::create($data, true);

        if (!$podcast) {
            Api::error(T_('Bad Request'), '4710', self::ACTION, 'system', $input['api_format']);

            return false;
        }

        Catalog::count_table('podcast');
        $user = User::get_from_username(Session::username($input['auth']));
        ob_end_clean();
        switch ($input['api_format']) {
            case 'json':
                echo Json_Data::podcasts(array($podcast), $user->id, false, false);
                break;
            default:
                echo XML_Data::podcasts(array($podcast), $user->id);
        }

        return true;
    }
}
