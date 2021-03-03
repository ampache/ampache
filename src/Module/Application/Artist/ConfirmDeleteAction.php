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

namespace Ampache\Module\Application\Artist;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Module\Application\ApplicationActionInterface;
use Ampache\Module\Application\Exception\AccessDeniedException;
use Ampache\Module\Artist\Deletion\ArtistDeleterInterface;
use Ampache\Module\Artist\Deletion\Exception\ArtistDeletionException;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\Catalog\MediaDeletionCheckerInterface;
use Ampache\Module\Util\UiInterface;
use Ampache\Repository\Model\ModelFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class ConfirmDeleteAction implements ApplicationActionInterface
{
    public const REQUEST_KEY = 'confirm_delete';

    private ConfigContainerInterface $configContainer;

    private ModelFactoryInterface $modelFactory;

    private UiInterface $ui;

    private ArtistDeleterInterface $artistDeleter;

    private MediaDeletionCheckerInterface $mediaDeletionChecker;

    public function __construct(
        ConfigContainerInterface $configContainer,
        ModelFactoryInterface $modelFactory,
        UiInterface $ui,
        ArtistDeleterInterface $artistDeleter,
        MediaDeletionCheckerInterface $mediaDeletionChecker
    ) {
        $this->configContainer      = $configContainer;
        $this->modelFactory         = $modelFactory;
        $this->ui                   = $ui;
        $this->artistDeleter        = $artistDeleter;
        $this->mediaDeletionChecker = $mediaDeletionChecker;
    }

    public function run(ServerRequestInterface $request, GuiGatekeeperInterface $gatekeeper): ?ResponseInterface
    {
        $this->ui->showHeader();

        $response = null;

        if ($this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::DEMO_MODE)) {
            $this->ui->showQueryStats();
            $this->ui->showFooter();

            return $response;
        }

        $artistId = (int) ($request->getQueryParams()['artist_id'] ?? 0);

        $artist = $this->modelFactory->createArtist($artistId);

        if (!$this->mediaDeletionChecker->mayDelete($artist, $gatekeeper->getUserId())) {
            throw new AccessDeniedException(
                sprintf('Unauthorized to remove the artist `%d`', $artistId)
            );
        }

        try {
            $this->artistDeleter->remove($artist);

            $this->ui->showConfirmation(
                T_('No Problem'),
                T_('The Artist has been deleted'),
                $this->configContainer->getWebPath()
            );
        } catch (ArtistDeletionException $e) {
            $this->ui->showConfirmation(
                T_('There Was a Problem'),
                T_('Couldn\'t delete this Artist.'),
                $this->configContainer->getWebPath()
            );
        }

        $this->ui->showQueryStats();
        $this->ui->showFooter();

        return $response;
    }
}
