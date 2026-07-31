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
use Ampache\Module\Api\Method\Exception\ResultEmptyException;
use Ampache\Module\Api\Output\ApiOutputInterface;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Module\Authorization\Check\PrivilegeCheckerInterface;
use Ampache\Module\Catalog\Catalog_local;
use Ampache\Repository\Model\Album;
use Ampache\Repository\Model\Artist;
use Ampache\Repository\Model\Catalog;
use Ampache\Repository\Model\Podcast_Episode;
use Ampache\Repository\Model\Song;
use Ampache\Repository\Model\User;
use Ampache\Repository\Model\Video;
use Exception;
use Psr\Http\Message\ResponseInterface;

/**
 * Performs add/clean/verify/remove on a single local catalog file
 *
 * The two live api versions only differ in how they name the catalog id: version 6 reports it as
 * `catalog` and version 8 as `filter`, each accepting the other as an alias. The version classes
 * supply that pair of names; everything else is shared.
 */
abstract class AbstractCatalogFileMethod implements MethodInterface
{
    public const string ACTION = 'catalog_file';

    public const string REST_ACTION = 'file';

    // the alias the version prefers when both names are supplied; overridden per version
    protected const string FILTER_ALIAS = 'catalog';

    // the name the version reports the catalog id under; overridden per version
    protected const string FILTER_KEY = 'filter';

    private ConfigContainerInterface $configContainer;
    private PrivilegeCheckerInterface $privilegeChecker;

    public function __construct(
        ConfigContainerInterface $configContainer,
        PrivilegeCheckerInterface $privilegeChecker,
    ) {
        $this->configContainer  = $configContainer;
        $this->privilegeChecker = $privilegeChecker;
    }

    /**
     * MINIMUM_API_VERSION=420000
     *
     * Perform actions on local catalog files.
     * Single file versions of catalog add, clean and verify.
     * Make sure you remember to urlencode those file names!
     *
     * file    = (string) urlencode(FULL path to local file)
     * task    = (string) 'add', 'clean', 'verify', 'remove' (can be comma separated)
     * catalog = (integer) $catalog_id
     *
     * @param array{
     *     file?: string,
     *     task?: string,
     *     filter?: int,
     *     catalog?: int,
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

        $filter = $input[static::FILTER_ALIAS] ?? $input[static::FILTER_KEY] ?? null;
        if ($filter === null) {
            throw new RequestParamMissingException(
                sprintf('Bad Request: %s', static::FILTER_KEY)
            );
        }

        foreach (['file', 'task'] as $parameter) {
            if (!array_key_exists($parameter, $input)) {
                throw new RequestParamMissingException(
                    sprintf('Bad Request: %s', $parameter)
                );
            }
        }

        $file  = html_entity_decode((string) $input['file']);
        $tasks = explode(',', html_entity_decode((string) $input['task']));

        // confirm that a valid task is going to happen
        if (
            in_array('remove', $tasks)
            && !$this->configContainer->get(ConfigurationKeyEnum::DELETE_FROM_DISK)
        ) {
            throw new AccessDeniedException(
                'Enable: delete_from_disk'
            );
        }

        if (!file_exists($file) && !in_array('clean', $tasks)) {
            throw new ResultEmptyException(
                $file,
                'file'
            );
        }

        foreach ($tasks as $item) {
            if (!in_array($item, ['add', 'clean', 'verify', 'remove'])) {
                $response->getBody()->write(
                    $output->error(
                        $apiVersion,
                        ErrorCodeEnum::BAD_REQUEST,
                        sprintf('Bad Request: %s', $item),
                        static::ACTION,
                        'task'
                    )
                );

                return $response;
            }
        }

        $outputTask = implode(', ', $tasks);
        $catalogId  = (int) $filter;
        $catalog    = Catalog::create_from_id($catalogId);
        if ($catalog === null) {
            throw new ResultEmptyException(
                (string) $catalogId,
                'catalog'
            );
        }

        if ($catalog->catalog_type !== 'local') {
            $response->getBody()->write(
                $output->error(
                    $apiVersion,
                    ErrorCodeEnum::NOT_FOUND,
                    'Not Found',
                    static::ACTION,
                    'catalog'
                )
            );

            return $response;
        }

        [$type, $media] = match ($catalog->gather_types) {
            'podcast' => ['podcast_episode', new Podcast_Episode(Catalog::get_id_from_file($file, 'podcast_episode'))],
            'video' => ['video', new Video(Catalog::get_id_from_file($file, 'video'))],
            default => ['song', new Song(Catalog::get_id_from_file($file, 'song'))],
        };

        foreach ($tasks as $item) {
            switch ($item) {
                case 'clean':
                    if ($media->isNew() === false) {
                        /** @var Catalog_local $catalog */
                        $catalog->clean_file($file, $type);
                    }
                    break;
                case 'verify':
                    if ($media->isNew() === false) {
                        Catalog::update_media_from_tags($media, [$type]);
                    }
                    break;
                case 'add':
                    if ($media->isNew()) {
                        /** @var Catalog_local $catalog */
                        try {
                            $catalog->add_file($file, []);
                        } catch (Exception) {
                            $response->getBody()->write(
                                $output->error(
                                    $apiVersion,
                                    ErrorCodeEnum::GENERIC_ERROR,
                                    sprintf('Bad Request: %s', $file),
                                    static::ACTION,
                                    'file'
                                )
                            );

                            return $response;
                        }
                    }
                    break;
                case 'remove':
                    if ($media->isNew() === false) {
                        $media->remove();
                    }
                    break;
            }
        }

        // update the counts too
        if ($media instanceof Song) {
            Album::update_album_count($media->album);
            Artist::update_table_counts();
        }

        $response->getBody()->write(
            $output->success($apiVersion, 'successfully started: ' . $outputTask . ' for ' . $file)
        );

        return $response;
    }
}
