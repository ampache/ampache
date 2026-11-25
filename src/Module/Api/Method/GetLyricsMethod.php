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
use Ampache\Module\Api\Method\Exception\RequestParamMissingException;
use Ampache\Module\Api\Output\ApiOutputInterface;
use Ampache\Repository\Model\LyricsSourceEnum;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\Model\Song;
use Ampache\Repository\Model\User;
use Psr\Http\Message\ResponseInterface;

/**
 * Returns database lyrics, or searches for them with plugins, by song id
 */
final class GetLyricsMethod implements MethodInterface
{
    public const string ACTION = 'get_lyrics';

    private ModelFactoryInterface $modelFactory;

    public function __construct(
        ModelFactoryInterface $modelFactory,
    ) {
        $this->modelFactory = $modelFactory;
    }

    /**
     * MINIMUM_API_VERSION=6.9.0
     *
     * Return Database lyrics or search with plugins by Song id
     *
     * filter  = (string) song id
     * plugins = (int) 0,1, if false disable plugin lookup (Default: 1)
     * force   = (int) 0,1, if true search plugins even when the database has lyrics (Default: 0) API8 only
     *
     * @param array{
     *     filter?: string,
     *     plugins?: int,
     *     force?: int,
     *     api_format: string,
     *     auth: string,
     * } $input
     * @throws RequestParamMissingException
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
        $libitem  = $this->modelFactory->createSong($objectId);

        // a song outside the caller's catalogs or withdrawn is refused like an id that was never there
        if ($libitem->isNew() || !$libitem->isShownTo($user)) {
            $response->getBody()->write(
                $output->error(
                    $apiVersion,
                    ErrorCodeEnum::BAD_REQUEST,
                    sprintf('Bad Request: %s', $objectId),
                    self::ACTION,
                    'filter'
                )
            );

            return $response;
        }

        $results = [
            'object_id' => ($apiVersion >= 8) ? (string) $objectId : $objectId,
            'object_type' => 'song',
            'plugin' => [],
        ];

        // the partial ext-info read skips the lyrics column, so load the full row before reading it
        $libitem->fill_ext_info();
        $databaseLyrics = $libitem->get_lyrics(true);
        if (!empty($databaseLyrics)) {
            $results['plugin']['database'] = $databaseLyrics;
        }

        // plugins are only searched when the database has nothing, unless an API8 caller forces it
        $force = $apiVersion >= 8 && (int) ($input['force'] ?? 0) === 1;
        if ((empty($databaseLyrics) || $force) && (int) ($input['plugins'] ?? 1) === 1) {
            $found = array_filter($libitem->get_lyrics_candidates($user), static fn(array $lyrics): bool => !empty($lyrics['text']));
            $results['plugin'] += $found;

            // cache the first hit so the same song isn't searched externally again; a forced search never replaces stored lyrics
            $first = current($found);
            if (empty($databaseLyrics) && $first !== false) {
                Song::update_lyrics((string) $first['text'], $libitem->getId(), LyricsSourceEnum::PLUGIN);
            }
        }

        $response->getBody()->write(
            $output->keyedArray($apiVersion, $results)
        );

        return $response;
    }
}
