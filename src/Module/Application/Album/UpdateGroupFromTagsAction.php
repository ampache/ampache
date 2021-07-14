<?php
/*
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 *  LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
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

declare(strict_types=1);

namespace Ampache\Module\Application\Album;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Module\Application\ApplicationActionInterface;
use Ampache\Module\Application\Exception\AccessDeniedException;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\Util\UiInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class UpdateGroupFromTagsAction implements ApplicationActionInterface
{
    public const REQUEST_KEY = 'update_group_from_tags';

    private ConfigContainerInterface $configContainer;

    private ModelFactoryInterface $modelFactory;

    private UiInterface $ui;

    public function __construct(
        ConfigContainerInterface $configContainer,
        ModelFactoryInterface $modelFactory,
        UiInterface $ui
    ) {
        $this->configContainer = $configContainer;
        $this->modelFactory    = $modelFactory;
        $this->ui              = $ui;
    }

    public function run(ServerRequestInterface $request, GuiGatekeeperInterface $gatekeeper): ?ResponseInterface
    {
        // Make sure they are a 'power' user at least
        if ($gatekeeper->mayAccess(AccessLevelEnum::TYPE_INTERFACE, AccessLevelEnum::LEVEL_MANAGER) === false) {
            throw new AccessDeniedException();
        }

        $albumId = (int) $request->getQueryParams()['album_id'] ?? 0;

        $album = $this->modelFactory->createAlbum($albumId);
        $album->format();

        $this->ui->showHeader();
        $this->ui->show(
            'show_update_item_group.inc.php',
            [
                'catalog_id' => $album->get_catalogs(),
                'type' => 'album',
                'objects' => $album->album_suite,
                'target_url' => sprintf(
                    '%s/albums.php?action=show&amp;album=%d',
                    $this->configContainer->getWebPath(),
                    $albumId
                )
            ]
        );
        $this->ui->showQueryStats();
        $this->ui->showFooter();

        return null;
    }
}
