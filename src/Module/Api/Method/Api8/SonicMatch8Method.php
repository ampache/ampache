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

namespace Ampache\Module\Api\Method\Api8;

use Ampache\Module\Api\Authentication\GatekeeperInterface;
use Ampache\Module\Api\Method\Exception\AccessDeniedException;
use Ampache\Module\Api\Method\Exception\RequestParamMissingException;
use Ampache\Module\Api\Method\Exception\ResultEmptyException;
use Ampache\Module\Api\Method\MethodInterface;
use Ampache\Module\Api\Output\ApiOutputInterface;
use Ampache\Module\System\Plugin\Plugin;
use Ampache\Module\System\Plugin\PluginTypeEnum;
use Ampache\Plugin\PluginSonicAnalysisInterface;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\Model\User;
use Psr\Http\Message\ResponseInterface;

/**
 * Returns songs that sound like a given song
 *
 * The native counterpart of the OpenSubsonic `sonicSimilarity` extension, so API8 clients get the same feature
 * without having to speak Subsonic. Both are served by the same sonic-analysis plugin.
 */
final class SonicMatch8Method implements MethodInterface
{
    public const string ACTION = 'sonic_match';

    private ModelFactoryInterface $modelFactory;

    public function __construct(
        ModelFactoryInterface $modelFactory,
    ) {
        $this->modelFactory = $modelFactory;
    }

    /**
     * sonic_match
     * MINIMUM_API_VERSION=800000
     *
     * Songs that sound like the given song, most similar first
     *
     * filter = (string) UID of Song
     * limit  = (integer) //optional
     * offset = (integer) //optional
     *
     * @param array{
     *     filter?: string,
     *     offset?: int,
     *     limit?: int,
     *     api_format: string,
     *     auth: string,
     * } $input
     *
     * @throws AccessDeniedException
     * @throws RequestParamMissingException
     * @throws ResultEmptyException
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

        // Similarity here is derived from analysing the audio, which Ampache does not do itself. With no plugin to
        // ask, the feature is unavailable rather than empty — an empty list would claim nothing sounds alike.
        $plugin = $this->getSonicAnalysisPlugin($user);
        if (!$plugin instanceof PluginSonicAnalysisInterface) {
            throw new AccessDeniedException(
                'Enable: sonic analysis plugin'
            );
        }

        $objectId = (int) $input['filter'];

        $song = $this->modelFactory->createSong($objectId);
        if ($song->isNew()) {
            throw new ResultEmptyException((string) $objectId);
        }

        $limit = (int) ($input['limit'] ?? 0);
        $count = ($limit > 0) ? $limit : 50;

        $results = $plugin->get_sonic_similar_songs($song, $count);
        if ($results === []) {
            $response->getBody()->write(
                $output->writeEmpty($apiVersion, 'song')
            );

            return $response;
        }

        $output->setOffset($apiVersion, $input['offset'] ?? 0);
        $output->setLimit($apiVersion, $limit);

        $response->getBody()->write(
            $output->sonicMatches($apiVersion, $results, $user, $input['auth'])
        );

        return $response;
    }

    /**
     * The first installed and enabled sonic-analysis plugin for this user, or null when none is available.
     */
    private function getSonicAnalysisPlugin(User $user): ?PluginSonicAnalysisInterface
    {
        foreach (Plugin::get_plugins(PluginTypeEnum::SONIC_ANALYSER) as $plugin_name) {
            $plugin = new Plugin($plugin_name);
            if (
                $plugin->_plugin instanceof PluginSonicAnalysisInterface
                && $plugin->load($user)
            ) {
                return $plugin->_plugin;
            }
        }

        return null;
    }
}
