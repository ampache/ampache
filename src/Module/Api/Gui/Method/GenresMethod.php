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

use Ampache\Module\Api\Gui\Api;
use Ampache\Module\Api\Gui\Authentication\GatekeeperInterface;
use Ampache\Module\Api\Gui\Output\ApiOutputInterface;
use Ampache\Repository\Model\ModelFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;

final class GenresMethod implements MethodInterface
{
    public const ACTION = 'genres';

    private ModelFactoryInterface $modelFactory;

    private StreamFactoryInterface $streamFactory;

    public function __construct(
        ModelFactoryInterface $modelFactory,
        StreamFactoryInterface $streamFactory
    ) {
        $this->modelFactory  = $modelFactory;
        $this->streamFactory = $streamFactory;
    }

    /**
     * MINIMUM_API_VERSION=380001
     *
     * This returns the genres (Tags) based on the specified filter
     *
     * @param GatekeeperInterface $gatekeeper
     * @param ResponseInterface $response
     * @param ApiOutputInterface $output
     * @param array $input
     * filter = (string) Alpha-numeric search term //optional
     * exact  = (integer) 0,1, if true filter is exact rather then fuzzy //optional
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
        $browse = $this->modelFactory->createBrowse(null);
        $browse->reset_filters();
        $browse->set_type('tag');
        $browse->set_sort('name', 'ASC');

        $method = ($input['exact'] ?? '') ? 'exact_match' : 'alpha_match';
        Api::set_filter($method, $input['filter'], $browse);

        $tags = $browse->get_objects();
        if ($tags === []) {
            $result = $output->emptyResult('genre');
        } else {
            $result = $output->genres(
                array_map('intval', $tags),
                true,
                (int) ($input['limit'] ?? 0),
                (int) ($input['offset'] ?? 0)
            );
        }

        return $response->withBody(
            $this->streamFactory->createStream($result)
        );
    }
}
