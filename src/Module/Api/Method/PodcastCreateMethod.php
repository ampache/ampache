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
use Ampache\Module\Api\Method\Exception\AccessDeniedException;
use Ampache\Module\Api\Method\Exception\AccessFailedException;
use Ampache\Module\Api\Method\Exception\RequestParamMissingException;
use Ampache\Module\Api\Output\ApiOutputInterface;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Module\Authorization\Check\PrivilegeCheckerInterface;
use Ampache\Module\Podcast\Exception\PodcastCreationException;
use Ampache\Module\Podcast\PodcastCreatorInterface;
use Ampache\Repository\Model\Catalog;
use Ampache\Repository\Model\User;
use Psr\Http\Message\ResponseInterface;

/**
 * Creates a podcast from an rss feed
 */
final class PodcastCreateMethod implements MethodInterface
{
    public const string ACTION = 'podcast_create';

    public const string REST_ACTION = 'podcasts_create';

    private ConfigContainerInterface $configContainer;
    private PodcastCreatorInterface $podcastCreator;
    private PrivilegeCheckerInterface $privilegeChecker;

    public function __construct(
        ConfigContainerInterface $configContainer,
        PodcastCreatorInterface $podcastCreator,
        PrivilegeCheckerInterface $privilegeChecker,
    ) {
        $this->configContainer  = $configContainer;
        $this->podcastCreator   = $podcastCreator;
        $this->privilegeChecker = $privilegeChecker;
    }

    /**
     * MINIMUM_API_VERSION=420000
     *
     * Create a podcast from an rss feed.
     *
     * url     = (string) rss url for podcast
     * catalog = (string) podcast catalog
     *
     * @param array{
     *     url?: string,
     *     catalog?: string,
     *     api_format: string,
     *     auth: string,
     * } $input
     * @throws AccessDeniedException|AccessFailedException|RequestParamMissingException
     */
    public function handle(
        GatekeeperInterface $gatekeeper,
        ResponseInterface $response,
        ApiOutputInterface $output,
        array $input,
        User $user,
        int $apiVersion,
    ): ResponseInterface {
        if (!$this->configContainer->get(ConfigurationKeyEnum::PODCAST)) {
            throw new AccessDeniedException(
                'Enable: podcast'
            );
        }

        if (
            !$this->privilegeChecker->check(
                AccessTypeEnum::INTERFACE,
                AccessLevelEnum::MANAGER,
                $user->getId()
            )
        ) {
            throw new AccessFailedException(
                sprintf('Require: %s', AccessLevelEnum::MANAGER->value)
            );
        }

        foreach (['url', 'catalog'] as $parameter) {
            if (!array_key_exists($parameter, $input)) {
                throw new RequestParamMissingException(
                    sprintf('Bad Request: %s', $parameter)
                );
            }
        }

        $catalog = Catalog::create_from_id((int) $input['catalog']);
        if ($catalog === null) {
            return $this->writeBadRequest($response, $output, $apiVersion);
        }

        try {
            $podcast = $this->podcastCreator->create(
                urldecode((string) $input['url']),
                $catalog
            );
        } catch (PodcastCreationException) {
            return $this->writeBadRequest($response, $output, $apiVersion);
        }

        Catalog::count_table('podcast');

        $response->getBody()->write(
            $output->podcasts($apiVersion, [$podcast->getId()], $user, $input['auth'], false, false)
        );

        return $response;
    }

    private function writeBadRequest(
        ResponseInterface $response,
        ApiOutputInterface $output,
        int $apiVersion,
    ): ResponseInterface {
        $response->getBody()->write(
            $output->error(
                $apiVersion,
                ErrorCodeEnum::BAD_REQUEST,
                'Bad Request',
                self::ACTION,
                'system'
            )
        );

        return $response;
    }
}
