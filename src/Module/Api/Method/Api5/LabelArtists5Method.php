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

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Module\Api\Authentication\GatekeeperInterface;
use Ampache\Module\Api\Method\Exception\AccessDeniedException;
use Ampache\Module\Api\Method\Exception\RequestParamMissingException;
use Ampache\Module\Api\Method\MethodInterface;
use Ampache\Module\Api\Output\ApiOutputInterface;
use Ampache\Repository\LabelRepositoryInterface;
use Ampache\Repository\Model\User;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;

/**
 * Returns all artists attached to a label.
 *
 * Version 5 reads the artists from the label itself and does not paginate the result, so it keeps
 * a method of its own.
 */
final class LabelArtists5Method implements MethodInterface
{
    public const string ACTION = 'label_artists';

    public function __construct(
        private ConfigContainerInterface $configContainer,
        private LabelRepositoryInterface $labelRepository,
        private StreamFactoryInterface $streamFactory,
    ) {}

    /**
     * label_artists
     * MINIMUM_API_VERSION=420000
     *
     * This returns all artists attached to a label ID
     *
     * filter = (string) UID of label
     * include = (array|string) 'albums', 'songs' //optional
     *
     * @param array{
     *     filter?: string,
     *     include?: string|string[],
     *     offset?: int,
     *     limit?: int,
     *     api_format: string,
     *     auth: string,
     * } $input
     * @param 5 $apiVersion
     *
     * @throws AccessDeniedException
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
        if (!$this->configContainer->get(ConfigurationKeyEnum::LABEL)) {
            throw new AccessDeniedException(
                'Enable: label'
            );
        }

        if (!array_key_exists('filter', $input)) {
            throw new RequestParamMissingException(
                sprintf('Bad Request: %s', 'filter')
            );
        }

        $include = [];
        if (array_key_exists('include', $input)) {
            if (!is_array($input['include'])) {
                $input['include'] = explode(',', html_entity_decode((string) $input['include']));
            }

            foreach ($input['include'] as $item) {
                if ($item === 'songs' || $item == '1') {
                    $include[] = 'songs';
                }

                if ($item === 'albums' || $item == '1') {
                    $include[] = 'albums';
                }
            }
        }

        $label = $this->labelRepository->findById((int) $input['filter']);
        if ($label === null) {
            return $response->withBody(
                $this->streamFactory->createStream(
                    $output->writeEmpty($apiVersion, 'artist')
                )
            );
        }

        $results = $label->get_artists();
        if ($results === []) {
            return $response->withBody(
                $this->streamFactory->createStream(
                    $output->writeEmpty($apiVersion, 'artist')
                )
            );
        }

        $output->setOffset($apiVersion, $input['offset'] ?? 0);
        $output->setLimit($apiVersion, $input['limit'] ?? 0);

        return $response->withBody(
            $this->streamFactory->createStream(
                $output->artists($apiVersion, $results, $include, $user, $input['auth'])
            )
        );
    }
}
