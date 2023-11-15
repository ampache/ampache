<?php
/*
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

declare(strict_types=0);

namespace Ampache\Module\Api\RefreshReordered;

use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Module\Application\ApplicationActionInterface;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\Util\RequestParserInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class RefreshAlbumSongsAction implements ApplicationActionInterface
{
    public const REQUEST_KEY = 'refresh_album_songs';

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
        $object_id = $this->requestParser->getFromRequest('id');

        $browse = $this->modelFactory->createBrowse();

        $browse->set_show_header(true);
        $browse->set_type('song');
        $browse->set_simple_browse(true);
        $browse->set_filter('album', $object_id);
        $browse->set_sort('track', 'ASC');
        $browse->get_objects();
        echo "<div id='browse_content_song' class='browse_content'>";
        $browse->show_objects(array(), true); // true argument is set to show the reorder column
        $browse->store();
        echo "</div>";

        return null;
    }
}
