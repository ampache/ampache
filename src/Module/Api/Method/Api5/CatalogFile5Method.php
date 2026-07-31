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
use Ampache\Module\Api\Exception\ErrorCodeEnum;
use Ampache\Module\Api\Method\Exception\AccessDeniedException;
use Ampache\Module\Api\Method\Exception\AccessFailedException;
use Ampache\Module\Api\Method\Exception\RequestParamMissingException;
use Ampache\Module\Api\Method\Exception\ResultEmptyException;
use Ampache\Module\Api\Method\MethodInterface;
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
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;

/**
 * Performs add/clean/verify/remove on a single local catalog file.
 *
 * Version 5 reads the catalog id from `catalog` only and checks the access level before the
 * parameters, so it keeps a method of its own.
 */
final class CatalogFile5Method implements MethodInterface
{
    public const string ACTION = 'catalog_file';

    public function __construct(
        private ConfigContainerInterface $configContainer,
        private PrivilegeCheckerInterface $privilegeChecker,
        private StreamFactoryInterface $streamFactory,
    ) {}

    /**
     * catalog_file
     * MINIMUM_API_VERSION=420000
     *
     * Perform actions on local catalog files.
     * Single file versions of catalog add, clean and verify.
     * Make sure you remember to urlencode those file names!
     *
     * file = (string) urlencode(FULL path to local file)
     * task = (string) 'add', 'clean', 'verify', 'remove' (can be comma separated)
     * catalog = (integer) $catalog_id
     *
     * @param array{
     *     file: string,
     *     task: string,
     *     catalog: int,
     *     api_format: string,
     *     auth: string,
     * } $input
     * @param 5 $apiVersion
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
                $user->id
            )
        ) {
            throw new AccessFailedException(
                sprintf('Require: %s', AccessLevelEnum::CONTENT_MANAGER->value)
            );
        }

        foreach (['catalog', 'file', 'task'] as $parameter) {
            if (!array_key_exists($parameter, $input)) {
                throw new RequestParamMissingException(
                    sprintf('Bad Request: %s', $parameter)
                );
            }
        }

        $file = html_entity_decode($input['file']);
        $task = explode(',', html_entity_decode((string) ($input['task'])));

        // confirm that a valid task is going to happen
        if (
            !$this->configContainer->get(ConfigurationKeyEnum::DELETE_FROM_DISK)
            && in_array('remove', $task)
        ) {
            throw new AccessDeniedException(
                'Enable: delete_from_disk'
            );
        }

        if (!file_exists($file) && !in_array('clean', $task)) {
            throw new ResultEmptyException(
                $file,
                'file'
            );
        }

        $output_task = '';
        foreach ($task as $item) {
            if (!in_array($item, ['add', 'clean', 'verify', 'remove'])) {
                return $response->withBody(
                    $this->streamFactory->createStream(
                        $output->error(
                            $apiVersion,
                            ErrorCodeEnum::BAD_REQUEST,
                            sprintf('Bad Request: %s', $item),
                            self::ACTION,
                            'task'
                        )
                    )
                );
            }

            $output_task .= $item . ', ';
        }

        $output_task = rtrim($output_task, ', ');
        $catalog_id  = (int) $input['catalog'];
        $catalog     = Catalog::create_from_id($catalog_id);
        if ($catalog === null) {
            throw new ResultEmptyException(
                (string) $catalog_id,
                'catalog'
            );
        }

        switch ($catalog->gather_types) {
            case 'podcast':
                $type  = 'podcast_episode';
                $media = new Podcast_Episode(Catalog::get_id_from_file($file, $type));
                break;
            case 'video':
                $type  = 'video';
                $media = new Video(Catalog::get_id_from_file($file, $type));
                break;
            case 'music':
            default:
                $type  = 'song';
                $media = new Song(Catalog::get_id_from_file($file, $type));
                break;
        }

        if ($catalog->catalog_type != 'local') {
            return $response->withBody(
                $this->streamFactory->createStream(
                    $output->error(
                        $apiVersion,
                        ErrorCodeEnum::NOT_FOUND,
                        'Not Found',
                        self::ACTION,
                        'catalog'
                    )
                )
            );
        }

        foreach ($task as $item) {
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
                        $catalog->add_file($file, []);
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

        return $response->withBody(
            $this->streamFactory->createStream(
                $output->success($apiVersion, 'successfully started: ' . $output_task . ' for ' . $file)
            )
        );
    }
}
