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

namespace Ampache\Module\Api\RefreshReordered;

use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Module\Application\ApplicationActionInterface;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\Util\RequestParserInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class RefreshPlaylistMediasAction implements ApplicationActionInterface
{
    public const REQUEST_KEY = 'refresh_playlist_medias';

    private RequestParserInterface $requestParser;

    private ModelFactoryInterface $modelFactory;

    public function __construct(
        RequestParserInterface $requestParser,
        ModelFactoryInterface $modelFactory
    ) {
        $this->requestParser = $requestParser;
        $this->modelFactory  = $modelFactory;
    }

    public function run(ServerRequestInterface $request, GuiGatekeeperInterface $gatekeeper): ?ResponseInterface
    {
        $objectId = $this->requestParser->getFromRequest('id');

        $browse   = $this->modelFactory->createBrowse();
        $playlist = $this->modelFactory->createPlaylist((int) $objectId);
        if ($playlist->isNew()) {
            return null;
        }
        $playlist->format();

        $object_ids = $playlist->get_items();

        $browse->set_type('playlist_media');
        $browse->add_supplemental_object('playlist', $playlist->getId());
        $browse->set_static_content(true);
        $browse->show_objects($object_ids);
        $browse->store();

        return null;
    }
}
