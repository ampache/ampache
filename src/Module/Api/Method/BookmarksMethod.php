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

namespace Ampache\Module\Api\Method;

use Ampache\Module\Api\Authentication\GatekeeperInterface;
use Ampache\Module\Api\Output\ApiOutputInterface;
use Ampache\Repository\BookmarkRepositoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;

final class BookmarksMethod implements MethodInterface
{
    public const ACTION = 'bookmarks';

    private StreamFactoryInterface $streamFactory;

    private BookmarkRepositoryInterface $bookmarkRepository;

    public function __construct(
        StreamFactoryInterface $streamFactory,
        BookmarkRepositoryInterface $bookmarkRepository
    ) {
        $this->streamFactory      = $streamFactory;
        $this->bookmarkRepository = $bookmarkRepository;
    }

    /**
     * MINIMUM_API_VERSION=5.0.0
     *
     * Get information about bookmarked media this user is allowed to manage.
     *
     * @param GatekeeperInterface $gatekeeper
     * @param ResponseInterface $response
     * @param ApiOutputInterface $output
     * @param array $input
     *
     * @return ResponseInterface
     */
    public function handle(
        GatekeeperInterface $gatekeeper,
        ResponseInterface $response,
        ApiOutputInterface $output,
        array $input
    ): ResponseInterface {
        $bookmarkIds = $this->bookmarkRepository->getBookmarks($gatekeeper->getUser()->getId());

        if ($bookmarkIds === []) {
            $result = $output->emptyResult('bookmark');
        } else {
            $result = $output->bookmarks($bookmarkIds);
        }

        return $response->withBody(
            $this->streamFactory->createStream($result)
        );
    }
}
