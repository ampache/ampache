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

namespace Ampache\Module\Api\Method\Api4;

use Ampache\Config\AmpConfig;
use Ampache\Module\Api\Api4;
use Ampache\Module\Api\Json4_Data;
use Ampache\Module\Api\Xml4_Data;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Module\Podcast\Exception\PodcastCreationException;
use Ampache\Module\Podcast\PodcastCreatorInterface;
use Ampache\Repository\Model\Catalog;
use Ampache\Repository\Model\User;

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
     * url     = (string) rss url for podcast
     * catalog = (string) podcast catalog
     *
     * @param array{
     *     url: string,
     *     catalog: string,
     *     api_format: string,
     *     auth: string,
     * } $input
     * @param User $user
     * @return bool
     */
    public static function podcast_create(array $input, User $user): bool
    {
        if (!AmpConfig::get('podcast')) {
            Api4::message('error', T_('Access Denied: podcast features are not enabled.'), '400', $input['api_format']);

            return false;
        }
        if (!Api4::check_access(AccessTypeEnum::INTERFACE, AccessLevelEnum::MANAGER, $user->id, 'update_podcast', $input['api_format'])) {
            return false;
        }
        if (!Api4::check_parameter($input, ['url', 'catalog'], self::ACTION)) {
            return false;
        }

        $catalog = Catalog::create_from_id((int)$input['catalog']);

        if ($catalog === null) {
            Api4::message('error', T_('Catalog not found'), '401', $input['api_format']);

            return false;
        }

        try {
            $podcast = self::getPodcastCreator()->create(
                $input['url'],
                $catalog
            );
        } catch (PodcastCreationException) {
            Api4::message('error', T_('Bad Request'), '401', $input['api_format']);

            return false;
        }

        Catalog::count_table('podcast');
        ob_end_clean();
        switch ($input['api_format']) {
            case 'json':
                echo Json4_Data::podcasts([$podcast->getId()], $user, $input['auth']);
                break;
            default:
                echo Xml4_Data::podcasts([$podcast->getId()], $user, $input['auth']);
        }

        return true;
    } // podcast_create

    /**
     * @deprecated inject dependency
     */
    private static function getPodcastCreator(): PodcastCreatorInterface
    {
        global $dic;

        return $dic->get(PodcastCreatorInterface::class);
    }
}
