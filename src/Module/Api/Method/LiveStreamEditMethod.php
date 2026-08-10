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
use Ampache\Module\Api\Method\Exception\AccessFailedException;
use Ampache\Module\Api\Method\Exception\RequestParamMissingException;
use Ampache\Module\Api\Method\Exception\ResultEmptyException;
use Ampache\Module\Api\Output\ApiOutputInterface;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Module\Authorization\Check\PrivilegeCheckerInterface;
use Ampache\Module\Catalog\Catalog;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\Model\User;
use Psr\Http\Message\ResponseInterface;

/**
 * Edits a live_stream (radio station)
 */
final class LiveStreamEditMethod implements MethodInterface
{
    public const string ACTION = 'live_stream_edit';

    public const string REST_ACTION = 'live_streams_edit';

    private ModelFactoryInterface $modelFactory;
    private PrivilegeCheckerInterface $privilegeChecker;

    public function __construct(
        ModelFactoryInterface $modelFactory,
        PrivilegeCheckerInterface $privilegeChecker,
    ) {
        $this->modelFactory     = $modelFactory;
        $this->privilegeChecker = $privilegeChecker;
    }

    /**
     * MINIMUM_API_VERSION=6.0.0
     *
     * Edit a live_stream (radio station) object.
     *
     * filter   = (string) object_id
     * name     = (string) Stream title //optional
     * url      = (string) URL of the http/s stream //optional
     * codec    = (string) stream codec ('mp3', 'flac', 'ogg', 'vorbis', 'opus', 'aac', 'alac') //optional
     * catalog  = (int) Catalog ID to associate with this stream //optional
     * site_url = (string) Homepage URL of the stream //optional
     *
     * @param array{
     *     filter?: string,
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

        if (!array_key_exists('filter', $input)) {
            throw new RequestParamMissingException(
                sprintf('Bad Request: %s', 'filter')
            );
        }

        $objectId = (int) filter_var($input['filter'], FILTER_SANITIZE_NUMBER_INT);
        $item     = $this->modelFactory->createLiveStream($objectId);
        if ($item->isNew()) {
            throw new ResultEmptyException(
                (string) $objectId
            );
        }

        $name = $item->name;
        if (isset($input['name']) && filter_var(urldecode($input['name']), FILTER_SANITIZE_SPECIAL_CHARS)) {
            $name = (string) filter_var(urldecode($input['name']), FILTER_SANITIZE_SPECIAL_CHARS);
        }

        $url = $item->url;
        if (isset($input['url']) && filter_var(urldecode($input['url']), FILTER_VALIDATE_URL)) {
            $url = (string) filter_var(urldecode($input['url']), FILTER_VALIDATE_URL);
        }

        $codec = $item->codec;
        if (isset($input['codec']) && preg_replace("/[^a-z]/", "", strtolower($input['codec']))) {
            $codec = (string) preg_replace("/[^a-z]/", "", strtolower($input['codec']));
        }

        $siteUrl = $item->site_url;
        if (isset($input['site_url']) && filter_var(urldecode($input['site_url']), FILTER_VALIDATE_URL)) {
            $siteUrl = (string) filter_var(urldecode($input['site_url']), FILTER_VALIDATE_URL);
        }

        $catalogId = $item->catalog;
        if (isset($input['catalog']) && filter_var($input['catalog'], FILTER_SANITIZE_NUMBER_INT)) {
            $catalogId = (int) filter_var($input['catalog'], FILTER_SANITIZE_NUMBER_INT);
        }

        // Make sure it's a real catalog
        if (Catalog::create_from_id($catalogId) === null) {
            throw new ResultEmptyException(
                (string) $catalogId,
                'catalog'
            );
        }

        $results = $item->update([
            'object_id' => $objectId,
            'name' => $name,
            'url' => $url,
            'codec' => $codec,
            'catalog' => $catalogId,
            'site_url' => $siteUrl,
        ]);

        if ($results === null) {
            $response->getBody()->write(
                $output->writeEmpty($apiVersion, null)
            );

            return $response;
        }

        $response->getBody()->write(
            $output->liveStreams($apiVersion, [$results], $user, false)
        );

        return $response;
    }
}
