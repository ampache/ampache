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

namespace Ampache\Module\Application\Art;

use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\Util\UiInterface;
use Ampache\Repository\Model\ModelFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class ShowArtDlgAction extends AbstractArtAction
{
    public const REQUEST_KEY = 'show_art_dlg';

    private UiInterface $ui;

    public function __construct(
        UiInterface $ui,
        ModelFactoryInterface $modelFactory
    ) {
        parent::__construct($modelFactory);

        $this->ui = $ui;
    }

    public function run(ServerRequestInterface $request, GuiGatekeeperInterface $gatekeeper): ?ResponseInterface
    {
        $queryParams = $request->getQueryParams();

        $object_type = $queryParams['object_type'] ?? '';
        $object_id   = (int) ($queryParams['object_id'] ?? 0);

        $item = $this->getItem($gatekeeper, $object_type, $object_id);

        $burl = '';
        if (array_key_exists('burl', $queryParams)) {
            $burl = base64_decode($queryParams['burl']);
        }

        $this->ui->showHeader();
        $this->ui->show(
            'show_get_art.inc.php',
            [
                'item' => $item,
                'object_type' => $object_type,
                'object_id' => $item->getId(),
                'burl' => $burl
            ]
        );

        $this->ui->showQueryStats();
        $this->ui->showFooter();

        return null;
    }
}
