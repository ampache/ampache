<?php

declare(strict_types=1);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2026
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

namespace Ampache\Module\Api\Method\Api6;

use Ampache\Config\AmpConfig;
use Ampache\Module\Api\Api6;
use Ampache\Module\Api\Exception\ErrorCodeEnum;
use Ampache\Module\Api\Json6_Data;
use Ampache\Module\Api\Xml6_Data;
use Ampache\Module\Authorization\AccessFunctionEnum;
use Ampache\Module\Authorization\Check\FunctionCheckerInterface;
use Ampache\Module\Share\ShareCreatorInterface;
use Ampache\Module\User\PasswordGeneratorInterface;
use Ampache\Module\Util\ObjectTypeToClassNameMapper;
use Ampache\Repository\Model\Album;
use Ampache\Repository\Model\Artist;
use Ampache\Repository\Model\Catalog;
use Ampache\Repository\Model\LibraryItemEnum;
use Ampache\Repository\Model\Live_Stream;
use Ampache\Repository\Model\Playlist;
use Ampache\Repository\Model\Podcast;
use Ampache\Repository\Model\Podcast_Episode;
use Ampache\Repository\Model\Search;
use Ampache\Repository\Model\Song;
use Ampache\Repository\Model\User;
use Ampache\Repository\Model\Video;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * Class ShareCreate6Method
 * @package Lib\Api6Methods
 */
final class ShareCreate6Method
{
    public const ACTION = 'share_create';

    public const REST_ACTION = 'shares_create';

    /**
     * share_create
     * MINIMUM_API_VERSION=420000
     * Create a public url that can be used by anyone to stream media.
     * Takes the file id with optional description and expires parameters.
     *
     * filter = (string) object_id
     * type = (string) object_type ('album', 'artist', 'playlist', 'podcast', 'podcast_episode', 'smartlist', 'song', 'video')
     * description = (string) description (will be filled for you if empty) //optional
     * expires = (integer) days to keep active //optional
     *
     * @param array{
     *     filter: string,
     *     type: string,
     *     description?: string,
     *     expires?: int,
     *     api_format: string,
     *     auth: string,
     * } $input
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public static function share_create(array $input, User $user): bool
    {
        if (!AmpConfig::get('share')) {
            Api6::error(ErrorCodeEnum::ACCESS_DENIED, 'Enable: share', self::ACTION, 'system', $input['api_format']);

            return false;
        }
        if (!Api6::check_parameter($input, ['type', 'filter'], self::ACTION)) {
            return false;
        }

        $object_id   = (string) $input['filter'];
        $object_type = strtolower((string) $input['type']);
        $description = $input['description'] ?? null;
        $expire_days = (isset($input['expires'])) ? filter_var($input['expires'], FILTER_SANITIZE_NUMBER_INT) : AmpConfig::get('share_expire', 7);
        // confirm the correct data
        if (!in_array($object_type, ['album', 'artist', 'playlist', 'podcast', 'podcast_episode', 'smartlist', 'song', 'video'])) {
            /* HINT: Requested object string/id/type ("album", "myusername", "some song title", 1298376) */
            Api6::error(ErrorCodeEnum::BAD_REQUEST, sprintf('Bad Request: %s', $object_type), self::ACTION, 'type', $input['api_format']);

            return false;
        }
        // searches are playlists but not in the database. 'smartlist' is always a search
        if ($object_type === 'smartlist' || ($object_type === 'playlist' && ((int) $object_id) === 0)) {
            $object_id   = str_replace('smart_', '', $object_id);
            $object_type = 'search';
        }

        $className = ObjectTypeToClassNameMapper::map($object_type);
        if (!$className || !$object_id) {
            debug_event(self::class, 'ERROR ' . $object_type . ' className: ' . $className . ' object_id: ' . $object_id, 5);
            /* HINT: Requested object string/id/type ("album", "myusername", "some song title", 1298376) */
            Api6::error(ErrorCodeEnum::BAD_REQUEST, sprintf('Bad Request: %s', $object_type), self::ACTION, 'type', $input['api_format']);

            return false;
        }

        /** @var Album|Artist|Live_stream|Playlist|Podcast|Podcast_episode|Search|Song|Video $item */
        $item = new $className((int) $object_id);
        if ($item->isNew()) {
            /* HINT: Requested object string/id/type ("album", "myusername", "some song title", 1298376) */
            Api6::error(ErrorCodeEnum::NOT_FOUND, sprintf('Not Found: %s', $object_id), self::ACTION, 'filter', $input['api_format']);

            return false;
        }

        // @todo Replace by constructor injection
        global $dic;
        $functionChecker   = $dic->get(FunctionCheckerInterface::class);
        $passwordGenerator = $dic->get(PasswordGeneratorInterface::class);
        $shareCreator      = $dic->get(ShareCreatorInterface::class);

        $share = $shareCreator->create(
            $user,
            LibraryItemEnum::from($object_type),
            (int) $object_id,
            true,
            $functionChecker->check(AccessFunctionEnum::FUNCTION_DOWNLOAD),
            (int) $expire_days,
            $passwordGenerator->generate_token(),
            0,
            $description
        );
        if ($share === null) {
            Api6::error(ErrorCodeEnum::BAD_REQUEST, 'Bad Request', self::ACTION, 'system', $input['api_format']);

            return false;
        }

        $results = [$share];

        Catalog::count_table('share');
        ob_end_clean();
        switch ($input['api_format']) {
            case 'json':
                echo Json6_Data::shares($results, $user, false);
                break;
            default:
                echo Xml6_Data::shares($results, $user);
        }

        return true;
    }

    /**
     * @param array{
     *     filter: string,
     *     type: string,
     *     description?: string,
     *     expires?: int,
     *     api_format: string,
     *     auth: string,
     * } $input
     */
    public static function shares_create(array $input, User $user): bool
    {
        return self::share_create($input, $user);
    }
}
