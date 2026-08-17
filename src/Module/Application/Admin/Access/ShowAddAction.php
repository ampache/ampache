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

namespace Ampache\Module\Application\Admin\Access;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Gui\Form\AddAccessFormView;
use Ampache\Module\Application\Admin\Access\Lib\AccessListTypeEnum;
use Ampache\Module\Application\ApplicationActionInterface;
use Ampache\Module\Application\Exception\AccessDeniedException;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\Util\RequestParserInterface;
use Ampache\Module\Util\UiInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final readonly class ShowAddAction implements ApplicationActionInterface
{
    public const string REQUEST_KEY = 'show_add';

    public function __construct(
        private UiInterface $ui,
        private ConfigContainerInterface $configContainer,
        private RequestParserInterface $requestParser,
    ) {}

    public function run(ServerRequestInterface $request, GuiGatekeeperInterface $gatekeeper): ?ResponseInterface
    {
        if ($gatekeeper->mayAccess(AccessTypeEnum::INTERFACE, AccessLevelEnum::ADMIN) === false) {
            throw new AccessDeniedException();
        }

        $this->ui->showHeader();
        $addType = (string) ($request->getQueryParams()['add_type'] ?? '');
        // "add current host" seeds both address fields with the caller's own address
        $currentIp = ($addType === AccessListTypeEnum::ADD_TYPE_CURRENT)
            ? (string) filter_var($_SERVER['REMOTE_ADDR'] ?? '', FILTER_VALIDATE_IP)
            : '';

        echo new AddAccessFormView(
            $this->configContainer->getWebPath('/admin'),
            $addType,
            $this->requestParser->getFromRequest('name'),
            $currentIp ?: $this->requestParser->getFromRequest('start'),
            $currentIp ?: $this->requestParser->getFromRequest('end'),
            $currentIp
        )->render();
        $this->ui->showQueryStats();
        $this->ui->showFooter();

        return null;
    }
}
