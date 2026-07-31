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
use Ampache\Repository\Model\Catalog;
use Ampache\Repository\Model\Live_Stream;
use Ampache\Repository\Model\User;
use Psr\Http\Message\ResponseInterface;

/**
 * Creates a live_stream (radio station)
 */
final class LiveStreamCreateMethod implements MethodInterface
{
    public const string ACTION = 'live_stream_create';

    public const string REST_ACTION = 'live_streams_create';

    private PrivilegeCheckerInterface $privilegeChecker;

    public function __construct(
        PrivilegeCheckerInterface $privilegeChecker,
    ) {
        $this->privilegeChecker = $privilegeChecker;
    }

    /**
     * MINIMUM_API_VERSION=6.0.0
     *
     * Create a live_stream (radio station) object.
     *
     * name     = (string) Stream title
     * url      = (string) URL of the http/s stream
     * codec    = (string) stream codec ('mp3', 'flac', 'ogg', 'vorbis', 'opus', 'aac', 'alac')
     * catalog  = (int) Catalog ID to associate with this stream
     * site_url = (string) Homepage URL of the stream //optional
     *
     * @param array{
     *     name?: string,
     *     url?: string,
     *     codec?: string,
     *     catalog?: int,
     *     site_url?: string,
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
                AccessLevelEnum::CONTENT_MANAGER,
                $user->getId()
            )
        ) {
            throw new AccessFailedException(
                sprintf('Require: %s', AccessLevelEnum::CONTENT_MANAGER->value)
            );
        }

        foreach (['name', 'codec', 'url', 'catalog'] as $parameter) {
            if (!array_key_exists($parameter, $input)) {
                throw new RequestParamMissingException(
                    sprintf('Bad Request: %s', $parameter)
                );
            }
        }

        $name      = (string) $input['name'];
        $url       = filter_var(urldecode((string) $input['url']), FILTER_VALIDATE_URL) ?: null;
        $codec     = (string) preg_replace("/[^a-z]/", "", strtolower((string) $input['codec']));
        $siteUrl   = (isset($input['site_url'])) ? filter_var(urldecode($input['site_url']), FILTER_VALIDATE_URL) : null;
        $catalogId = (int) filter_var($input['catalog'], FILTER_SANITIZE_NUMBER_INT);

        // Make sure it's a real catalog
        if (Catalog::create_from_id($catalogId) === null) {
            throw new ResultEmptyException(
                (string) $catalogId,
                'catalog'
            );
        }

        if (!$url) {
            $response->getBody()->write(
                $output->error(
                    $apiVersion,
                    ErrorCodeEnum::BAD_REQUEST,
                    sprintf('Bad Request: %s', $url),
                    self::ACTION,
                    'url'
                )
            );

            return $response;
        }

        $results = Live_Stream::create([
            'name' => $name,
            'url' => $url,
            'codec' => $codec,
            'catalog' => $catalogId,
            'site_url' => $siteUrl,
        ]);

        if (empty($results)) {
            $response->getBody()->write(
                $output->writeEmpty($apiVersion, null)
            );

            return $response;
        }

        $response->getBody()->write(
            $output->liveStreams($apiVersion, [(int) $results], $user, false)
        );

        return $response;
    }
}
