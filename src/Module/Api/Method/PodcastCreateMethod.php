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

use Ampache\Config\AmpConfig;
use Ampache\Module\Api\Api;
use Ampache\Module\Api\Exception\ErrorCodeEnum;
use Ampache\Module\Api\Json_Data;
use Ampache\Module\Api\Xml_Data;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Module\Podcast\Exception\PodcastCreationException;
use Ampache\Module\Podcast\PodcastCreatorInterface;
use Ampache\Repository\Model\Catalog;
use Ampache\Repository\Model\User;

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
     * url     = (string) rss url for podcast
     * catalog = (string) podcast catalog
     */
    public static function podcast_create(array $input, User $user): bool
    {
        if (!AmpConfig::get('podcast')) {
            Api::error('Enable: podcast', ErrorCodeEnum::ACCESS_DENIED, self::ACTION, 'system', $input['api_format']);

            return false;
        }
        if (!Api::check_access(AccessTypeEnum::INTERFACE, AccessLevelEnum::MANAGER, $user->id, self::ACTION, $input['api_format'])) {
            return false;
        }
        if (!Api::check_parameter($input, array('url', 'catalog'), self::ACTION)) {
            return false;
        }

        $catalog = Catalog::create_from_id((int) ($input['catalog'] ?? 0));

        if ($catalog === null) {
            Api::error('Bad Request', ErrorCodeEnum::BAD_REQUEST, self::ACTION, 'system', $input['api_format']);

            return false;
        }

        try {
            $podcast = self::getPodcastCreator()->create(
                urldecode($input['url']),
                $catalog
            );
        } catch (PodcastCreationException $e) {
            Api::error('Bad Request', '4710', self::ACTION, 'system', $input['api_format']);

            return false;
        }

        Catalog::count_table('podcast');
        ob_end_clean();
        switch ($input['api_format']) {
            case 'json':
                echo Json_Data::podcasts(array($podcast->getId()), $user, false, true, false);
                break;
            default:
                echo Xml_Data::podcasts(array($podcast->getId()), $user);
        }

        return true;
    }

    /**
     * @deprecated inject dependency
     */
    private static function getPodcastCreator(): PodcastCreatorInterface
    {
        global $dic;

        return $dic->get(PodcastCreatorInterface::class);
    }
}
