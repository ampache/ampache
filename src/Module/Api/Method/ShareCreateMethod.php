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

namespace Ampache\Module\Api\Method;

use Ampache\Config\AmpConfig;
use Ampache\Module\Api\Exception\ErrorCodeEnum;
use Ampache\Module\Share\ShareCreatorInterface;
use Ampache\Repository\Model\Album;
use Ampache\Repository\Model\Artist;
use Ampache\Repository\Model\Catalog;
use Ampache\Repository\Model\Live_Stream;
use Ampache\Repository\Model\Playlist;
use Ampache\Repository\Model\Podcast;
use Ampache\Repository\Model\Podcast_Episode;
use Ampache\Repository\Model\Search;
use Ampache\Module\Api\Api;
use Ampache\Module\Api\Json_Data;
use Ampache\Module\Api\Xml_Data;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\Check\FunctionCheckerInterface;
use Ampache\Module\User\PasswordGeneratorInterface;
use Ampache\Module\Util\ObjectTypeToClassNameMapper;
use Ampache\Repository\Model\Song;
use Ampache\Repository\Model\User;
use Ampache\Repository\Model\Video;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * Class ShareCreateMethod
 * @package Lib\ApiMethods
 */
final class ShareCreateMethod
{
    public const ACTION = 'share_create';

    /**
     * share_create
     * MINIMUM_API_VERSION=420000
     * Create a public url that can be used by anyone to stream media.
     * Takes the file id with optional description and expires parameters.
     *
     * @param array $input
     *  filter      = (string) object_id
     *  type        = (string) object_type ('album', 'artist', 'playlist', 'podcast', 'podcast_episode', 'song', 'video')
     *  description = (string) description (will be filled for you if empty) //optional
     *  expires     = (integer) days to keep active //optional
     * @param User $user
     * @return bool
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public static function share_create(array $input, User $user): bool
    {
        if (!AmpConfig::get('share')) {
            Api::error('Enable: share', ErrorCodeEnum::ACCESS_DENIED, self::ACTION, 'system', $input['api_format']);

            return false;
        }
        if (!Api::check_parameter($input, ['type', 'filter'], self::ACTION)) {
            return false;
        }

        $object_id   = $input['filter'];
        $object_type = $input['type'];
        $description = $input['description'] ?? null;
        $expire_days = (isset($input['expires'])) ? filter_var($input['expires'], FILTER_SANITIZE_NUMBER_INT) : AmpConfig::get('share_expire', 7);
        // confirm the correct data
        if (!in_array(strtolower($object_type), ['album', 'artist', 'playlist', 'podcast', 'podcast_episode', 'song', 'video'])) {
            /* HINT: Requested object string/id/type ("album", "myusername", "some song title", 1298376) */
            Api::error(sprintf('Bad Request: %s', $object_type), ErrorCodeEnum::BAD_REQUEST, self::ACTION, 'type', $input['api_format']);

            return false;
        }
        // searches are playlists but not in the database
        if ($object_type === 'playlist' && ((int)$object_id) === 0) {
            $object_id   = str_replace('smart_', '', (string) $object_id);
            $object_type = 'search';
        }

        $className = ObjectTypeToClassNameMapper::map($object_type);

        $results = [];
        if (!$className || !$object_id) {
            debug_event(self::class, 'ERROR ' . $object_type . ' className: ' . $className . ' object_id: ' . $object_id, 5);
            /* HINT: Requested object string/id/type ("album", "myusername", "some song title", 1298376) */
            Api::error(sprintf('Bad Request: %s', $object_type), ErrorCodeEnum::BAD_REQUEST, self::ACTION, 'type', $input['api_format']);
        } else {
            /** @var Album|Artist|Live_stream|Playlist|Podcast|Podcast_episode|Search|Song|Video $item */
            $item = new $className((int)$object_id);
            if ($item->isNew()) {
                /* HINT: Requested object string/id/type ("album", "myusername", "some song title", 1298376) */
                Api::error(sprintf('Not Found: %s', $object_id), ErrorCodeEnum::NOT_FOUND, self::ACTION, 'filter', $input['api_format']);

                return false;
            }
            // @todo Replace by constructor injection
            global $dic;
            $functionChecker   = $dic->get(FunctionCheckerInterface::class);
            $passwordGenerator = $dic->get(PasswordGeneratorInterface::class);
            $shareCreator      = $dic->get(ShareCreatorInterface::class);

            $share = $shareCreator->create(
                $user,
                $object_type,
                $object_id,
                true,
                $functionChecker->check(AccessLevelEnum::FUNCTION_DOWNLOAD),
                $expire_days,
                $passwordGenerator->generate_token(),
                0,
                $description
            );
            if ($share !== null) {
                $results[] = $share;
            }
        }
        if (empty($results)) {
            Api::error('Bad Request', ErrorCodeEnum::BAD_REQUEST, self::ACTION, 'system', $input['api_format']);

            return false;
        }

        Catalog::count_table('share');
        ob_end_clean();
        switch ($input['api_format']) {
            case 'json':
                echo Json_Data::shares($results, false);
                break;
            default:
                echo Xml_Data::shares($results, $user);
        }

        return true;
    }
}
