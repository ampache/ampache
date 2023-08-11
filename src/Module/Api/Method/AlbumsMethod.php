<?php

/*
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 *  LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2023
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

declare(strict_types=0);

namespace Ampache\Module\Api\Method;

use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Module\Api\Api;
use Ampache\Module\Api\Authentication\GatekeeperInterface;
use Ampache\Module\Api\Method\Exception\ResultEmptyException;
use Ampache\Module\Api\Output\ApiOutputInterface;
use Ampache\Repository\Model\User;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;

/**
 * Class AlbumsMethod
 * @package Lib\ApiMethods
 */
final class AlbumsMethod implements MethodInterface
{
    public const ACTION = 'albums';

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
     * This returns albums based on the provided search filters
     *
     * @param GatekeeperInterface $gatekeeper
     * @param ResponseInterface $response
     * @param ApiOutputInterface $output
     * @param array $input
     * @param User $user
     * filter  = (string) Alpha-numeric search term //optional
     * exact   = (integer) 0,1, if true filter is exact rather then fuzzy //optional
     * add     = Api::set_filter(date) //optional
     * update  = Api::set_filter(date) //optional
     * offset  = (integer) //optional
     * limit   = (integer) //optional
     * include = (array|string) 'songs' //optional
     * @return ResponseInterface
     *
     * @throws ResultEmptyException
     */
    public function handle(
        GatekeeperInterface $gatekeeper,
        ResponseInterface $response,
        ApiOutputInterface $output,
        array $input,
        User  $user
    ): ResponseInterface {
        $browse = $this->modelFactory->createBrowse(null, false);
        $browse->reset_filters();
        $browse->set_type('album');
        $browse->set_sort('name', 'ASC');
        $method = (array_key_exists('exact', $input) && (int)$input['exact'] == 1) ? 'exact_match' : 'alpha_match';
        Api::set_filter($method, $input['filter'] ?? '', $browse);
        Api::set_filter('add', $input['add'] ?? '', $browse);
        Api::set_filter('update', $input['update'] ?? '', $browse);

        $results = $browse->get_objects();
        if ($results === []) {
            throw new ResultEmptyException(
                T_('No Results')
            );
        }
        $include = [];
        if (array_key_exists('include', $input)) {
            $include = (is_array($input['include'])) ? $input['include'] : explode(',', (string)$input['include']);
        }

        ob_end_clean();

        $result = $output->albums(
            $results,
            $include,
            $user,
            true,
            true,
            (int)$input['limit'] ?? 0,
            (int)$input['offset'] ?? 0
        );

        return $response->withBody(
            $this->streamFactory->createStream(
                $result
            )
        );
    }
}
