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
use Ampache\Model\Catalog;
use Ampache\Model\TVShow_Season;
use Ampache\Module\Application\ApplicationActionInterface;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\System\LegacyLogger;
use Ampache\Module\Util\UiInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;

final class ConfirmDeleteAction implements ApplicationActionInterface
{
    public const REQUEST_KEY = 'confirm_delete';
    
    private UiInterface $ui;

    private ConfigContainerInterface $configContainer;

    private LoggerInterface $logger;

    public function __construct(
        UiInterface $ui,
        ConfigContainerInterface $configContainer,
        LoggerInterface $logger
    ) {
        $this->ui              = $ui;
        $this->configContainer = $configContainer;
        $this->logger          = $logger;
    }

    public function run(ServerRequestInterface $request, GuiGatekeeperInterface $gatekeeper): ?ResponseInterface
    {
        if ($this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::DEMO_MODE) === true) {
            return null;
        }

        $tvshow_season = new TVShow_Season($_REQUEST['tvshow_season_id']);
        if (!Catalog::can_remove($tvshow_season)) {
            $this->logger->critical(
                sprintf('Unauthorized to remove the tvshow `%s`', $tvshow_season->id),
                [LegacyLogger::CONTEXT_TYPE => __CLASS__]
            );

            $this->ui->accessDenied();

            return null;
        }

        $this->ui->showHeader();

        if ($tvshow_season->remove()) {
            show_confirmation(
                T_('No Problem'),
                T_('TV Season has been deleted'),
                $this->configContainer->getWebPath()
            );
        } else {
            show_confirmation(
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
