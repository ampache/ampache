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

use Ampache\Module\Api\Authentication\GatekeeperInterface;
use Ampache\Module\Api\Exception\ErrorCodeEnum;
use Ampache\Module\Api\Method\Exception\AccessFailedException;
use Ampache\Module\Api\Method\Exception\RequestParamMissingException;
use Ampache\Module\Api\Method\Exception\ResultEmptyException;
use Ampache\Module\Api\Output\ApiOutputInterface;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Module\Authorization\Check\PrivilegeCheckerInterface;
use Ampache\Module\Catalog\Catalog;
use Ampache\Repository\Model\Album;
use Ampache\Repository\Model\User;
use Psr\Http\Message\ResponseInterface;

/**
 * Kicks off a catalog update or clean for the selected catalog
 *
 * The live api versions differ in how they name the catalog id (version 6 reports it as `catalog`,
 * version 8 as `filter`, each accepting the other as an alias) and in the tasks they accept
 * (version 8 added `update_catalog`). The version classes supply both; everything else is shared.
 */
abstract class AbstractCatalogActionMethod implements MethodInterface
{
    public const string ACTION = 'catalog_action';

    public const string REST_ACTION = 'action';

    // the alias the version prefers when both names are supplied; overridden per version
    protected const string FILTER_ALIAS = 'catalog';

    // the name the version reports the catalog id under; overridden per version
    protected const string FILTER_KEY = 'filter';

    /**
     * the tasks the version accepts; overridden per version
     *
     * @var string[]
     */
    protected const array TASKS = [
        'add_to_catalog',
        'clean_catalog',
        'verify_catalog',
        'gather_art',
        'garbage_collect',
    ];

    private PrivilegeCheckerInterface $privilegeChecker;

    public function __construct(
        PrivilegeCheckerInterface $privilegeChecker,
    ) {
        $this->privilegeChecker = $privilegeChecker;
    }

    /**
     * MINIMUM_API_VERSION=400001
     * CHANGED_IN_API_VERSION=420000
     *
     * Kick off a catalog update or clean for the selected catalog
     *
     * task    = (string) 'add_to_catalog', 'clean_catalog', 'verify_catalog', 'gather_art', 'garbage_collect'
     * catalog = (integer) $catalog_id
     *
     * @param array{
     *     task?: string,
     *     filter?: int,
     *     catalog?: int,
     *     api_format: string,
     *     auth: string,
     * } $input
     * @throws AccessFailedException|RequestParamMissingException|ResultEmptyException
     */
    public function handle(
        GatekeeperInterface $gatekeeper,
        ResponseInterface $response,
        ApiOutputInterface $output,
        array $input,
        User $user,
        int $apiVersion,
    ): ResponseInterface {
        if (
            !$this->privilegeChecker->check(
                AccessTypeEnum::INTERFACE,
                AccessLevelEnum::MANAGER,
                $user->getId()
            )
        ) {
            throw new AccessFailedException(
                sprintf('Require: %s', AccessLevelEnum::MANAGER->value)
            );
        }

        $filter = $input[static::FILTER_ALIAS] ?? $input[static::FILTER_KEY] ?? null;
        if ($filter === null) {
            throw new RequestParamMissingException(
                sprintf('Bad Request: %s', static::FILTER_KEY)
            );
        }

        if (!array_key_exists('task', $input)) {
            throw new RequestParamMissingException(
                sprintf('Bad Request: %s', 'task')
            );
        }

        $task = (string) $input['task'];

        // confirm the correct data
        if (!in_array($task, static::TASKS, true)) {
            $response->getBody()->write(
                $output->error(
                    $apiVersion,
                    ErrorCodeEnum::BAD_REQUEST,
                    sprintf('Bad Request: %s', $task),
                    static::ACTION,
                    'task'
                )
            );

            return $response;
        }

        $catalog = Catalog::create_from_id((int) $filter);
        if ($catalog === null) {
            throw new ResultEmptyException(
                (string) $filter,
                'catalog'
            );
        }

        $options = [
            'gather_art' => true,
            'parse_playlist' => false,
        ];

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
                $catalog->add_to_catalog($options);
                break;
            case 'update_catalog':
                // full update; runs clean, verify then add in that order
                $catalog->clean_catalog_proc();
                $catalog->verify_catalog_proc();
                $catalog->add_to_catalog($options);
                break;
            case 'garbage_collect':
                $catalogMediaType = $catalog->gather_types;
                if ($catalogMediaType === 'music') {
                    Catalog::clean_empty_albums();
                    Album::update_album_artist();
                }
                Catalog::update_catalog_map($catalogMediaType);
                break;
        }

        $response->getBody()->write(
            $output->success($apiVersion, 'successfully started: ' . $task)
        );

        return $response;
    }
}
