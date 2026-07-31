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

use Ampache\Module\Api\Authentication\GatekeeperInterface;
use Ampache\Module\Api\Exception\ErrorCodeEnum;
use Ampache\Module\Api\Method\Exception\AccessFailedException;
use Ampache\Module\Api\Method\Exception\RequestParamMissingException;
use Ampache\Module\Api\Method\MethodInterface;
use Ampache\Module\Api\Output\ApiOutputInterface;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Module\Authorization\Check\PrivilegeCheckerInterface;
use Ampache\Repository\Model\Album;
use Ampache\Repository\Model\Catalog;
use Ampache\Repository\Model\User;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;

/**
 * Kicks off a catalog update or clean for the selected catalog.
 *
 * Version 5 reads the catalog id from `catalog` only, so it keeps a method of its own.
 */
final class CatalogAction5Method implements MethodInterface
{
    public const string ACTION = 'catalog_action';

    public function __construct(
        private PrivilegeCheckerInterface $privilegeChecker,
        private StreamFactoryInterface $streamFactory,
    ) {}

    /**
     * catalog_action
     * MINIMUM_API_VERSION=400001
     * CHANGED_IN_API_VERSION=420000
     *
     * Kick off a catalog update or clean for the selected catalog
     * Added 'verify_catalog', 'gather_art'
     *
     * task = (string) 'add_to_catalog', 'clean_catalog', 'verify_catalog', 'gather_art'
     * catalog = (integer) $catalog_id
     *
     * @param array{
     *     task: string,
     *     catalog: int,
     *     api_format: string,
     *     auth: string,
     * } $input
     * @param 5 $apiVersion
     * @throws AccessFailedException|RequestParamMissingException
     */
    public function handle(
        GatekeeperInterface $gatekeeper,
        ResponseInterface $response,
        ApiOutputInterface $output,
        array $input,
        User $user,
        int $apiVersion,
    ): ResponseInterface {
        foreach (['catalog', 'task'] as $parameter) {
            if (!array_key_exists($parameter, $input)) {
                throw new RequestParamMissingException(
                    sprintf('Bad Request: %s', $parameter)
                );
            }
        }

        if (
            !$this->privilegeChecker->check(
                AccessTypeEnum::INTERFACE,
                AccessLevelEnum::MANAGER,
                $user->id
            )
        ) {
            throw new AccessFailedException(
                sprintf('Require: %s', AccessLevelEnum::MANAGER->value)
            );
        }

        $task = (string) $input['task'];

        // confirm the correct data
        if (!in_array($task, ['add_to_catalog', 'clean_catalog', 'verify_catalog', 'gather_art'])) {
            return $response->withBody(
                $this->streamFactory->createStream(
                    $output->error(
                        $apiVersion,
                        ErrorCodeEnum::BAD_REQUEST,
                        sprintf('Bad Request: %s', $task),
                        self::ACTION,
                        'task'
                    )
                )
            );
        }

        $catalog = Catalog::create_from_id((int) $input['catalog']);
        if ($catalog === null) {
            return $response->withBody(
                $this->streamFactory->createStream(
                    $output->error(
                        $apiVersion,
                        ErrorCodeEnum::NOT_FOUND,
                        'Not Found',
                        self::ACTION,
                        'catalog'
                    )
                )
            );
        }

        switch ($task) {
            case 'clean_catalog':
                $catalog->clean_catalog_proc();
                break;
            case 'verify_catalog':
                $catalog->verify_catalog_proc();
                break;
            case 'gather_art':
                $catalog->gather_art();
                break;
            case 'add_to_catalog':
                $options = [
                    'gather_art' => true,
                    'parse_playlist' => false,
                ];
                $catalog->add_to_catalog($options);
                break;
        }

        // clean up after the action
        $catalog_media_type = $catalog->gather_types;
        if ($catalog_media_type == 'music') {
            Catalog::clean_empty_albums();
            Album::update_album_artist();
        }

        Catalog::update_catalog_map($catalog_media_type);
        Catalog::update_counts();

        return $response->withBody(
            $this->streamFactory->createStream(
                $output->success($apiVersion, 'successfully started: ' . $task)
            )
        );
    }
}
