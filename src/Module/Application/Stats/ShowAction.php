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

namespace Ampache\Module\Application\Stats;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Gui\GuiFactoryInterface;
use Ampache\Gui\TalFactoryInterface;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\Util\UiInterface;
use Ampache\Repository\Model\LibraryItemLoaderInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final readonly class ShowAction extends AbstractGraphRendererAction
{
    public const REQUEST_KEY = 'show';

    public function __construct(
        private UiInterface $ui,
        private ConfigContainerInterface $configContainer,
        private GuiFactoryInterface $guiFactory,
        private TalFactoryInterface $talFactory,
        LibraryItemLoaderInterface $libraryItemLoader
    ) {
        parent::__construct(
            $libraryItemLoader,
        );
    }

    public function run(ServerRequestInterface $request, GuiGatekeeperInterface $gatekeeper): ?ResponseInterface
    {
        $this->ui->showHeader();

        define('TABLE_RENDERED', 1);

        // Temporary workaround to avoid sorting on custom base requests
        define('NO_BROWSE_SORTING', true);

        if ($gatekeeper->mayAccess(AccessTypeEnum::INTERFACE, AccessLevelEnum::CONTENT_MANAGER) === true) {
            $this->ui->showBoxTop(T_('Statistics'), 'box box_stats');

            echo $this->talFactory
                ->createTalView()
                ->setTemplate('stats.xhtml')
                ->setContext('STATS', $this->guiFactory->createStatsViewAdapter())
                ->render();

            $this->ui->showBoxBottom();

            if (
                $this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::STATISTICAL_GRAPHS) &&
                is_dir(__DIR__ . '/../../../../vendor/szymach/c-pchart/src/Chart/')
            ) {
                $this->renderGraph($gatekeeper);
            }
        }

        show_table_render(false, true);

        $this->ui->showQueryStats();
        $this->ui->showFooter();

        return null;
    }
}
