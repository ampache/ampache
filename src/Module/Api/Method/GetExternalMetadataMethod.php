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
use Ampache\Module\Api\Method\Exception\RequestParamMissingException;
use Ampache\Module\Api\Output\ApiOutputInterface;
use Ampache\Module\System\Plugin\PluginTypeEnum;
use Ampache\Plugin\PluginGetMetadataInterface;
use Ampache\Repository\Model\Album;
use Ampache\Repository\Model\Artist;
use Ampache\Repository\Model\Label;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\Model\Plugin;
use Ampache\Repository\Model\Song;
use Ampache\Repository\Model\User;
use Psr\Http\Message\ResponseInterface;

/**
 * Returns external plugin metadata for an object
 */
final class GetExternalMetadataMethod implements MethodInterface
{
    public const string ACTION = 'get_external_metadata';

    private ConfigContainerInterface $configContainer;
    private ModelFactoryInterface $modelFactory;

    public function __construct(
        ConfigContainerInterface $configContainer,
        ModelFactoryInterface $modelFactory,
    ) {
        $this->configContainer = $configContainer;
        $this->modelFactory    = $modelFactory;
    }

    /**
     * MINIMUM_API_VERSION=6.6.7
     *
     * Return External plugin metadata searching by object id and type
     *
     * filter = (string) album id, artist id or song id
     * type   = (string) 'song', 'artist', 'album', 'label'
     *
     * @param array{
     *     filter?: string,
     *     type?: string,
     *     api_format: string,
     *     auth: string,
     * } $input
     * @throws AccessDeniedException|RequestParamMissingException
     */
    public function handle(
        GatekeeperInterface $gatekeeper,
        ResponseInterface $response,
        ApiOutputInterface $output,
        array $input,
        User $user,
        int $apiVersion,
    ): ResponseInterface {
        foreach (['type', 'filter'] as $parameter) {
            if (!array_key_exists($parameter, $input)) {
                throw new RequestParamMissingException(
                    sprintf('Bad Request: %s', $parameter)
                );
            }
        }

        $type     = (string) $input['type'];
        $objectId = (int) $input['filter'];

        // confirm the correct data
        if (!in_array(strtolower($type), ['song', 'album', 'artist', 'label'])) {
            $response->getBody()->write(
                $output->error(
                    $apiVersion,
                    ErrorCodeEnum::BAD_REQUEST,
                    sprintf('Bad Request: %s', $type),
                    self::ACTION,
                    'type'
                )
            );

            return $response;
        }

        if (
            $type === 'label'
            && !$this->configContainer->get(ConfigurationKeyEnum::LABEL)
        ) {
            throw new AccessDeniedException(
                'Enable: label'
            );
        }

        // the in_array check above accepts any casing, but only the canonical names build a lookup
        switch ($type) {
            case 'song':
                $libitem = $this->modelFactory->createSong($objectId);
                $data    = [
                    'artist' => $libitem->get_parent_fullname(),
                    'song' => $libitem->get_fullname(),
                    'mb_trackid' => $libitem->mbid,
                ];
                break;
            case 'album':
                $libitem = $this->modelFactory->createAlbum($objectId);
                $data    = [
                    'albumartist' => $libitem->get_parent_fullname(),
                    'album' => $libitem->get_fullname(true),
                    'mb_albumid_group' => $libitem->mbid_group,
                ];
                break;
            case 'artist':
                $libitem = $this->modelFactory->createArtist($objectId);
                $data    = [
                    'artist' => $libitem->get_fullname(),
                    'mb_artistid' => $libitem->mbid,
                ];
                break;
            case 'label':
                $libitem = new Label($objectId);
                $data    = [
                    'label' => $libitem->get_fullname(),
                    'mb_labelid' => $libitem->mbid,
                ];
                break;
        }

        if (!isset($data) || !isset($libitem) || $libitem->isNew()) {
            $response->getBody()->write(
                $output->error(
                    $apiVersion,
                    ErrorCodeEnum::BAD_REQUEST,
                    sprintf('Bad Request: %s', $type),
                    self::ACTION,
                    'filter'
                )
            );

            return $response;
        }

        $results = [
            'object_id' => ($apiVersion >= 8) ? (string) $objectId : $objectId,
            'object_type' => $type,
            'plugin' => [],
        ];

        foreach (Plugin::get_plugins(PluginTypeEnum::METADATA_RETRIEVER) as $tagSource) {
            $plugin = new Plugin($tagSource);
            if (
                $plugin->_plugin instanceof PluginGetMetadataInterface
                && Plugin::get_plugin_version($plugin->_plugin->name) > 0
                && $plugin->load($user)
            ) {
                $metadata = $plugin->_plugin->get_metadata(['music', $type], $data);
                if ($metadata !== []) {
                    $results['plugin'][$tagSource] = $metadata;
                }
            }
        }

        if ($results['plugin'] === []) {
            $response->getBody()->write(
                $output->writeEmpty($apiVersion, $type)
            );

            return $response;
        }

        $response->getBody()->write(
            $output->keyedArray($apiVersion, $results)
        );

        return $response;
    }
}
