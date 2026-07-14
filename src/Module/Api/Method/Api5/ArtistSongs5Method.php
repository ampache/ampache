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

namespace Ampache\Module\Api\Method\Api5;

use Ampache\Module\Api\Authentication\GatekeeperInterface;
use Ampache\Module\Api\Method\Exception\RequestParamMissingException;
use Ampache\Module\Api\Method\Exception\ResultEmptyException;
use Ampache\Module\Api\Method\MethodInterface;
use Ampache\Module\Api\Output\ApiOutputInterface;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\Model\User;
use Ampache\Repository\SongRepositoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;

/**
 * Returns the songs of a single artist.
 *
 * Version 5 reads the songs straight from the song repository and ignores the `sort` and `cond`
 * parameters that the later versions browse with, so it keeps a method of its own.
 */
final class ArtistSongs5Method implements MethodInterface
{
    public const string ACTION = 'artist_songs';

    public function __construct(
        private ModelFactoryInterface $modelFactory,
        private SongRepositoryInterface $songRepository,
        private StreamFactoryInterface $streamFactory,
    ) {}

    /**
     * artist_songs
     * MINIMUM_API_VERSION=380001
     *
     * This returns the songs of the specified artist
     *
     * filter = (string) UID of Artist
     * top50 = (integer) 0,1, if true filter to the artist top 50 //optional
     * offset = (integer) //optional
     * limit = (integer) //optional
     *
     * @param array{
     *     filter?: string,
     *     top50?: int,
     *     offset?: int,
     *     limit?: int,
     *     api_format: string,
     *     auth: string,
     * } $input
     * @param 5 $apiVersion
     *
     * @throws RequestParamMissingException
     * @throws ResultEmptyException
     */
    public function handle(
        GatekeeperInterface $gatekeeper,
        ResponseInterface $response,
        ApiOutputInterface $output,
        array $input,
        User $user,
        int $apiVersion,
    ): ResponseInterface {
        if (!array_key_exists('filter', $input)) {
            throw new RequestParamMissingException(
                sprintf('Bad Request: %s', 'filter')
            );
        }

        $objectId = (int) $input['filter'];

        $artist = $this->modelFactory->createArtist($objectId);
        if ($artist->isNew()) {
            throw new ResultEmptyException((string) $objectId);
        }

        $results = (array_key_exists('top50', $input) && (int) $input['top50'] == 1)
            ? $this->songRepository->getTopSongsByArtist($artist)
            : $this->songRepository->getByArtist($objectId);

        if ($results === []) {
            return $response->withBody(
                $this->streamFactory->createStream(
                    $output->writeEmpty($apiVersion, 'song')
                )
            );
        }

        $output->setOffset($apiVersion, $input['offset'] ?? 0);
        $output->setLimit($apiVersion, $input['limit'] ?? 0);

        return $response->withBody(
            $this->streamFactory->createStream(
                $output->songs($apiVersion, $results, $user, $input['auth'])
            )
        );
    }
}
