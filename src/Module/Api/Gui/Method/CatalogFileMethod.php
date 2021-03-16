<?php
/*
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright 2001 - 2020 Ampache.org
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

declare(strict_types=0);

namespace Ampache\Module\Api\Gui\Method;

use Ampache\Config\AmpConfig;
use Ampache\Config\ConfigContainerInterface;
use Ampache\Module\Api\Gui\Authentication\GatekeeperInterface;
use Ampache\Module\Api\Gui\Method\Exception\AccessDeniedException;
use Ampache\Module\Api\Gui\Method\Exception\FunctionDisabledException;
use Ampache\Module\Api\Gui\Method\Exception\RequestParamMissingException;
use Ampache\Module\Api\Gui\Method\Exception\ResultEmptyException;
use Ampache\Module\Api\Gui\Output\ApiOutputInterface;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Song\Deletion\SongDeleterInterface;
use Ampache\Repository\Model\Catalog;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\Model\Podcast_Episode;
use Ampache\Repository\Model\Song;
use Ampache\Repository\Model\Video;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;

final class CatalogFileMethod implements MethodInterface
{
    public const ACTION = 'catalog_file';

    private SongDeleterInterface $songDeleter;

    private ModelFactoryInterface $modelFactory;

    private ConfigContainerInterface $configContainer;

    private StreamFactoryInterface $streamFactory;

    public function __construct(
        SongDeleterInterface $songDeleter,
        ModelFactoryInterface $modelFactory,
        ConfigContainerInterface $configContainer,
        StreamFactoryInterface $streamFactory
    ) {
        $this->songDeleter     = $songDeleter;
        $this->modelFactory    = $modelFactory;
        $this->configContainer = $configContainer;
        $this->streamFactory   = $streamFactory;
    }

    /**
     * MINIMUM_API_VERSION=420000
     *
     * Perform actions on local catalog files.
     * Single file versions of catalog add, clean and verify.
     * Make sure you remember to urlencode those file names!
     *
     * @param array $input
     * file    = (string) urlencode(FULL path to local file)
     * task    = (string) 'add', 'clean', 'verify', 'remove'
     * catalog = (integer) $catalog_id)
     *
     * @return ResponseInterface
     *
     * @throws FunctionDisabledException
     * @throws AccessDeniedException
     * @throws RequestParamMissingException
     * @throws ResultEmptyException
     */
    public function handle(
        GatekeeperInterface $gatekeeper,
        ResponseInterface $response,
        ApiOutputInterface $output,
        array $input
    ): ResponseInterface {
        $task = (string) $input['task'];
        if ($task === 'remove' && !AmpConfig::get('delete_from_disk')) {
            throw new FunctionDisabledException(
                T_('Enable: delete_from_disk')
            );
        }

        if ($gatekeeper->mayAccess(AccessLevelEnum::TYPE_INTERFACE, AccessLevelEnum::LEVEL_CONTENT_MANAGER) === false) {
            throw new AccessDeniedException(
                T_('Require: 50')
            );
        }

        foreach (['catalog', 'file', 'task'] as $key) {
            if (!array_key_exists($key, $input)) {
                throw new RequestParamMissingException(
                    sprintf(T_('Bad Request: %s'), $key)
                );
            }
        }

        $file = (string) html_entity_decode($input['file']);
        // confirm the correct data
        if (!in_array($task, ['add', 'clean', 'verify', 'remove'])) {
            throw new RequestParamMissingException(
                sprintf(T_('Bad Request: %s'), $task)
            );
        }
        if (!file_exists($file) && $task !== 'clean') {
            throw new ResultEmptyException(
                sprintf(T_('Not Found: %s'), $file)
            );
        }
        $catalog_id = (int) $input['catalog'];
        $catalog    = Catalog::create_from_id($catalog_id);
        if ($catalog->id < 1) {
            throw new ResultEmptyException(
                sprintf(T_('Not Found: %s'), $catalog_id)
            );
        }
        switch ($catalog->gather_types) {
            case 'podcast':
                $type  = 'podcast_episode';
                $media = new Podcast_Episode(Catalog::get_id_from_file($file, $type));
                break;
            case 'clip':
            case 'tvshow':
            case 'movie':
            case 'personal_video':
                $type  = 'video';
                $media = new Video(Catalog::get_id_from_file($file, $type));
                break;
            case 'music':
            default:
                $type  = 'song';
                $media = new Song(Catalog::get_id_from_file($file, $type));
                break;
        }

        if ($catalog->catalog_type == 'local') {
            define('API', true);
            unset($SSE_OUTPUT);
            switch ($task) {
                case 'clean':
                    $catalog->clean_file($file, $type);
                    break;
                case 'verify':
                    Catalog::update_media_from_tags($media, array($type));
                    break;
                case 'add':
                    $catalog->add_file($file);
                    break;
                case 'remove':
                    $media->remove();
                    break;
            }

            return $response->withBody(
                $this->streamFactory->createStream(
                    $output->success(
                        sprintf('successfully started: %s for %s', $task, $file)
                    )
                )
            );
        } else {
            throw new ResultEmptyException(
                T_('Not Found')
            );
        }
    }
}
