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

namespace Ampache\Module\Application\Art;

use Ampache\Config\AmpConfig;
use Ampache\Repository\Model\Album;
use Ampache\Repository\Model\Art;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Module\Application\Exception\AccessDeniedException;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\System\Core;
use Ampache\Module\Util\UiInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Teapot\StatusCode;

final class SelectArtAction extends AbstractArtAction
{
    public const REQUEST_KEY = 'select_art';

    private ModelFactoryInterface $modelFactory;

    private ResponseFactoryInterface $responseFactory;

    private UiInterface $ui;

    public function __construct(
        ModelFactoryInterface $modelFactory,
        ResponseFactoryInterface $responseFactory,
        UiInterface $ui
    ) {
        parent::__construct($modelFactory);

        $this->modelFactory    = $modelFactory;
        $this->responseFactory = $responseFactory;
        $this->ui              = $ui;
    }

    public function run(ServerRequestInterface $request, GuiGatekeeperInterface $gatekeeper): ?ResponseInterface
    {
        $queryParams = $request->getQueryParams();

        /* Check to see if we have the image url still */
        $image_id = $queryParams['image'];

        $object_type = $queryParams['object_type'] ?? '';
        $object_id   = (int) ($queryParams['object_id'] ?? 0);

        $item = $this->getItem($gatekeeper, $object_type, $object_id);

        $object_id = $item->getId();

        $burl = '';
        if (filter_has_var(INPUT_GET, 'burl')) {
            $burl = base64_decode(Core::get_get('burl'));
        }

        // Prevent the script from timing out
        set_time_limit(0);

        $art_type   = (AmpConfig::get('show_song_art')) ? 'song' : 'album';
        $image      = Art::get_from_source($_SESSION['form']['images'][$image_id], $art_type);
        $dimensions = Core::image_dimensions($image);
        $mime       = $_SESSION['form']['images'][$image_id]['mime'];
        if (!Art::check_dimensions($dimensions)) {
            $this->ui->showHeader();

            $this->ui->showConfirmation(
                T_('There Was a Problem'),
                T_('Art file failed size check'),
                $burl
            );

            $this->ui->showQueryStats();
            $this->ui->showFooter();

            return null;
        }

        // Special case for albums, I'm not sure if we should keep it, remove it or find a generic way
        if ($object_type == 'album') {
            /** @var Album $album */
            $album = $this->modelFactory->mapObjectType(
                $object_type,
                (int) $object_id
            );
            $album_groups = $album->get_group_disks_ids();
            foreach ($album_groups as $a_id) {
                $art = $this->modelFactory->createArt($a_id, $object_type);
                $art->insert($image, $mime);
            }
        } else {
            $art = $this->modelFactory->createArt($object_id, $object_type);
            $art->insert($image, $mime);
        }

        return $this->responseFactory
            ->createResponse(StatusCode::FOUND)
            ->withHeader(
                'Location',
                $burl
            );
    }
}
