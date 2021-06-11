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

namespace Ampache\Module\Application\TvShowSeason;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Module\Application\ApplicationActionInterface;
use Ampache\Module\Application\Exception\AccessDeniedException;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\Catalog\MediaDeletionCheckerInterface;
use Ampache\Module\TvShowSeason\Deletion\TvShowSeasonDeleterInterface;
use Ampache\Module\Util\UiInterface;
use Ampache\Repository\Model\ModelFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class ConfirmDeleteAction implements ApplicationActionInterface
{
    public const REQUEST_KEY = 'confirm_delete';

    private UiInterface $ui;

    private ConfigContainerInterface $configContainer;

    private ModelFactoryInterface $modelFactory;

    private MediaDeletionCheckerInterface $mediaDeletionChecker;

    private TvShowSeasonDeleterInterface $tvShowSeasonDeleter;

    public function __construct(
        UiInterface $ui,
        ConfigContainerInterface $configContainer,
        ModelFactoryInterface $modelFactory,
        MediaDeletionCheckerInterface $mediaDeletionChecker,
        TvShowSeasonDeleterInterface $tvShowSeasonDeleter
    ) {
        $this->ui                   = $ui;
        $this->configContainer      = $configContainer;
        $this->modelFactory         = $modelFactory;
        $this->mediaDeletionChecker = $mediaDeletionChecker;
        $this->tvShowSeasonDeleter  = $tvShowSeasonDeleter;
    }

    public function run(ServerRequestInterface $request, GuiGatekeeperInterface $gatekeeper): ?ResponseInterface
    {
        if ($this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::DEMO_MODE) === true) {
            return null;
        }

        $tvshowSeasonId = (int) ($request->getQueryParams()['tvshow_season_id'] ?? 0);

        $tvshow_season = $this->modelFactory->createTvShowSeason($tvshowSeasonId);

        if ($this->mediaDeletionChecker->mayDelete($tvshow_season, $gatekeeper->getUserId()) === false) {
            throw new AccessDeniedException(
                sprintf('Unauthorized to remove the tvshow `%s`', $tvshowSeasonId),
            );
        }

        $this->ui->showHeader();
        if ($this->tvShowSeasonDeleter->delete($tvshow_season)) {
            $this->ui->showConfirmation(
                T_('No Problem'),
                T_('TV Season has been deleted'),
                $this->configContainer->getWebPath()
            );
        } else {
            $this->ui->showConfirmation(
                T_('There Was a Problem'),
                T_('Couldn\'t delete this TV Season.'),
                $this->configContainer->getWebPath()
            );
        }
        $this->ui->showQueryStats();
        $this->ui->showFooter();

        return null;
    }
}
