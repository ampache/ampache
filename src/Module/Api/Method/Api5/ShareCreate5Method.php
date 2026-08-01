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

namespace Ampache\Module\Api\Method\Api5;

use Ampache\Config\AmpConfig;
use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Module\Api\Authentication\GatekeeperInterface;
use Ampache\Module\Api\Exception\ErrorCodeEnum;
use Ampache\Module\Api\Method\Exception\AccessDeniedException;
use Ampache\Module\Api\Method\Exception\RequestParamMissingException;
use Ampache\Module\Api\Method\Exception\ResultEmptyException;
use Ampache\Module\Api\Method\MethodInterface;
use Ampache\Module\Api\Output\ApiOutputInterface;
use Ampache\Module\Authorization\AccessFunctionEnum;
use Ampache\Module\Authorization\Check\FunctionCheckerInterface;
use Ampache\Module\Catalog\Catalog;
use Ampache\Module\Share\ShareCreatorInterface;
use Ampache\Module\User\PasswordGeneratorInterface;
use Ampache\Module\Util\ObjectTypeToClassNameMapper;
use Ampache\Repository\Model\Album;
use Ampache\Repository\Model\Artist;
use Ampache\Repository\Model\LibraryItemEnum;
use Ampache\Repository\Model\Song;
use Ampache\Repository\Model\User;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;

/**
 * Creates a public url that can be used by anyone to stream media.
 *
 * Version 5 only shares songs, albums and artists and knows nothing about the wider object list the
 * later versions accept, so it keeps a method of its own.
 */
final class ShareCreate5Method implements MethodInterface
{
    public const string ACTION = 'share_create';

    public function __construct(
        private ConfigContainerInterface $configContainer,
        private FunctionCheckerInterface $functionChecker,
        private PasswordGeneratorInterface $passwordGenerator,
        private ShareCreatorInterface $shareCreator,
        private StreamFactoryInterface $streamFactory,
    ) {}

    /**
     * share_create
     * MINIMUM_API_VERSION=420000
     * Create a public url that can be used by anyone to stream media.
     * Takes the file id with optional description and expires parameters.
     *
     * filter = (string) object_id
     * type = (string) object_type ('song', 'album', 'artist')
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
     * @param 5 $apiVersion
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

        $object_id   = $input['filter'];
        $object_type = $input['type'];
        $description = $input['description'] ?? null;
        $expire_days = (isset($input['expires']))
            ? filter_var($input['expires'], FILTER_SANITIZE_NUMBER_INT)
            : AmpConfig::get('share_expire', 7);

        // the type is matched case insensitively, so resolve the object from the normalized name
        $item_type = strtolower($object_type);

        // confirm the correct data
        if (!in_array($item_type, ['song', 'album', 'artist'])) {
            return $response->withBody(
                $this->streamFactory->createStream(
                    $this->writeTypeError($output, $apiVersion, $object_type)
                )
            );
        }

        $className = ObjectTypeToClassNameMapper::map($item_type);
        if (!$className || !$object_id) {
            return $response->withBody(
                $this->streamFactory->createStream(
                    $this->writeTypeError($output, $apiVersion, $object_type)
                )
            );
        }

        /** @var Song|Album|Artist $item */
        $item = new $className((int) $object_id);
        if ($item->isNew()) {
            throw new ResultEmptyException(
                (string) $object_id
            );
        }

        $share = $this->shareCreator->create(
            $user,
            LibraryItemEnum::from($item_type),
            (int) $object_id,
            true,
            $this->functionChecker->check(AccessFunctionEnum::FUNCTION_DOWNLOAD),
            (int) $expire_days,
            $this->passwordGenerator->generate_token(),
            0,
            $description
        );
        if ($share === null) {
            return $response->withBody(
                $this->streamFactory->createStream(
                    $output->error(
                        $apiVersion,
                        ErrorCodeEnum::BAD_REQUEST,
                        'Bad Request',
                        self::ACTION,
                        'system'
                    )
                )
            );
        }

        Catalog::count_table('share');

        return $response->withBody(
            $this->streamFactory->createStream(
                $output->shares($apiVersion, [$share], $user, false)
            )
        );
    }

    private function writeTypeError(
        ApiOutputInterface $output,
        int $apiVersion,
        string $objectType,
    ): string {
        return $output->error(
            $apiVersion,
            ErrorCodeEnum::BAD_REQUEST,
            sprintf('Bad Request: %s', $objectType),
            self::ACTION,
            'type'
        );
    }
}
