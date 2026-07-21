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
use Ampache\Module\Api\Method\Exception\AccessDeniedException;
use Ampache\Module\Api\Method\Exception\RequestParamMissingException;
use Ampache\Module\Api\Output\ApiOutputInterface;
use Ampache\Repository\Model\User;
use Ampache\Repository\ShoutRepositoryInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Returns the latest posted shouts
 *
 * The two live api versions only differ in how they name the user: version 6 reports it as
 * `username` and version 8 as `filter`, each accepting the other as an alias. The version classes
 * supply that pair of names; everything else is shared.
 */
abstract class AbstractLastShoutsMethod implements MethodInterface
{
    public const string ACTION = 'last_shouts';

    // the alias the version prefers when both names are supplied; overridden per version
    protected const string FILTER_ALIAS = 'username';

    // the name the version reports the user under; overridden per version
    protected const string FILTER_KEY = 'filter';

    private ConfigContainerInterface $configContainer;
    private ShoutRepositoryInterface $shoutRepository;

    public function __construct(
        ConfigContainerInterface $configContainer,
        ShoutRepositoryInterface $shoutRepository,
    ) {
        $this->configContainer = $configContainer;
        $this->shoutRepository = $shoutRepository;
    }

    /**
     * MINIMUM_API_VERSION=380001
     *
     * This gets the latest posted shouts
     *
     * filter   = (integer|string) filter by user id OR username //optional
     * username = (string) $username //optional
     * limit    = (integer) $limit Default: 10 (popular_threshold) //optional
     *
     * @param array{
     *     filter?: int|string,
     *     username?: string,
     *     limit?: int,
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
        if (!$this->configContainer->get(ConfigurationKeyEnum::SOCIABLE)) {
            throw new AccessDeniedException(
                'Enable: sociable'
            );
        }

        $filter = $input[static::FILTER_ALIAS] ?? $input[static::FILTER_KEY] ?? null;
        if ($filter === null) {
            throw new RequestParamMissingException(
                sprintf('Bad Request: %s', static::FILTER_KEY)
            );
        }

        $limit = (int) ($input['limit'] ?? 0);
        if ($limit < 1) {
            $limit = (int) $this->configContainer->get(ConfigurationKeyEnum::POPULAR_THRESHOLD);
        }

        $username = (!empty($filter))
            ? $filter
            : null;

        $results = (is_numeric($username))
            ? iterator_to_array($this->shoutRepository->getTopById($limit, (int) $username))
            : iterator_to_array($this->shoutRepository->getTop($limit, $username));

        // no empty-envelope short circuit: the populated response is a bare `shout: []` too
        $response->getBody()->write(
            $output->shouts($apiVersion, $results)
        );

        return $response;
    }
}
