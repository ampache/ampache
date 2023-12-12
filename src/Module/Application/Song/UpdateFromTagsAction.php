<?php

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 *  LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
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

namespace Ampache\Module\Application\Song;

use Ampache\Config\ConfigContainerInterface;
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
        // Make sure they are a 'power' user at least
        if ($gatekeeper->mayAccess(AccessLevelEnum::TYPE_INTERFACE, AccessLevelEnum::LEVEL_CONTENT_MANAGER) === false) {
            throw new AccessDeniedException();
        }

        $songId = (int) ($request->getQueryParams()['song_id'] ?? 0);

        $song = $this->modelFactory->createSong($songId);
        $song->format();

        $this->ui->showHeader();
        $this->ui->show(
            'show_update_items.inc.php',
            [
                'object_id' => $songId,
                'catalog_id' => $song->getCatalogId(),
                'type' => 'song',
                'target_url' => sprintf(
                    '%s/song.php?action=show&amp;song_id=%d',
                    $this->configContainer->getWebPath(),
                    $songId
                )
            ]
        );
        $this->ui->showQueryStats();
        $this->ui->showFooter();

        return null;
    }
}
