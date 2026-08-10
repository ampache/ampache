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

namespace Ampache\Module\Api\Method\Api4;

use Ampache\Config\AmpConfig;
use Ampache\Module\Api\Api4;
use Ampache\Module\Api\Authentication\GatekeeperInterface;
use Ampache\Module\Api\Method\MethodInterface;
use Ampache\Module\Api\Output\ApiOutputInterface;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Module\Catalog\Catalog;
use Ampache\Module\Catalog\CountableTableEnum;
use Ampache\Module\Podcast\Exception\PodcastCreationException;
use Ampache\Module\Podcast\PodcastCreatorInterface;
use Ampache\Repository\Model\User;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;

final class PodcastCreate4Method implements MethodInterface
{
    public const string ACTION = 'podcast_create';

    public function __construct(
        private PodcastCreatorInterface $podcastCreator,
        private StreamFactoryInterface $streamFactory,
    ) {}

    /**
     * podcast_create
     * MINIMUM_API_VERSION=420000
     * Create a public url that can be used by anyone to stream media.
     * Takes the file id with optional description and expires parameters.
     *
     * url = (string) rss url for podcast
     * catalog = (string) podcast catalog
     *
     * @param array{
     *     url: string,
     *     catalog: string,
     *     api_format: string,
     *     auth: string,
     * } $input
     * @param 4 $apiVersion
     */
    public function handle(
        GatekeeperInterface $gatekeeper,
        ResponseInterface $response,
        ApiOutputInterface $output,
        array $input,
        User $user,
        int $apiVersion,
    ): ResponseInterface {
        if (!AmpConfig::get('podcast')) {
            Api4::message('error', 'Access Denied: podcast features are not enabled.', '400', $input['api_format']);

            return $response;
        }
        if (!Api4::check_access(AccessTypeEnum::INTERFACE, AccessLevelEnum::MANAGER, $user->id, 'update_podcast', $input['api_format'])) {
            return $response;
        }
        if (!Api4::check_parameter($input, ['url', 'catalog'], self::ACTION)) {
            return $response;
        }

        $catalog = Catalog::create_from_id((int) $input['catalog']);

        if ($catalog === null) {
            Api4::message('error', 'Catalog not found', '401', $input['api_format']);

            return $response;
        }

        try {
            $podcast = $this->podcastCreator->create(
                $input['url'],
                $catalog
            );
        } catch (PodcastCreationException) {
            Api4::message('error', 'Bad Request', '401', $input['api_format']);

            return $response;
        }

        Catalog::count_table(CountableTableEnum::PODCAST);
        ob_end_clean();

        return $response->withBody(
            $this->streamFactory->createStream(
                $output->podcasts($apiVersion, [$podcast->getId()], $user, $input['auth'])
            )
        );
    }
}
