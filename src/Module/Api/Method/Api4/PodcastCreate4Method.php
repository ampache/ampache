<?php

/*
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 *  LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright 2001 - 2022 Ampache.org
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

namespace Ampache\Module\Api\Method\Api4;

use Ampache\Config\AmpConfig;
use Ampache\Repository\Model\Catalog;
use Ampache\Repository\Model\Podcast;
use Ampache\Repository\Model\User;
use Ampache\Module\Api\Api4;
use Ampache\Module\Api\Json4_Data;
use Ampache\Module\Api\Xml4_Data;

/**
 * Class PodcastCreate4Method
 */
final class PodcastCreate4Method
{
    public const ACTION = 'podcast_create';

    /**
     * podcast_create
     * MINIMUM_API_VERSION=420000
     * Create a public url that can be used by anyone to stream media.
     * Takes the file id with optional description and expires parameters.
     *
     * @param array $input
     * @param User $user
     * url     = (string) rss url for podcast
     * catalog = (string) podcast catalog
     * @return boolean
     */
    public static function podcast_create(array $input, User $user): bool
    {
        if (!AmpConfig::get('podcast')) {
            Api4::message('error', T_('Access Denied: podcast features are not enabled.'), '400', $input['api_format']);

            return false;
        }
        if (!Api4::check_access('interface', 75, $user->id, 'update_podcast', $input['api_format'])) {
            return false;
        }
        if (!Api4::check_parameter($input, array('url', 'catalog'), self::ACTION)) {
            return false;
        }
        $data            = array();
        $data['feed']    = $input['url'];
        $data['catalog'] = $input['catalog'];
        $podcast         = Podcast::create($data, true);
        if ($podcast) {
            Catalog::count_table('podcast');
            ob_end_clean();
            switch ($input['api_format']) {
                case 'json':
                    echo Json4_Data::podcasts(array($podcast), $user);
                    break;
                default:
                    echo Xml4_Data::podcasts(array($podcast), $user);
            }
        } else {
            Api4::message('error', T_('Failed: podcast was not created.'), '401', $input['api_format']);
        }

        return true;
    } // podcast_create
}
