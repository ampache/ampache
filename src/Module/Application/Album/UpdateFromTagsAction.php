<?php

declare(strict_types=0);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2024
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

namespace Ampache\Module\Application\Album;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Module\Application\Exception\ObjectNotFoundException;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Module\Application\ApplicationActionInterface;
use Ampache\Module\Application\Exception\AccessDeniedException;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\Util\UiInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class UpdateFromTagsAction implements ApplicationActionInterface
{
    public const REQUEST_KEY = 'update_from_tags';

    private ModelFactoryInterface $modelFactory;

    private UiInterface $ui;

    private ConfigContainerInterface $configContainer;

    public function __construct(
        ModelFactoryInterface $modelFactory,
        UiInterface $ui,
        ConfigContainerInterface $configContainer
    ) {
        $this->modelFactory    = $modelFactory;
        $this->ui              = $ui;
        $this->configContainer = $configContainer;
    }

    public function run(ServerRequestInterface $request, GuiGatekeeperInterface $gatekeeper): ?ResponseInterface
    {
        $albumId = (int) ($request->getQueryParams()['album_id'] ?? 0);
        $album   = $this->modelFactory->createAlbum($albumId);
        if ($album->isNew()) {
            throw new ObjectNotFoundException($albumId);
        }

        // Make sure they are a 'power' user or the object owner
        if (
            $gatekeeper->mayAccess(AccessTypeEnum::INTERFACE, AccessLevelEnum::CONTENT_MANAGER) === false &&
            $gatekeeper->getUserId() !== $album->get_user_owner()
        ) {
            throw new AccessDeniedException();
        }

        $this->ui->showHeader();
        $this->ui->show(
            'show_update_items.inc.php',
            [
                'object_id' => $albumId,
                'catalog_id' => $album->getCatalogId(),
                'type' => 'album',
                'target_url' => sprintf(
                    '%s/albums.php?action=show&album=%d',
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
