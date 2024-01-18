<?php

declare(strict_types=0);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2023
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

namespace Ampache\Module\Application\Batch;

use Ampache\Module\Util\RequestParserInterface;
use Ampache\Repository\Model\library_item;
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
use Ampache\Repository\SongRepositoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;

final class DefaultAction implements ApplicationActionInterface
{
    public const REQUEST_KEY = 'default';

    private RequestParserInterface $requestParser;

    private ModelFactoryInterface $modelFactory;

    private LoggerInterface $logger;

    private ZipHandlerInterface $zipHandler;

    private FunctionCheckerInterface $functionChecker;

    private SongRepositoryInterface $songRepository;

    public function __construct(
        RequestParserInterface $requestParser,
        ModelFactoryInterface $modelFactory,
        LoggerInterface $logger,
        ZipHandlerInterface $zipHandler,
        FunctionCheckerInterface $functionChecker,
        SongRepositoryInterface $songRepository
    ) {
        $this->requestParser   = $requestParser;
        $this->modelFactory    = $modelFactory;
        $this->logger          = $logger;
        $this->zipHandler      = $zipHandler;
        $this->functionChecker = $functionChecker;
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
        $default_name = 'Unknown';
        $name         = $default_name;
        $action       = $this->requestParser->getFromRequest('action');
        $flat_path    = (in_array($action, array('browse', 'playlist', 'tmp_playlist')));
        $object_type  = ($action == 'browse')
            ? $this->requestParser->getFromRequest('type')
            : $action;

        if (!$this->zipHandler->isZipable($object_type)) {
            $this->logger->error(
                'Object type `' . $object_type . '` is not allowed to be zipped.',
                [LegacyLogger::CONTEXT_TYPE => __CLASS__]
            );
            throw new AccessDeniedException();
        }

        if (InterfaceImplementationChecker::is_playable_item($object_type)) {
            $object_id = (int)$this->requestParser->getFromRequest('id');
            $this->logger->debug(
                'Requested item ' . $object_id,
                [LegacyLogger::CONTEXT_TYPE => __CLASS__]
            );

            $className = ObjectTypeToClassNameMapper::map($object_type);
            /** @var class-string<library_item> $className */
            $libitem = new $className($object_id);
            if ($libitem->isNew() === false) {
                if (method_exists($libitem, 'format')) {
                    $libitem->format();
                }
                $name      = (string)$libitem->get_fullname();
                $media_ids = array_merge($media_ids, $libitem->get_medias());
            }
        } else {
            // Switch on the actions
            switch ($action) {
                case 'tmp_playlist':
                    $media_ids = Core::get_global('user')->playlist->get_items();
                    $name      = Core::get_global('user')->username . ' - Playlist';
                    break;
                case 'browse':
                    $object_id        = (int)$this->requestParser->getFromRequest('browse_id');
                    $browse           = $this->modelFactory->createBrowse($object_id);
                    $browse_media_ids = $browse->get_saved();
                    foreach ($browse_media_ids as $media_id) {
                        switch ($object_type) {
                            case 'album':
                                $album = $this->modelFactory->createAlbum($media_id);
                                if ($album->isNew() === false) {
                                    $media_ids = array_merge($media_ids, $this->songRepository->getByAlbum($album->id));
                                }
                                break;
                            case 'album_disk':
                                $albumDisk = $this->modelFactory->createAlbumDisk($media_id);
                                if ($albumDisk->isNew() === false) {
                                    $media_ids = array_merge($media_ids, $this->songRepository->getByAlbumDisk($albumDisk->id));
                                }
                                break;
                            case 'song':
                                $song = $this->modelFactory->createSong($media_id);
                                if ($song->isNew() === false) {
                                    $media_ids[] = $media_id;
                                }
                                break;
                            case 'video':
                                $video = $this->modelFactory->createVideo($media_id);
                                if ($video->isNew() === false) {
                                    $media_ids[] = ['object_type' => 'Video', 'object_id' => $media_id];
                                }
                                break;
                        } // switch on type
                    } // foreach media_id
                    $name = 'Batch-' . get_datetime(time(), 'short', 'none', 'y-MM-dd');
                    break;
            }
        }

        if (!defined('NO_SESSION') && !User::stream_control($media_ids)) {
            $this->logger->notice(
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
        $media_files = $this->getMediaFiles($media_ids);
        if (is_array($media_files['0'])) {
            set_memory_limit($media_files['1'] + 32);
            $this->zipHandler->zip($name, $media_files['0'], $flat_path);
        }

        return null;
    }

    /**
     * Takes an array of media ids and returns an array of the actual filenames
     *
     * @param array $media_ids Media IDs.
     * @return array
     */
    private function getMediaFiles(array $media_ids): array
    {
        $media_files = [];
        $total_size  = 0;
        foreach ($media_ids as $element) {
            if (is_array($element)) {
                if (isset($element['object_type'])) {
                    $type    = $element['object_type'];
                    $mediaid = $element['object_id'];
                } else {
                    $type    = array_shift($element);
                    $mediaid = array_shift($element);
                }
                $className = ObjectTypeToClassNameMapper::map($type);
                /** @var class-string<library_item> $className */
                $media = new $className($mediaid);
            } else {
                $media = $this->modelFactory->createSong((int) $element);
            }
            if ($media->isNew()) {
                continue;
            }
            if ($media->enabled) {
                $total_size = ((int)$total_size) + ($media->size ?? 0);
                $dirname    = '';
                $parent     = $media->get_parent();
                if ($parent != null) {
                    $className = ObjectTypeToClassNameMapper::map($parent['object_type']);
                    /** @var class-string<library_item> $className */
                    $pobj = new $className($parent['object_id']);
                    $pobj->format();
                    $dirname = (string)$pobj->get_fullname();
                }
                if (!empty($dirname) && !array_key_exists($dirname, $media_files)) {
                    $media_files[$dirname] = [];
                }
                $media_files[$dirname][] = Core::conv_lc_file($media->file);
            }
        }

        return array(
            $media_files,
            $total_size
        );
    }
}
