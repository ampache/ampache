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
use Ampache\Module\Api\Output\ApiOutputInterface;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\Model\User;
use Psr\Http\Message\ResponseInterface;

/**
 * Get information about shared media this user is allowed to manage.
 *
 * The parameters and checks are identical for api versions 6 and 8, so a single method serves
 * both. Only the output data is version specific and that is resolved by the ApiOutputInterface.
 */
final class SharesMethod implements MethodInterface
{
    public const string ACTION = 'shares';

    public function __construct(
        private ConfigContainerInterface $configContainer,
        private ModelFactoryInterface $modelFactory,
    ) {}

    /**
     * MINIMUM_API_VERSION=420000
     *
     * Get information about shared media this user is allowed to manage.
     *
     * filter = (string) Alpha-numeric search term //optional
     * exact  = (integer) 0,1, if true filter is exact rather than fuzzy //optional
     * add    = $browse->set_api_filter(date) //optional
     * update = $browse->set_api_filter(date) //optional
     * offset = (integer) //optional
     * limit  = (integer) //optional
     * cond   = (string) Apply additional filters to the browse using ';' separated comma string pairs //optional
     * sort   = (string) sort name or comma separated key pair. Order default 'ASC' (name, ASC) //optional
     *
     * @param array{
     *     filter?: string,
     *     exact?: int,
     *     add?: string,
     *     update?: string,
     *     offset?: int,
     *     limit?: int,
     *     cond?: string,
     *     sort?: string,
     *     api_format: string,
     *     auth: string,
     * } $input
     * @param 6|8 $apiVersion
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
            $response->getBody()->write(
                $output->error(
                    $apiVersion,
                    ErrorCodeEnum::ACCESS_DENIED,
                    'Enable: share',
                    self::ACTION,
                    'system'
                )
            );

            return $response;
        }

        $browse = $this->modelFactory->createBrowse(null, false);

        $browse->set_user_id($user);

        $browse->set_type('share');

        $browse->set_sort_order(html_entity_decode((string) ($input['sort'] ?? '')), ['title', 'ASC']);

        $method = (array_key_exists('exact', $input) && (int) $input['exact'] == 1)
            ? 'exact_match'
            : 'alpha_match';

        $browse->set_api_filter($method, $input['filter'] ?? '');
        $browse->set_api_filter('add', $input['add'] ?? '');
        $browse->set_api_filter('update', $input['update'] ?? '');

        $browse->set_conditions(html_entity_decode((string) ($input['cond'] ?? '')));

        $results = $browse->get_objects();
        if ($results === []) {
            $response->getBody()->write(
                $output->writeEmpty($apiVersion, 'share')
            );

            return $response;
        }

        $output->setOffset($apiVersion, $input['offset'] ?? 0);
        $output->setLimit($apiVersion, $input['limit'] ?? 0);

        $response->getBody()->write(
            $output->shares($apiVersion, $results, $user)
        );

        return $response;
    }
}
