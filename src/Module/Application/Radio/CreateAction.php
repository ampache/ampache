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

namespace Ampache\Module\Application\Radio;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Module\Util\RequestParserInterface;
use Ampache\Repository\Model\Catalog;
use Ampache\Repository\Model\Live_Stream;
use Ampache\Module\Application\ApplicationActionInterface;
use Ampache\Module\Application\Exception\AccessDeniedException;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\Util\UiInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class CreateAction implements ApplicationActionInterface
{
    public const REQUEST_KEY = 'create';

    private ConfigContainerInterface $configContainer;

    private UiInterface $ui;

    private RequestParserInterface $requestParser;

    public function __construct(
        ConfigContainerInterface $configContainer,
        UiInterface $ui,
        RequestParserInterface $requestParser
    ) {
        $this->configContainer = $configContainer;
        $this->ui              = $ui;
        $this->requestParser   = $requestParser;
    }

    public function run(ServerRequestInterface $request, GuiGatekeeperInterface $gatekeeper): ?ResponseInterface
    {
        if (
            $this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::RADIO) === false ||
            $gatekeeper->mayAccess(AccessTypeEnum::INTERFACE, AccessLevelEnum::MANAGER) === false ||
            $this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::DEMO_MODE) === true ||
            !$this->requestParser->verifyForm('add_radio')
        ) {
            throw new AccessDeniedException();
        }

        $this->ui->showHeader();

        // Try to create the sucker
        if (!Live_Stream::create($_POST)) {
            $this->ui->show('show_add_live_stream.inc.php');
        } else {
            Catalog::update_mapping('live_stream');
            $body  = T_('Radio Station created');
            $title = '';
            $this->ui->showConfirmation(
                $title,
                $body,
                sprintf(
                    '%s/browse.php?action=live_stream',
                    $this->configContainer->getWebPath('/client')
                )
            );
        }

        $this->ui->showQueryStats();
        $this->ui->showFooter();

        return null;
    }
}
