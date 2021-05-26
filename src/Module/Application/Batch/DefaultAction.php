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

namespace Ampache\Module\Application\Batch;

use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\Model\User;
use Ampache\Module\Application\ApplicationActionInterface;
use Ampache\Module\Application\Exception\AccessDeniedException;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\Check\FunctionCheckerInterface;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\System\Core;
use Ampache\Module\System\LegacyLogger;
use Ampache\Module\Util\InterfaceImplementationChecker;
use Ampache\Module\Util\ObjectTypeToClassNameMapper;
use Ampache\Module\Util\ZipHandlerInterface;
use Ampache\Repository\AlbumRepositoryInterface;
use Ampache\Repository\SongRepositoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;

final class DefaultAction implements ApplicationActionInterface
{
    public const REQUEST_KEY = 'default';

    private ModelFactoryInterface $modelFactory;

    private LoggerInterface $logger;

    private ZipHandlerInterface $zipHandler;

    private FunctionCheckerInterface $functionChecker;

    private AlbumRepositoryInterface $albumRepository;

    private SongRepositoryInterface $songRepository;

    public function __construct(
        ModelFactoryInterface $modelFactory,
        LoggerInterface $logger,
        ZipHandlerInterface $zipHandler,
        FunctionCheckerInterface $functionChecker,
        AlbumRepositoryInterface $albumRepository,
        SongRepositoryInterface $songRepository
    ) {
        $this->modelFactory    = $modelFactory;
        $this->logger          = $logger;
        $this->zipHandler      = $zipHandler;
        $this->functionChecker = $functionChecker;
        $this->albumRepository = $albumRepository;
        $this->songRepository  = $songRepository;
    }

    public function run(ServerRequestInterface $request, GuiGatekeeperInterface $gatekeeper): ?ResponseInterface
    {
        ob_end_clean();
        if (
            !defined('NO_SESSION') &&
            !$this->functionChecker->check(AccessLevelEnum::FUNCTION_BATCH_DOWNLOAD)
        ) {
            throw new AccessDeniedException();
        }

        /* Drop the normal Time limit constraints, this can take a while */
        set_time_limit(0);

        $media_ids    = [];
        $default_name = 'Unknown.zip';
        $object_type  = (string) scrub_in(Core::get_request('action'));
        $name         = $default_name;

        if ($object_type == 'browse') {
            $object_type = Core::get_request('type');
        }

        if (!$this->zipHandler->isZipable($object_type)) {
            $this->logger->error(
                'Object type `' . $object_type . '` is not allowed to be zipped.',
                [LegacyLogger::CONTEXT_TYPE => __CLASS__]
            );
            throw new AccessDeniedException();
        }

        if (InterfaceImplementationChecker::is_playable_item($object_type) && $object_type !== 'album') {
            $object_id = $_REQUEST['id'];
            if (!is_array($object_id)) {
                $object_id = [$object_id];
            }
            $media_ids = [];
            foreach ($object_id as $item) {
                $this->logger->debug(
                    'Requested item ' . $item,
                    [LegacyLogger::CONTEXT_TYPE => __CLASS__]
                );
                $class_name = ObjectTypeToClassNameMapper::map($object_type);
                $libitem    = new $class_name($item);
                if ($libitem->id) {
                    $libitem->format();
                    $name      = $libitem->get_fullname();
                    $media_ids = array_merge($media_ids, $libitem->get_medias());
                }
            }
        } else {
            // Switch on the actions
            switch ($object_type) {
                case 'tmp_playlist':
                    $media_ids = Core::get_global('user')->playlist->get_items();
                    $name      = Core::get_global('user')->username . ' - Playlist';
                    break;
                case 'album':
                    $albumList  = explode(',', $_REQUEST['id']);
                    $media_ids  = $this->albumRepository->getSongsGrouped($albumList);
                    $class_name = ObjectTypeToClassNameMapper::map($object_type);
                    $libitem    = new $class_name((int)$albumList[0]);
                    if ($libitem->id) {
                        $libitem->format();
                        $name = $libitem->get_fullname();
                    }
                    break;
                case 'browse':
                    $object_id        = (int) scrub_in(Core::get_post('browse_id'));
                    $browse           = $this->modelFactory->createBrowse($object_id);
                    $browse_media_ids = $browse->get_saved();
                    foreach ($browse_media_ids as $media_id) {
                        switch ($object_type) {
                            case 'album':
                                $album     = $this->modelFactory->createAlbum($media_id);
                                $media_ids = array_merge($media_ids, $this->songRepository->getByAlbum($album->id));
                                break;
                            case 'song':
                                $media_ids[] = $media_id;
                                break;
                            case 'video':
                                $media_ids[] = ['object_type' => 'Video', 'object_id' => $media_id];
                                break;
                        } // switch on type
                    } // foreach media_id
                    $name = 'Batch-' . get_datetime(time(), 'short', 'none', 'y-MM-dd');
                    break;
                default:
                    break;
            }
        }

        if (!User::stream_control($media_ids)) {
            $this->logger->info(
                'Access denied: Stream control failed for user ' . Core::get_global('user')->username,
                [LegacyLogger::CONTEXT_TYPE => __CLASS__]
            );
            throw new AccessDeniedException();
        }

        // Write/close session data to release session lock for this script.
        // This to allow other pages from the same session to be processed
        // Do NOT change any session variable after this call
        session_write_close();

        // Take whatever we've got and send the zip
        $song_files = $this->getMediaFiles($media_ids);
        if (is_array($song_files['0'])) {
            set_memory_limit($song_files['1'] + 32);
            $this->zipHandler->zip($name, $song_files['0']);
        }

        return null;
    }

    /**
     * Takes an array of media ids and returns an array of the actual filenames
     *
     * @param array $media_ids Media IDs.
     * @return array
     */
    private function getMediaFiles(array $media_ids)
    {
        $media_files = [];
        $total_size  = 0;
        foreach ($media_ids as $element) {
            if (is_array($element)) {
                if (isset($element['object_type'])) {
                    $type    = $element['object_type'];
                    $mediaid = $element['object_id'];
                } else {
                    $type      = array_shift($element);
                    $mediaid   = array_shift($element);
                }
                $class_name = ObjectTypeToClassNameMapper::map($type);
                $media      = new $class_name($mediaid);
            } else {
                $media = $this->modelFactory->createSong((int) $element);
            }
            if ($media->enabled) {
                $media->format();
                $total_size .= sprintf("%.2f", ($media->size / 1048576));
                $dirname = '';
                $parent  = $media->get_parent();
                if ($parent != null) {
                    $class_name = ObjectTypeToClassNameMapper::map($parent['object_type']);
                    $pobj       = new $class_name($parent['object_id']);
                    $pobj->format();
                    $dirname = $pobj->get_fullname();
                }
                if (!array_key_exists($dirname, $media_files)) {
                    $media_files[$dirname] = [];
                }
                array_push($media_files[$dirname], Core::conv_lc_file($media->file));
            }
        }

        return array($media_files, $total_size);
    }
}
