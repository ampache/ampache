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

namespace Ampache\Module\Api\Method;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Module\Api\Authentication\GatekeeperInterface;
use Ampache\Module\Api\Exception\ErrorCodeEnum;
use Ampache\Module\Api\Method\Exception\AccessDeniedException;
use Ampache\Module\Api\Method\Exception\RequestParamMissingException;
use Ampache\Module\Api\Method\Exception\ResultEmptyException;
use Ampache\Module\Api\Output\ApiOutputInterface;
use Ampache\Module\Authorization\AccessFunctionEnum;
use Ampache\Module\Authorization\Check\FunctionCheckerInterface;
use Ampache\Module\Catalog\Catalog;
use Ampache\Module\Database\Query\Search;
use Ampache\Module\Share\ShareCreatorInterface;
use Ampache\Module\User\PasswordGeneratorInterface;
use Ampache\Module\Util\ObjectTypeToClassNameMapper;
use Ampache\Repository\Model\Album;
use Ampache\Repository\Model\Artist;
use Ampache\Repository\Model\LibraryItemEnum;
use Ampache\Repository\Model\Live_Stream;
use Ampache\Repository\Model\Playlist;
use Ampache\Repository\Model\Podcast;
use Ampache\Repository\Model\Podcast_Episode;
use Ampache\Repository\Model\Share;
use Ampache\Repository\Model\Song;
use Ampache\Repository\Model\User;
use Ampache\Repository\Model\Video;
use Psr\Http\Message\ResponseInterface;

/**
 * Creates a share for an object
 *
 * The two live api versions only differ in which value they report back in the type error: version 6
 * reports the resolved type (a zero-id playlist becomes `search`), version 8 reports the raw input.
 * The version classes supply that choice; everything else is shared.
 */
abstract class AbstractShareCreateMethod implements MethodInterface
{
    public const string ACTION = 'share_create';

    public const string REST_ACTION = 'shares_create';

    private ConfigContainerInterface $configContainer;
    private FunctionCheckerInterface $functionChecker;
    private PasswordGeneratorInterface $passwordGenerator;
    private ShareCreatorInterface $shareCreator;

    public function __construct(
        ConfigContainerInterface $configContainer,
        FunctionCheckerInterface $functionChecker,
        PasswordGeneratorInterface $passwordGenerator,
        ShareCreatorInterface $shareCreator,
    ) {
        $this->configContainer   = $configContainer;
        $this->functionChecker   = $functionChecker;
        $this->passwordGenerator = $passwordGenerator;
        $this->shareCreator      = $shareCreator;
    }

    /**
     * MINIMUM_API_VERSION=420000
     *
     * Create a public url that can be used by anyone to stream media.
     *
     * filter      = (string) object_id
     * type        = (string) object_type
     * description = (string) description (will be filled for you if empty) //optional
     * expires     = (integer) days to keep active //optional
     *
     * @param array{
     *     filter?: string,
     *     type?: string,
     *     description?: string,
     *     expires?: int,
     *     api_format: string,
     *     auth: string,
     * } $input
     * @throws AccessDeniedException|RequestParamMissingException|ResultEmptyException
     */
    public function handle(
        GatekeeperInterface $gatekeeper,
        ResponseInterface $response,
        ApiOutputInterface $output,
        array $input,
        User $user,
        int $apiVersion,
    ): ResponseInterface {
        if (!$this->configContainer->get(ConfigurationKeyEnum::SHARE)) {
            throw new AccessDeniedException(
                'Enable: share'
            );
        }

        foreach (['type', 'filter'] as $parameter) {
            if (!array_key_exists($parameter, $input)) {
                throw new RequestParamMissingException(
                    sprintf('Bad Request: %s', $parameter)
                );
            }
        }

        $objectId    = (string) $input['filter'];
        $objectType  = (string) $input['type'];
        $description = $input['description'] ?? null;
        $expireDays  = (isset($input['expires']))
            ? filter_var($input['expires'], FILTER_SANITIZE_NUMBER_INT)
            : $this->configContainer->get(ConfigurationKeyEnum::SHARE_EXPIRE);

        // searches are playlists but not in the database
        if (($objectType === 'playlist' || $objectType === 'smartlist') && ((int) $objectId) === 0) {
            $objectId   = str_replace('smart_', '', $objectId);
            $objectType = 'search';
        }

        // confirm the correct data
        $objectTypeEnum = LibraryItemEnum::tryFrom(strtolower($objectType));
        if ($objectTypeEnum === null || !in_array($objectTypeEnum, Share::VALID_TYPES, true)) {
            return $this->writeTypeError($response, $output, $apiVersion, $input, $objectType);
        }

        $className = ObjectTypeToClassNameMapper::map($objectType);
        if (!$className || !$objectId) {
            debug_event(static::class, 'ERROR ' . $objectType . ' className: ' . $className . ' object_id: ' . $objectId, 5);

            return $this->writeTypeError($response, $output, $apiVersion, $input, $objectType);
        }

        /** @var Album|Artist|Live_Stream|Playlist|Podcast|Podcast_Episode|Search|Song|Video $item */
        $item = new $className((int) $objectId);
        if ($item->isNew()) {
            throw new ResultEmptyException(
                $objectId
            );
        }

        $share = $this->shareCreator->create(
            $user,
            LibraryItemEnum::from($objectType),
            (int) $objectId,
            true,
            $this->functionChecker->check(AccessFunctionEnum::FUNCTION_DOWNLOAD),
            (int) $expireDays,
            $this->passwordGenerator->generate_token(),
            0,
            $description
        );

        if ($share === null) {
            $response->getBody()->write(
                $output->error(
                    $apiVersion,
                    ErrorCodeEnum::BAD_REQUEST,
                    'Bad Request',
                    static::ACTION,
                    'system'
                )
            );

            return $response;
        }

        Catalog::count_table('share');

        $response->getBody()->write(
            $output->shares($apiVersion, [$share], $user, false)
        );

        return $response;
    }

    /**
     * Which type value the version reports back in the error: the raw input or the resolved type
     *
     * @param array<string, mixed> $input
     */
    abstract protected function reportedType(array $input, string $objectType): string;

    /**
     * @param array<string, mixed> $input
     */
    private function writeTypeError(
        ResponseInterface $response,
        ApiOutputInterface $output,
        int $apiVersion,
        array $input,
        string $objectType,
    ): ResponseInterface {
        $response->getBody()->write(
            $output->error(
                $apiVersion,
                ErrorCodeEnum::BAD_REQUEST,
                sprintf('Bad Request: %s', $this->reportedType($input, $objectType)),
                static::ACTION,
                'type'
            )
        );

        return $response;
    }
}
