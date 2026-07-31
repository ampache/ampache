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
use Ampache\Module\Api\Method\Exception\AccessDeniedException;
use Ampache\Module\Api\Method\Exception\AccessFailedException;
use Ampache\Module\Api\Method\Exception\RequestParamMissingException;
use Ampache\Module\Api\Method\Exception\ResultEmptyException;
use Ampache\Module\Api\Output\ApiOutputInterface;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Module\Authorization\Check\PrivilegeCheckerInterface;
use Ampache\Repository\Model\User;
use Ampache\Repository\PodcastRepositoryInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Updates the details of an existing podcast
 */
final class PodcastEditMethod implements MethodInterface
{
    public const string ACTION = 'podcast_edit';

    public const string REST_ACTION = 'podcasts_edit';

    private ConfigContainerInterface $configContainer;
    private PodcastRepositoryInterface $podcastRepository;
    private PrivilegeCheckerInterface $privilegeChecker;

    public function __construct(
        ConfigContainerInterface $configContainer,
        PodcastRepositoryInterface $podcastRepository,
        PrivilegeCheckerInterface $privilegeChecker,
    ) {
        $this->configContainer   = $configContainer;
        $this->podcastRepository = $podcastRepository;
        $this->privilegeChecker  = $privilegeChecker;
    }

    /**
     * MINIMUM_API_VERSION=420000
     * CHANGED_IN_API_VERSION=5.0.0
     *
     * Update the description and/or expiration date for an existing podcast.
     * Takes the podcast id to update with optional description and expires parameters.
     *
     * filter      = (string) Alpha-numeric search term
     * feed        = (string) feed url (xml!) //optional
     * title       = (string) title string //optional
     * website     = (string) source website url //optional
     * description = (string) //optional
     * generator   = (string) //optional
     * copyright   = (string) //optional
     *
     * @param array{
     *     filter?: string,
     *     feed?: string,
     *     title?: string,
     *     website?: string,
     *     description?: string,
     *     generator?: string,
     *     copyright?: string,
     *     api_format: string,
     *     auth: string,
     * } $input
     * @throws AccessDeniedException|AccessFailedException|RequestParamMissingException|ResultEmptyException
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

        $podcastId = (string) $input['filter'];
        $podcast   = $this->podcastRepository->findById((int) $podcastId);

        if ($podcast === null) {
            throw new ResultEmptyException(
                $podcastId
            );
        }

        $feed        = (array_key_exists('feed', $input) && filter_var($input['feed'], FILTER_VALIDATE_URL)) ? filter_var($input['feed'], FILTER_VALIDATE_URL) : $podcast->getFeedUrl();
        $title       = (array_key_exists('title', $input)) ? scrub_in((string) $input['title']) : $podcast->getTitle();
        $website     = (array_key_exists('website', $input) && filter_var($input['website'], FILTER_VALIDATE_URL)) ? filter_var($input['website'], FILTER_VALIDATE_URL) : $podcast->getWebsite();
        $description = (array_key_exists('description', $input)) ? scrub_in((string) $input['description']) : $podcast->get_description();
        $generator   = (array_key_exists('generator', $input)) ? scrub_in((string) $input['generator']) : $podcast->getGenerator();
        $copyright   = (array_key_exists('copyright', $input)) ? scrub_in((string) $input['copyright']) : $podcast->getCopyright();

        $podcast->setFeedUrl($feed)
            ->setTitle($title)
            ->setWebsite($website)
            ->setDescription($description)
            ->setGenerator($generator)
            ->setCopyright($copyright)
            ->save();

        $response->getBody()->write(
            $output->success($apiVersion, 'podcast ' . $podcastId . ' updated')
        );

        return $response;
    }
}
