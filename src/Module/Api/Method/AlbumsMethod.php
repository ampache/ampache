<?php

declare(strict_types=0);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2024
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

use Ampache\Config\AmpConfig;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Module\Api\Authentication\GatekeeperInterface;
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
     *  filter  = (string) Alpha-numeric search term //optional
     *  include = (array|string) 'songs' //optional
     *  exact   = (integer) 0,1, if true filter is exact rather then fuzzy //optional
     *  add     = $browse->set_api_filter(date) //optional
     *  update  = $browse->set_api_filter(date) //optional
     *  offset  = (integer) //optional
     *  limit   = (integer) //optional
     *  cond    = (string) Apply additional filters to the browse using ';' separated comma string pairs (e.g. 'filter1,value1;filter2,value2') //optional
     *  sort    = (string) sort name or comma separated key pair. Order default 'ASC' (e.g. 'name,ASC' and 'name' are the same) //optional
     * @param User $user
     * @return ResponseInterface
     */
    public function handle(
        GatekeeperInterface $gatekeeper,
        ResponseInterface $response,
        ApiOutputInterface $output,
        array $input,
        User $user
    ): ResponseInterface {
        $browse = $this->modelFactory->createBrowse(null, false);
        $browse->set_user_id($user);
        $browse->set_type('album');
        $original_year = AmpConfig::get('use_original_year') ? "original_year" : "year";
        $sort_type     = AmpConfig::get('album_sort');
        switch ($sort_type) {
            case 'name_asc':
                $sort  = 'name';
                $order = 'ASC';
                break;
            case 'name_desc':
                $sort  = 'name';
                $order = 'DESC';
                break;
            case 'year_asc':
                $sort  = $original_year;
                $order = 'ASC';
                break;
            case 'year_desc':
                $sort  = $original_year;
                $order = 'DESC';
                break;
            case 'default':
            default:
                $sort  = 'name_' . $original_year;
                $order = 'ASC';
        }
        $browse->set_sort_order(html_entity_decode((string)($input['sort'] ?? '')), [$sort, $order]);

        $method = (array_key_exists('exact', $input) && (int)$input['exact'] == 1) ? 'exact_match' : 'alpha_match';
        $browse->set_api_filter($method, $input['filter'] ?? '');
        $browse->set_api_filter('add', $input['add'] ?? '');
        $browse->set_api_filter('update', $input['update'] ?? '');

        $browse->set_conditions(html_entity_decode((string)($input['cond'] ?? '')));

        $results = $browse->get_objects();
        $include = [];
        if (array_key_exists('include', $input)) {
            $include = (is_array($input['include'])) ? $input['include'] : explode(',', html_entity_decode((string)($input['include'])));
        }

        ob_end_clean();

        $output->setOffset($input['offset'] ?? 0);
        $output->setLimit($input['limit'] ?? 0);
        $output->setCount($browse->get_total());

        /** @var string $result */
        $result = $output->albums(
            $results,
            $include,
            $user
        );

        return $response->withBody(
            $this->streamFactory->createStream(
                $result
            )
        );
    }
}
