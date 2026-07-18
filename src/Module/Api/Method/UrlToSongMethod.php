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
use Ampache\Module\Api\Exception\ErrorCodeEnum;
use Ampache\Module\Api\Method\Exception\RequestParamMissingException;
use Ampache\Module\Api\Output\ApiOutputInterface;
use Ampache\Module\Playback\Stream_Url;
use Ampache\Repository\Model\User;
use Psr\Http\Message\ResponseInterface;

/**
 * Takes a stream url and returns the song object it points at
 */
final class UrlToSongMethod implements MethodInterface
{
    public const string ACTION = 'url_to_song';

    private ConfigContainerInterface $configContainer;

    public function __construct(
        ConfigContainerInterface $configContainer,
    ) {
        $this->configContainer = $configContainer;
    }

    /**
     * MINIMUM_API_VERSION=380001
     *
     * This takes a url and returns the song object in question
     *
     * url = (string) $url
     *
     * @param array{
     *     filter?: string,
     *     url?: string,
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
        // the filter alias takes precedence over the documented url parameter
        $url = $input['filter'] ?? $input['url'] ?? null;
        if ($url === null) {
            throw new RequestParamMissingException(
                sprintf('Bad Request: %s', 'url')
            );
        }

        $charset = $this->configContainer->get(ConfigurationKeyEnum::SITE_CHARSET) ?? 'UTF-8';
        $songUrl = html_entity_decode((string) $url, ENT_QUOTES, (string) $charset);
        $urlData = Stream_Url::parse($songUrl);

        if (!array_key_exists('id', $urlData)) {
            $response->getBody()->write(
                $output->error(
                    $apiVersion,
                    ErrorCodeEnum::BAD_REQUEST,
                    'Bad Request',
                    self::ACTION,
                    'url'
                )
            );

            return $response;
        }

        $response->getBody()->write(
            $output->songs($apiVersion, [(int) $urlData['id']], $user, $input['auth'], true, false)
        );

        return $response;
    }
}
