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
use Ampache\Module\Api\Method\Exception\RequestParamMissingException;
use Ampache\Module\Api\Method\Lib\DemocraticControlMapperInterface;
use Ampache\Module\Api\Output\ApiOutputInterface;
use Ampache\Repository\DemocraticRepositoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;

final class DemocraticMethod implements MethodInterface
{
    public const ACTION = 'democratic';

    private DemocraticControlMapperInterface $democraticControlMapper;

    private StreamFactoryInterface $streamFactory;

    private DemocraticRepositoryInterface $democraticRepository;

    public function __construct(
        DemocraticControlMapperInterface $democraticControlMapper,
        StreamFactoryInterface $streamFactory,
        DemocraticRepositoryInterface $democraticRepository
    ) {
        $this->democraticControlMapper = $democraticControlMapper;
        $this->streamFactory           = $streamFactory;
        $this->democraticRepository    = $democraticRepository;
    }

    /**
     * MINIMUM_API_VERSION=380001
     *
     * This is for controlling democratic play
     *
     * @param GatekeeperInterface $gatekeeper ,
     * @param ResponseInterface $response
     * @param ApiOutputInterface $output
     * @param array $input
     * method = (string) 'vote', 'devote', 'playlist', 'play'
     * oid    = (integer) //optional
     *
     * @return ResponseInterface
     *
     * @throws RequestParamMissingException
     */
    public function handle(
        GatekeeperInterface $gatekeeper,
        ResponseInterface $response,
        ApiOutputInterface $output,
        array $input
    ): ResponseInterface {
        $method = $input['method'] ?? null;

        if ($method === null) {
            throw new RequestParamMissingException(
                sprintf(T_('Bad Request: %s'), 'method')
            );
        }

        $action = $this->democraticControlMapper->map((string) $method);

        if ($action === null) {
            throw new RequestParamMissingException(
                T_('Invalid Request')
            );
        }

        $user = $gatekeeper->getUser();

        // Load up democratic information
        $democratic = $this->democraticRepository->getCurrent(
            (int) $user->access
        );
        $democratic->set_parent();

        return $response->withBody(
            $this->streamFactory->createStream(
                call_user_func(
                    $action,
                    $democratic,
                    $output,
                    $user,
                    (int) ($input['oid'] ?? 0)
                )
            )
        );
    }
}
