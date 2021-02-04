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
use Ampache\Repository\TagRepositoryInterface;
use Ampache\Repository\UserRepositoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;

final class GenreSongsMethod implements MethodInterface
{
    public const ACTION = 'genre_songs';

    private StreamFactoryInterface $streamFactory;

    private UserRepositoryInterface $userRepository;

    private TagRepositoryInterface $tagRepository;

    public function __construct(
        StreamFactoryInterface $streamFactory,
        UserRepositoryInterface $userRepository,
        TagRepositoryInterface $tagRepository
    ) {
        $this->streamFactory  = $streamFactory;
        $this->userRepository = $userRepository;
        $this->tagRepository  = $tagRepository;
    }

    /**
     * MINIMUM_API_VERSION=380001
     *
     * returns the songs for this genre
     *
     * @param GatekeeperInterface $gatekeeper
     * @param ResponseInterface $response
     * @param ApiOutputInterface $output
     * @param array $input
     * filter = (string) UID of Genre //optional
     *
     * @return ResponseInterface
     */
    public function handle(
        GatekeeperInterface $gatekeeper,
        ResponseInterface $response,
        ApiOutputInterface $output,
        array $input
    ): ResponseInterface {
        $objectIds = $this->tagRepository->getTagObjectIds(
            'song',
            (int) ($input['filter'] ?? 0)
        );

        if ($objectIds === []) {
            $result = $output->emptyResult('song');
        } else {
            $result = $output->songs(
                $objectIds,
                $gatekeeper->getUser()->getId()
            );
        }

        return $response->withBody(
            $this->streamFactory->createStream($result)
        );
    }
}
