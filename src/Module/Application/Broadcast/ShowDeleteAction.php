<?php

declare(strict_types=0);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
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

namespace Ampache\Module\Application\Broadcast;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Module\Application\ApplicationActionInterface;
use Ampache\Module\Application\Exception\AccessDeniedException;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\Util\RequestParserInterface;
use Ampache\Module\Util\UiInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class ShowDeleteAction implements ApplicationActionInterface
{
    public const REQUEST_KEY = 'show_delete';

    private RequestParserInterface $requestParser;

    private ConfigContainerInterface $configContainer;

    private UiInterface $ui;

    public function __construct(
        RequestParserInterface $requestParser,
        ConfigContainerInterface $configContainer,
        UiInterface $ui
    ) {
        $this->requestParser   = $requestParser;
        $this->configContainer = $configContainer;
        $this->ui              = $ui;
    }

    public function run(ServerRequestInterface $request, GuiGatekeeperInterface $gatekeeper): ?ResponseInterface
    {
        if ($this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::BROADCAST) === false) {
            throw new AccessDeniedException();
        }

        $this->ui->showHeader();

        $object_id = $this->requestParser->getFromRequest('id');

        $next_url = sprintf(
            '%s/broadcast.php?action=delete&id=%s',
            $this->configContainer->getWebPath(),
            scrub_out($object_id)
        );
        $this->ui->showConfirmation(
            T_('Are You Sure?'),
            T_('This Broadcast will be deleted'),
            $next_url,
            1,
            'delete_broadcast'
        );

        $this->ui->showQueryStats();
        $this->ui->showFooter();

        return null;
    }
}
