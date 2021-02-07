<?php
/*
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 *  LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
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

namespace Ampache\Module\Api\Method;

use Ampache\Module\Api\Authentication\GatekeeperInterface;
use Ampache\Module\Api\Output\ApiOutputInterface;
use Ampache\Repository\CatalogRepositoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;

final class CatalogsMethod implements MethodInterface
{
    public const ACTION = 'catalogs';

    private StreamFactoryInterface $streamFactory;

    private CatalogRepositoryInterface $catalogRepository;

    public function __construct(
        StreamFactoryInterface $streamFactory,
        CatalogRepositoryInterface $catalogRepository
    ) {
        $this->streamFactory     = $streamFactory;
        $this->catalogRepository = $catalogRepository;
    }

    /**
     * MINIMUM_API_VERSION=420000
     *
     * Get information about catalogs this user is allowed to manage.
     *
     * @param GatekeeperInterface $gatekeeper
     * @param ResponseInterface $response
     * @param ApiOutputInterface $output
     * @param array $input
     * filter = (string) set $filter_type //optional
     * offset = (integer) //optional
     * limit  = (integer) //optional
     *
     * @return ResponseInterface
     */
    public function handle(
        GatekeeperInterface $gatekeeper,
        ResponseInterface $response,
        ApiOutputInterface $output,
        array $input
    ): ResponseInterface {
        $filterValue = (string) ($input['filter'] ?? '');
        $filter      = null;

        // filter for specific catalog types
        if (in_array($filterValue, ['music', 'clip', 'tvshow', 'movie', 'personal_video', 'podcast'])) {
            $filter = $filterValue;
        }

        $catalogIds = $this->catalogRepository->getList($filter);

        if ($catalogIds === []) {
            $result = $output->emptyResult('catalog');
        } else {
            $result = $output->catalogs($catalogIds);
        }

        return $response->withBody(
            $this->streamFactory->createStream($result)
        );
    }
}
