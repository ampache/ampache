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

namespace Ampache\Module\Api\Method\Api5;

use Ampache\Config\AmpConfig;
use Ampache\Module\Api\Exception\ErrorCodeEnum;
use Ampache\Repository\Model\Podcast;
use Ampache\Module\Api\Api5;
use Ampache\Module\Api\Json5_Data;
use Ampache\Module\Api\Xml5_Data;
use Ampache\Repository\Model\User;
use Ampache\Repository\PodcastRepositoryInterface;

/**
 * Class Podcast5Method
 */
final class Podcast5Method
{
    public const ACTION = 'podcast';

    /**
     * podcast
     * MINIMUM_API_VERSION=420000
     *
     * Get the podcast from it's id.
     *
     * filter  = (integer) Podcast ID number
     * include = (string) 'episodes' (include episodes in the response) //optional
     */
    public static function podcast(array $input, User $user): bool
    {
        if (!AmpConfig::get('podcast')) {
            Api5::error(T_('Enable: podcast'), ErrorCodeEnum::ACCESS_DENIED, self::ACTION, 'system', $input['api_format']);

            return false;
        }
        if (!Api5::check_parameter($input, array('filter'), self::ACTION)) {
            return false;
        }
        $object_id = (int) $input['filter'];
        $include   = $input['include'] ?? '';
        $podcast   = self::getPodcastRepository()->findById($object_id);

        if ($podcast === null) {
            /* HINT: Requested object string/id/type ("album", "myusername", "some song title", 1298376) */
            Api5::error(sprintf(T_('Not Found: %s'), $object_id), ErrorCodeEnum::NOT_FOUND, self::ACTION, 'filter', $input['api_format']);

            return false;
        }

        ob_end_clean();
        $episodes = ($include == 'episodes' || (int)$include == 1);
        switch ($input['api_format']) {
            case 'json':
                echo Json5_Data::podcasts(array($object_id), $user, $episodes, false);
                break;
            default:
                echo Xml5_Data::podcasts(array($object_id), $user, $episodes);
        }

        return true;
    }

    /**
     * @deprecated Inject by constructor
     */
    private static function getPodcastRepository(): PodcastRepositoryInterface
    {
        global $dic;

        return $dic->get(PodcastRepositoryInterface::class);
    }
}
