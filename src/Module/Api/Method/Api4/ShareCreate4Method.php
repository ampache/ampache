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

namespace Ampache\Module\Api\Method\Api4;

use Ampache\Config\AmpConfig;
use Ampache\Module\Api\Api4;
use Ampache\Module\Api\Authentication\GatekeeperInterface;
use Ampache\Module\Api\Method\MethodInterface;
use Ampache\Module\Api\Output\ApiOutputInterface;
use Ampache\Module\Authorization\AccessFunctionEnum;
use Ampache\Module\Authorization\Check\FunctionCheckerInterface;
use Ampache\Module\Catalog\Catalog;
use Ampache\Module\Catalog\CountableTableEnum;
use Ampache\Module\Share\ShareCreatorInterface;
use Ampache\Module\User\PasswordGeneratorInterface;
use Ampache\Module\Util\InterfaceImplementationChecker;
use Ampache\Module\Util\ObjectTypeToClassNameMapper;
use Ampache\Repository\Model\library_item;
use Ampache\Repository\Model\LibraryItemEnum;
use Ampache\Repository\Model\User;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;

final class ShareCreate4Method implements MethodInterface
{
    public const string ACTION = 'share_create';

    public function __construct(
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
     * type = (string) object_type
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
     * @param 4 $apiVersion
     */
    public function handle(
        GatekeeperInterface $gatekeeper,
        ResponseInterface $response,
        ApiOutputInterface $output,
        array $input,
        User $user,
        int $apiVersion,
    ): ResponseInterface {
        if (!AmpConfig::get('share')) {
            Api4::message('error', 'Access Denied: sharing features are not enabled.', '400', $input['api_format']);

            return $response;
        }
        if (!Api4::check_parameter($input, ['type', 'filter'], self::ACTION)) {
            return $response;
        }

        $object_id   = $input['filter'];
        $object_type = $input['type'];
        $description = $input['description'] ?? null;
        $expire_days = (isset($input['expires'])) ? filter_var($input['expires'], FILTER_SANITIZE_NUMBER_INT) : AmpConfig::get('share_expire', 7);
        // confirm the correct data
        if (!in_array($object_type, ['song', 'album', 'artist'])) {
            Api4::message('error', 'Wrong object type ' . $object_type, '401', $input['api_format']);

            return $response;
        }
        $results = [];
        if (!InterfaceImplementationChecker::is_library_item($object_type) || !$object_id) {
            Api4::message('error', 'Wrong library item type', '401', $input['api_format']);
        } else {
            $className = ObjectTypeToClassNameMapper::map($object_type);
            /** @var library_item $item */
            $item = new $className($object_id);
            if ($item->getId() === 0) {
                Api4::message('error', 'Library item not found', '404', $input['api_format']);

                return $response;
            }

            $shareId = $this->shareCreator->create(
                $user,
                LibraryItemEnum::from($object_type),
                (int) $object_id,
                true,
                $this->functionChecker->check(AccessFunctionEnum::FUNCTION_DOWNLOAD),
                (int) $expire_days,
                $this->passwordGenerator->generate_token(),
                0,
                $description
            );

            // a failed create has no id to render
            if ($shareId !== null) {
                $results[] = $shareId;
            }
        }
        Catalog::count_table(CountableTableEnum::SHARE);
        ob_end_clean();

        return $response->withBody(
            $this->streamFactory->createStream(
                $output->shares($apiVersion, $results, $user)
            )
        );
    }
}
