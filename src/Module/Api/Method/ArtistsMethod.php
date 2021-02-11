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

use Ampache\Module\Api\Api;
use Ampache\Module\Api\Authentication\GatekeeperInterface;
use Ampache\Module\Api\Method\Exception\ResultEmptyException;
use Ampache\Module\Api\Output\ApiOutputInterface;
use Ampache\Repository\Model\ModelFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;

final class ArtistsMethod implements MethodInterface
{
    public const ACTION = 'artists';

    private StreamFactoryInterface $streamFactory;

    private ModelFactoryInterface $modelFactory;

    public function __construct(
        StreamFactoryInterface $streamFactory,
        ModelFactoryInterface $modelFactory
    ) {
        $this->streamFactory = $streamFactory;
        $this->modelFactory  = $modelFactory;
    }

    /**
     * MINIMUM_API_VERSION=380001
     *
     * This takes a collection of inputs and returns
     * artist objects. This function is deprecated!
     *
     * @param GatekeeperInterface $gatekeeper
     * @param ResponseInterface $response
     * @param ApiOutputInterface $output
     * @param array $input
     * filter       = (string) Alpha-numeric search term //optional
     * exact        = (integer) 0,1, if true filter is exact rather then fuzzy //optional
     * add          = self::set_filter(date) //optional
     * update       = self::set_filter(date) //optional
     * include      = (array|string) 'albums', 'songs' //optional
     * album_artist = (integer) 0,1, if true filter for album artists only //optional
     * offset       = (integer) //optional
     * limit        = (integer) //optional
     *
     * @return ResponseInterface
     *
     * @throws ResultEmptyException
     */
    public function handle(
        GatekeeperInterface $gatekeeper,
        ResponseInterface $response,
        ApiOutputInterface $output,
        array $input
    ): ResponseInterface {
        $browse = $this->modelFactory->createBrowse(null, false);
        $browse->reset_filters();
        $browse->set_type('artist');
        $browse->set_sort('name', 'ASC');
        $method = $input['exact'] ? 'exact_match' : 'alpha_match';
        Api::set_filter($method, $input['filter'] ?? '', $browse);
        Api::set_filter('add', $input['add'] ?? '', $browse);
        Api::set_filter('update', $input['update'] ?? '', $browse);

        $artists  = $browse->get_objects();
        if ($artists === []) {
            throw new ResultEmptyException(
                T_('No Results')
            );
        }

        $include = (is_array($input['include'])) ? $input['include'] : explode(',', (string) $input['include']);

        return $response->withBody(
            $this->streamFactory->createStream(
                $output->artists($artists, $include, $gatekeeper->getUser()->getId())
            )
        );
    }
}
