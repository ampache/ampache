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

namespace Ampache\Module\Api\Method\Api6;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Module\Api\Authentication\GatekeeperInterface;
use Ampache\Module\Api\Exception\ErrorCodeEnum;
use Ampache\Module\Api\Method\Exception\AccessDeniedException;
use Ampache\Module\Api\Method\Exception\RequestParamMissingException;
use Ampache\Module\Api\Method\MethodInterface;
use Ampache\Module\Api\Method\ObjectTypeGate;
use Ampache\Module\Api\Output\ApiOutputInterface;
use Ampache\Module\Database\Query\BrowseFactoryInterface;
use Ampache\Module\System\Preference;
use Ampache\Repository\Model\User;
use Psr\Http\Message\ResponseInterface;

/**
 * Returns an index of object ids for a type
 *
 * This is deprecated and only exists in api version 6; version 8 replaced it with `index`.
 */
final class GetIndexes6Method implements MethodInterface
{
    public const string ACTION = 'get_indexes';

    private BrowseFactoryInterface $browseFactory;
    private ConfigContainerInterface $configContainer;

    public function __construct(
        ConfigContainerInterface $configContainer,
        BrowseFactoryInterface $browseFactory,
    ) {
        $this->configContainer  = $configContainer;
        $this->browseFactory    = $browseFactory;
    }

    /**
     * MINIMUM_API_VERSION=400001
     *
     * This takes a collection of inputs and returns an index of object ids
     *
     * type        = (string) the object type
     * filter      = (string) //optional
     * hide_search = (integer) 0,1 hide searches in the result //optional
     * exact       = (integer) 0,1 match the filter exactly //optional
     * add         = $browse->set_api_filter(date) //optional
     * update      = $browse->set_api_filter(date) //optional
     * include     = (integer) 0,1 include songs if available for that object //optional
     * offset      = (integer) //optional
     * limit       = (integer) //optional
     * cond        = (string) Apply additional filters to the browse //optional
     * sort        = (string) sort name or comma separated key pair //optional
     *
     * @param array{
     *     type?: string,
     *     filter?: string,
     *     hide_search?: int,
     *     exact?: int,
     *     add?: string,
     *     update?: string,
     *     include?: int,
     *     offset?: int,
     *     limit?: int,
     *     cond?: string,
     *     sort?: string,
     *     api_format: string,
     *     auth: string,
     * } $input
     * @throws AccessDeniedException|RequestParamMissingException
     */
    public function handle(
        GatekeeperInterface $gatekeeper,
        ResponseInterface $response,
        ApiOutputInterface $output,
        array $input,
        User $user,
        int $apiVersion,
    ): ResponseInterface {
        if (!array_key_exists('type', $input)) {
            throw new RequestParamMissingException(
                sprintf('Bad Request: %s', 'type')
            );
        }

        $type = (string) $input['type'];

        $gate = ObjectTypeGate::check($this->configContainer, $type);
        if ($gate !== null) {
            throw new AccessDeniedException($gate);
        }

        // confirm the correct data
        if (!in_array(strtolower($type), ObjectTypeGate::INDEX_TYPES)) {
            $response->getBody()->write(
                $output->error(
                    $apiVersion,
                    ErrorCodeEnum::BAD_REQUEST,
                    sprintf('Bad Request: %s', $type),
                    self::ACTION,
                    'type'
                )
            );

            return $response;
        }

        $include = (array_key_exists('include', $input) && (int) $input['include'] === 1);
        $hide    = (array_key_exists('hide_search', $input) && (int) $input['hide_search'] === 1)
            || (bool) $this->configContainer->get('hide_search');

        $browse = $this->browseFactory->create(null, false);
        $browse->set_user_id($user);

        if ($type === 'playlist' && $hide === false) {
            $browse->set_type('playlist_search');
        } else {
            $browse->set_type($type);
        }

        // hide playlists starting with the user string (if enabled)
        $hideString = ($type === 'playlist')
            ? str_replace(
                '%',
                '\%',
                str_replace('_', '\_', (string) Preference::get_by_user($user->getId(), 'api_hidden_playlists'))
            )
            : '';
        if (!empty($hideString)) {
            $browse->set_filter('not_starts_with', $hideString);
        }

        $browse->set_sort_order(html_entity_decode((string) ($input['sort'] ?? '')), ['name', 'ASC']);

        $method = (array_key_exists('exact', $input) && (int) $input['exact'] === 1)
            ? 'exact_match'
            : 'alpha_match';

        $browse->set_api_filter($method, $input['filter'] ?? '');
        $browse->set_api_filter('add', $input['add'] ?? '');
        $browse->set_api_filter('update', $input['update'] ?? '');

        if ($type === 'playlist') {
            $browse->set_filter('playlist_open', $user->getId());

            if (
                $hide === false
                && (bool) Preference::get_by_user($user->getId(), 'api_hide_dupe_searches') === true
            ) {
                $browse->set_filter('hide_dupe_smartlist', 1);
            }
        }

        $browse->set_conditions(html_entity_decode((string) ($input['cond'] ?? '')));

        $results = $browse->get_objects();
        if (empty($results)) {
            $response->getBody()->write(
                $output->writeEmpty($apiVersion, $type)
            );

            return $response;
        }

        $output->setOffset($apiVersion, (int) ($input['offset'] ?? 0));
        $output->setLimit($apiVersion, (int) ($input['limit'] ?? 0));

        $response->getBody()->write(
            $output->indexes($apiVersion, $results, $type, $user, $input['auth'], true, $include)
        );

        return $response;
    }
}
