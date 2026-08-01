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
use Ampache\Module\Api\Method\Exception\ResultEmptyException;
use Ampache\Module\Api\Output\ApiOutputInterface;
use Ampache\Module\Catalog\Catalog;
use Ampache\Module\Database\Query\Browse;
use Ampache\Module\Util\ObjectTypeToClassNameMapper;
use Ampache\Repository\Model\Album;
use Ampache\Repository\Model\AlbumDisk;
use Ampache\Repository\Model\Artist;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\Model\Podcast;
use Ampache\Repository\Model\User;
use Psr\Http\Message\ResponseInterface;

/**
 * Returns the children of a parent object in a folder traversal/browse style
 */
final class BrowseMethod implements MethodInterface
{
    public const string ACTION = 'browse';

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
     * MINIMUM_API_VERSION=6.0.0
     *
     * Return children of a parent object in a folder traversal/browse style
     * If you don't send any parameters you'll get a catalog list (the 'root' path)
     * Catalog ID is optional on 'album_artist', 'artist', 'album', 'album_disk', 'podcast' from api version 8;
     * send it to restrict the children to a single catalog, omit it to get them from every catalog you can see.
     * Api version 6 still requires it on those types.
     *
     * filter  = (string) object_id //optional
     * type    = (string) 'root', 'catalog', 'album_artist', 'artist', 'album', 'album_disk', 'podcast' //optional
     * catalog = (integer) catalog ID you are browsing //optional
     * add     = $browse->set_api_filter(date) //optional
     * update  = $browse->set_api_filter(date) //optional
     * offset  = (integer) //optional
     * limit   = (integer) //optional
     * cond    = (string) Apply additional filters to the browse //optional
     * sort    = (string) sort name or comma separated key pair //optional
     *
     * @param array{
     *     filter?: string,
     *     type?: string,
     *     catalog?: int,
     *     add?: string,
     *     update?: string,
     *     offset?: int,
     *     limit?: int,
     *     cond?: string,
     *     sort?: string,
     *     api_format: string,
     *     auth: string,
     * } $input
     * @throws AccessDeniedException|RequestParamMissingException|ResultEmptyException
     */
    public function handle(
        GatekeeperInterface $gatekeeper,
        ResponseInterface $response,
        ApiOutputInterface $output,
        array $input,
        User $user,
        int $apiVersion,
    ): ResponseInterface {
        $catalogId  = (isset($input['catalog'])) ? (int) $input['catalog'] : null;
        $objectId   = (isset($input['filter'])) ? (int) $input['filter'] : null;
        $objectType = $input['type'] ?? 'root';

        if (!$objectId && $objectType === 'catalog') {
            $objectId = $catalogId;
        }

        if (
            $objectType === 'podcast'
            && !$this->configContainer->get(ConfigurationKeyEnum::PODCAST)
        ) {
            throw new AccessDeniedException(
                'Enable: podcast'
            );
        }

        // confirm the correct data (album_disk is api version 8 only)
        if (
            !in_array(strtolower((string) $objectType), ['root', 'catalog', 'album_artist', 'artist', 'album', 'album_disk', 'podcast'])
            || ($apiVersion < 8 && strtolower((string) $objectType) === 'album_disk')
        ) {
            return $this->writeTypeError($response, $output, $apiVersion, (string) $objectType);
        }

        $browse = $this->modelFactory->createBrowse(null, false);
        $browse->set_user_id($user);

        if ($objectType === 'root') {
            $childType  = 'catalog';
            $outputType = 'catalog';

            $gatherTypes = ['music'];
            if ($this->configContainer->get(ConfigurationKeyEnum::PODCAST)) {
                $gatherTypes[] = 'podcast';
            }

            if ($this->configContainer->get('video')) {
                $gatherTypes[] = 'video';
            }

            $browse->set_type($outputType);
            $browse->set_filter('gather_types', $gatherTypes);
            $browse->set_filter('user', $user->getId());
        } elseif ($objectType === 'catalog') {
            if ($objectId === null) {
                throw new RequestParamMissingException(
                    sprintf('Bad Request: %s', 'filter')
                );
            }

            $catalog = Catalog::create_from_id($objectId);
            if ($catalog === null) {
                throw new ResultEmptyException(
                    (string) $objectId
                );
            }

            switch ((string) $catalog->gather_types) {
                case 'video':
                    $outputType = 'video';
                    $gatherType = 'video';
                    $browse->set_type('video');
                    break;
                case 'music':
                    $outputType = 'artist';
                    $gatherType = 'music';
                    $browse->set_type('album_artist');
                    break;
                case 'podcast':
                    $outputType = 'podcast';
                    $gatherType = 'podcast';
                    $browse->set_type('podcast');
                    break;
                default:
                    $response->getBody()->write(
                        $output->error(
                            $apiVersion,
                            ErrorCodeEnum::BAD_REQUEST,
                            sprintf('Bad Request: %s', $catalogId),
                            self::ACTION,
                            'catalog'
                        )
                    );

                    return $response;
            }

            $childType = $outputType;

            $browse->set_sort_order(html_entity_decode((string) ($input['sort'] ?? '')), ['name', 'ASC']);
            $browse->set_filter('gather_type', $gatherType);
            $browse->set_filter('catalog', $catalog->id);
        } else {
            if (!array_key_exists('filter', $input)) {
                throw new RequestParamMissingException(
                    sprintf('Bad Request: %s', 'filter')
                );
            }

            // The catalog narrows the children rather than addressing the parent, so api 8 treats it as an
            // optional filter and browses every catalog the user can see when it is absent. Api 6 is served
            // by Ampache7 as well and requires it, so that version keeps the parameter mandatory.
            if ($apiVersion < 8 && !array_key_exists('catalog', $input)) {
                throw new RequestParamMissingException(
                    sprintf('Bad Request: %s', 'catalog')
                );
            }

            $catalog = null;
            if ($catalogId !== null) {
                $catalog = Catalog::create_from_id($catalogId);
                if ($catalog === null) {
                    throw new ResultEmptyException(
                        (string) $catalogId,
                        'catalog'
                    );
                }
            }

            $className = ObjectTypeToClassNameMapper::map((string) $objectType);
            if ($className === $objectType || !$objectId) {
                return $this->writeTypeError($response, $output, $apiVersion, (string) $objectType);
            }

            /** @var Album|AlbumDisk|Artist|Podcast $item */
            $item = new $className($objectId);
            if ($item->isNew()) {
                throw new ResultEmptyException(
                    (string) $objectId
                );
            }

            // for sub objects you want to browse their children
            [$objectType, $outputType, $filterType, $sort, $order] = $this->resolveChildBrowse($browse, (string) $objectType);

            $childType = $outputType;

            $browse->set_sort_order(html_entity_decode((string) ($input['sort'] ?? '')), [$sort, $order]);

            if (!empty($filterType)) {
                $browse->set_filter($filterType, $item->getId());
            }

            if ($catalog !== null) {
                $browse->set_filter('catalog', $catalog->id);
            }
        }

        $browse->set_api_filter('add', $input['add'] ?? '');
        $browse->set_api_filter('update', $input['update'] ?? '');
        $browse->set_conditions(html_entity_decode((string) ($input['cond'] ?? '')));

        $objects = $browse->get_objects();
        if (empty($objects)) {
            $response->getBody()->write(
                $output->writeEmpty($apiVersion, 'browse')
            );

            return $response;
        }

        $sortOrder = $browse->get_sort();
        $results   = Catalog::get_name_array(
            $objects,
            $outputType,
            $sortOrder['name'] ?? 'name',
            $sortOrder['order'] ?? 'ASC'
        );
        if (empty($results)) {
            $response->getBody()->write(
                $output->writeEmpty($apiVersion, 'browse')
            );

            return $response;
        }

        $output->setOffset($apiVersion, (int) ($input['offset'] ?? 0));
        $output->setLimit($apiVersion, (int) ($input['limit'] ?? 0));

        $response->getBody()->write(
            $output->browses($apiVersion, $results, (string) $objectType, $childType, $objectId, $catalogId)
        );

        return $response;
    }

    /**
     * Points the browse at the children of the requested object
     *
     * @return array{0: string, 1: string, 2: string, 3: string, 4: string}
     */
    private function resolveChildBrowse(Browse $browse, string $objectType): array
    {
        switch ($objectType) {
            case 'artist':
            case 'album_artist':
                $browse->set_type('album');

                $originalYear = ($this->configContainer->get('use_original_year'))
                    ? 'original_year'
                    : 'year';

                [$sort, $order] = match ((string) $this->configContainer->get(ConfigurationKeyEnum::ALBUM_SORT)) {
                    'name_asc' => ['name', 'ASC'],
                    'name_desc' => ['name', 'DESC'],
                    'year_asc' => [$originalYear, 'ASC'],
                    'year_desc' => [$originalYear, 'DESC'],
                    default => ['name_' . $originalYear, 'ASC'],
                };

                return ['artist', 'album', 'album_artist', $sort, $order];
            case 'album':
                $browse->set_type('song');

                return ['album', 'song', 'album', 'album', 'ASC'];
            case 'album_disk':
                $browse->set_type('song');

                return ['album_disk', 'song', 'album_disk', 'track', 'ASC'];
            case 'podcast':
                $browse->set_type('podcast_episode');

                return ['podcast', 'podcast_episode', 'podcast', 'podcast', 'ASC'];
            default:
                return [$objectType, $objectType, '', 'name', 'ASC'];
        }
    }

    private function writeTypeError(
        ResponseInterface $response,
        ApiOutputInterface $output,
        int $apiVersion,
        string $objectType,
    ): ResponseInterface {
        $response->getBody()->write(
            $output->error(
                $apiVersion,
                ErrorCodeEnum::BAD_REQUEST,
                sprintf('Bad Request: %s', $objectType),
                self::ACTION,
                'type'
            )
        );

        return $response;
    }
}
