<?php
/*
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright 2001 - 2022 Ampache.org
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
        $this->modelFactory    = $modelFactory;
        $this->responseFactory = $responseFactory;
        $this->ui              = $ui;
    }

    public function run(ServerRequestInterface $request, GuiGatekeeperInterface $gatekeeper): ?ResponseInterface
    {
        /* Check to see if we have the image url still */
        $image_id = $_REQUEST['image'];

        $object_type = filter_input(INPUT_GET, 'object_type', FILTER_SANITIZE_SPECIAL_CHARS, FILTER_FLAG_NO_ENCODE_QUOTES);

        $item = $this->getItem($gatekeeper);
        if ($item === null) {
            throw new AccessDeniedException();
        }

        $burl = '';
        if (isset($_GET['burl'])) {
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

            $this->ui->showContinue(
                T_('There Was a Problem'),
                T_('Art file failed size check'),
                $burl
            );

            $this->ui->showQueryStats();
            $this->ui->showFooter();

            return null;
        }

        $art = $this->modelFactory->createArt($item->getId(), $object_type);
        $art->insert($image, $mime);

        return $this->responseFactory
            ->createResponse(StatusCode::FOUND)
            ->withHeader(
                'Location',
                $burl
            );
    }
}
