<?php

declare(strict_types=1);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2026
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

namespace Ampache\Module\Application\Index;

use Ampache\Config\AmpConfig;
use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Gui\Form\StatsFormViewFactoryInterface;
use Ampache\Gui\Index\HomeView;
use Ampache\Gui\Partial\JavascriptRefreshView;
use Ampache\Module\Application\ApplicationActionInterface;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\System\Core;
use Ampache\Module\Util\RequestParserInterface;
use Ampache\Module\Util\UiInterface;
use Ampache\Repository\Model\User;
use Ampache\Repository\VideoRepositoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final readonly class ShowAction implements ApplicationActionInterface
{
    public const string REQUEST_KEY = 'show';

    public function __construct(
        private RequestParserInterface $requestParser,
        private UiInterface $ui,
        private ConfigContainerInterface $configContainer,
        private VideoRepositoryInterface $videoRepository,
        private StatsFormViewFactoryInterface $statsFormViewFactory,
    ) {}

    public function run(ServerRequestInterface $request, GuiGatekeeperInterface $gatekeeper): ?ResponseInterface
    {
        $this->ui->showHeader();

        $this->requestParser->getFromRequest('action');

        if (!Core::is_session_started()) {
            session_start();
        }

        $_SESSION['catalog'] = 0;

        $refreshLimit = $this->configContainer->getInt(ConfigurationKeyEnum::REFRESH_LIMIT);

        /**
         * Check for the refresh mojo, if it's there then require the
         * refresh_javascript include. Must be greater then 5, I'm not
         * going to let them break their servers
         */
        if (
            $refreshLimit > 5
            && $this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::HOME_NOW_PLAYING)
        ) {
            echo new JavascriptRefreshView($refreshLimit, '?page=index&action=refresh_index')->render();
        }

        $user = Core::get_global('user');
        echo new HomeView(
            ($user instanceof User) ? $user : null,
            $this->statsFormViewFactory->createBrowse()->render(),
            $this->videoRepository,
            AmpConfig::get_web_path('/client'),
            $gatekeeper->mayAccess(AccessTypeEnum::INTERFACE, AccessLevelEnum::USER)
        )->render();

        // Show the Footer
        $this->ui->showQueryStats();
        $this->ui->showFooter();

        return null;
    }
}
