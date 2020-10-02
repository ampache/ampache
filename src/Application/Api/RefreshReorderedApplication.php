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

namespace Ampache\Application\Api;

use Ampache\Application\ApplicationInterface;
use Ampache\Model\ModelFactoryInterface;
use Ampache\Module\System\LegacyLogger;
use Ampache\Module\Util\RequestParserInterface;
use Psr\Log\LoggerInterface;

final class RefreshReorderedApplication implements ApplicationInterface
{
    public const ACTION_REFRESH_PLAYLIST_MEDIAS = 'refresh_playlist_medias';
    public const ACTION_REFRESH_ALBUM_SONGS     = 'refresh_album_songs';

    private LoggerInterface $logger;

    private ModelFactoryInterface $modelFactory;

    private RequestParserInterface $requestParser;

    public function __construct(
        LoggerInterface $logger,
        ModelFactoryInterface $modelFactory,
        RequestParserInterface $requestParser
    ) {
        $this->logger        = $logger;
        $this->modelFactory  = $modelFactory;
        $this->requestParser = $requestParser;
    }

    public function run(): void
    {
        $action    = $this->requestParser->getFromRequest('action');
        $object_id = $this->requestParser->getFromRequest('id');

        $this->logger->debug(
            'Called for action: {' . $action . '}',
            [LegacyLogger::CONTEXT_TYPE => __CLASS__]
        );

        $browse = $this->modelFactory->createBrowse();

        // Switch on the actions
        switch ($action) {
            case static::ACTION_REFRESH_PLAYLIST_MEDIAS:
                $playlist = $this->modelFactory->createPlaylist((int) $object_id);
                $playlist->format();

                $object_ids = $playlist->get_items();

                $browse->set_type('playlist_media');
                $browse->add_supplemental_object('playlist', $playlist->id);
                $browse->set_static_content(true);
                $browse->show_objects($object_ids);
                $browse->store();
                break;
            case static::ACTION_REFRESH_ALBUM_SONGS:
                $browse->set_show_header(true);
                $browse->set_type('song');
                $browse->set_simple_browse(true);
                $browse->set_filter('album', $object_id);
                $browse->set_sort('track', 'ASC');
                $browse->get_objects();
                echo "<div id='browse_content_song' class='browse_content'>";
                $browse->show_objects(null, true); // true argument is set to show the reorder column
                $browse->store();
                echo "</div>";
                break;
        }
    }
}
