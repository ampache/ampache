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
use Ampache\Module\Api\Method\Exception\RequestParamMissingException;
use Ampache\Module\Api\Output\ApiOutputInterface;
use Ampache\Module\System\Plugin\PluginTypeEnum;
use Ampache\Plugin\PluginGetLyricsInterface;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\Model\Plugin;
use Ampache\Repository\Model\User;
use Psr\Http\Message\ResponseInterface;

/**
 * Returns database lyrics, or searches for them with plugins, by song id
 */
final class GetLyricsMethod implements MethodInterface
{
    public const string ACTION = 'get_lyrics';

    private ModelFactoryInterface $modelFactory;

    public function __construct(
        ModelFactoryInterface $modelFactory,
    ) {
        $this->modelFactory = $modelFactory;
    }

    /**
     * MINIMUM_API_VERSION=6.9.0
     *
     * Return Database lyrics or search with plugins by Song id
     *
     * filter  = (string) song id
     * plugins = (int) 0,1, if false disable plugin lookup (Default: 1)
     *
     * @param array{
     *     filter?: string,
     *     plugins?: int,
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
        if (!array_key_exists('filter', $input)) {
            throw new RequestParamMissingException(
                sprintf('Bad Request: %s', 'filter')
            );
        }

        $objectId = (int) $input['filter'];
        $libitem  = $this->modelFactory->createSong($objectId);

        if ($libitem->isNew()) {
            $response->getBody()->write(
                $output->error(
                    $apiVersion,
                    ErrorCodeEnum::BAD_REQUEST,
                    sprintf('Bad Request: %s', $objectId),
                    self::ACTION,
                    'filter'
                )
            );

            return $response;
        }

        $results = [
            'object_id' => ($apiVersion >= 8) ? (string) $objectId : $objectId,
            'object_type' => 'song',
            'plugin' => [],
        ];

        $databaseLyrics = $libitem->get_lyrics(true);
        if (!empty($databaseLyrics)) {
            $results['plugin']['database'] = $databaseLyrics;
        }

        if ((int) ($input['plugins'] ?? 1) === 1) {
            foreach (Plugin::get_plugins(PluginTypeEnum::LYRIC_RETRIEVER) as $pluginName) {
                $plugin = new Plugin($pluginName);
                if ($plugin->_plugin instanceof PluginGetLyricsInterface && $plugin->load($user)) {
                    $lyrics = $plugin->_plugin->get_lyrics($libitem);

                    // save the lyrics if not set before
                    if ($lyrics && !empty($lyrics['text'])) {
                        $results['plugin'][$pluginName] = $lyrics;
                    }
                }
            }
        }

        $response->getBody()->write(
            $output->keyedArray($apiVersion, $results)
        );

        return $response;
    }
}
