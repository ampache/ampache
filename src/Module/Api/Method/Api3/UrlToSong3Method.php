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

namespace Ampache\Module\Api\Method\Api3;

use Ampache\Config\AmpConfig;
use Ampache\Module\Api\Authentication\GatekeeperInterface;
use Ampache\Module\Api\Method\MethodInterface;
use Ampache\Module\Api\Output\ApiOutputInterface;
use Ampache\Module\Playback\Stream_Url;
use Ampache\Repository\Model\User;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;

final class UrlToSong3Method implements MethodInterface
{
    public const string ACTION = 'url_to_song';

    public function __construct(
        private StreamFactoryInterface $streamFactory,
    ) {}

    /**
     * url_to_song
     *
     * This takes a url and returns the song object in question
     *
     * @param array{
     *     url: string,
     *     api_format: string,
     *     auth: string,
     * } $input
     * @param 3 $apiVersion
     */
    public function handle(
        GatekeeperInterface $gatekeeper,
        ResponseInterface $response,
        ApiOutputInterface $output,
        array $input,
        User $user,
        int $apiVersion,
    ): ResponseInterface {
        $charset  = AmpConfig::get('site_charset', 'UTF-8');
        $song_url = html_entity_decode($input['url'], ENT_QUOTES, $charset);
        $url_data = Stream_Url::parse($song_url);
        ob_end_clean();

        return $response->withBody(
            $this->streamFactory->createStream(
                $output->songs($apiVersion, [(int) ($url_data['id'] ?? 0)], $user, $input['auth'])
            )
        );
    }
}
