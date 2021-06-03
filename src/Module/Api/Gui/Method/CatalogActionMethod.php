<?php
/*
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright 2001 - 2020 Ampache.org
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

declare(strict_types=1);

namespace Ampache\Module\Api\Gui\Method;

use Ampache\Module\Api\Gui\Authentication\GatekeeperInterface;
use Ampache\Module\Api\Gui\Method\Exception\AccessDeniedException;
use Ampache\Module\Api\Gui\Method\Exception\RequestParamMissingException;
use Ampache\Module\Api\Gui\Method\Exception\ResultEmptyException;
use Ampache\Module\Api\Gui\Output\ApiOutputInterface;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Catalog\Loader\CatalogLoaderInterface;
use Ampache\Module\Catalog\Loader\Exception\CatalogNotFoundException;
use Ampache\Module\Catalog\Process\CatalogProcessTypeMapperInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;

final class CatalogActionMethod implements MethodInterface
{
    public const ACTION = 'catalog_action';

    private StreamFactoryInterface $streamFactory;

    private CatalogProcessTypeMapperInterface $catalogProcessTypeMapper;

    private CatalogLoaderInterface $catalogLoader;

    public function __construct(
        StreamFactoryInterface $streamFactory,
        CatalogProcessTypeMapperInterface $catalogProcessTypeMapper,
        CatalogLoaderInterface $catalogLoader
    ) {
        $this->streamFactory            = $streamFactory;
        $this->catalogProcessTypeMapper = $catalogProcessTypeMapper;
        $this->catalogLoader            = $catalogLoader;
    }

    /**
     * MINIMUM_API_VERSION=400001
     * CHANGED_IN_API_VERSION=420000
     *
     * Kick off a catalog update or clean for the selected catalog
     * Added 'verify_catalog', 'gather_art'
     *
     * @param GatekeeperInterface $gatekeeper
     * @param ResponseInterface $response
     * @param ApiOutputInterface $output
     * @param array $input
     * task    = (string) 'add_to_catalog', 'clean_catalog', 'verify_catalog', 'gather_art'
     * catalog = (integer) $catalog_id)
     *
     * @return ResponseInterface
     *
     * @throws ResultEmptyException
     * @throws RequestParamMissingException
     * @throws AccessDeniedException
     */
    public function handle(
        GatekeeperInterface $gatekeeper,
        ResponseInterface $response,
        ApiOutputInterface $output,
        array $input
    ): ResponseInterface {
        $task = $input['task'] ?? null;

        if ($task === null) {
            throw new RequestParamMissingException(
                sprintf(T_('Bad Request: %s'), 'task')
            );
        }

        if ($gatekeeper->mayAccess(AccessLevelEnum::TYPE_INTERFACE, AccessLevelEnum::LEVEL_MANAGER) === false) {
            throw new AccessDeniedException(T_('Require: 75'));
        }

        $processType = $this->catalogProcessTypeMapper->map((string) $task);
        // confirm the correct data
        if ($processType === null) {
            throw new RequestParamMissingException(
                sprintf(T_('Bad Request: %s'), $task)
            );
        }

        try {
            $catalog = $this->catalogLoader->byId((int) ($input['catalog'] ?? 0));
        } catch (CatalogNotFoundException $e) {
            throw new ResultEmptyException(T_('Not Found'));
        }

        /** @todo remove constant definition */
        define('API', true);

        $processType->process($catalog);

        return $response->withBody(
            $this->streamFactory->createStream(
                $output->success(
                    sprintf('successfully started: %s', $task)
                )
            )
        );
    }
}
