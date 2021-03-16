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

declare(strict_types=0);

namespace Ampache\Module\Api\Gui\Method;

use Ampache\Module\Api\Gui\Authentication\GatekeeperInterface;
use Ampache\Module\Api\Gui\Method\Exception\RequestParamMissingException;
use Ampache\Module\Api\Gui\Output\ApiOutputInterface;
use Ampache\Module\Stream\Url\StreamUrlParserInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;

final class UrlToSongMethod implements MethodInterface
{
    public const ACTION = 'url_to_song';

    private StreamFactoryInterface $streamFactory;

    private StreamUrlParserInterface $streamUrlParser;

    public function __construct(
        StreamFactoryInterface $streamFactory,
        StreamUrlParserInterface $streamUrlParser
    ) {
        $this->streamFactory   = $streamFactory;
        $this->streamUrlParser = $streamUrlParser;
    }

    /**
     * MINIMUM_API_VERSION=380001
     *
     * This takes a url and returns the song object in question
     *
     * @param GatekeeperInterface $gatekeeper
     * @param ResponseInterface $response
     * @param ApiOutputInterface $output
     * @param array $input
     * url = (string) $url
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
        $url = $input['url'] ?? null;

        if ($url === null) {
            throw new RequestParamMissingException(
                sprintf(T_('Bad Request: %s'), 'url')
            );
        }

        // Don't scrub, the function needs her raw and juicy
        $data = $this->streamUrlParser->parse($url);


        if (array_key_exists('id', $data) === false) {
            throw new RequestParamMissingException(
                sprintf(T_('Bad Request: %s'), 'url')
            );
        }

        return $response->withBody(
            $this->streamFactory->createStream(
                $output->songs(
                    [(int) $data['id']],
                    $gatekeeper->getUser()->getId(),
                    true,
                    false
                )
            )
        );
    }
}
