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

namespace Ampache\Module\Application\Collection;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Gui\Form\CreateCollectionFormView;
use Ampache\Module\Application\ApplicationActionInterface;
use Ampache\Module\Application\Exception\AccessDeniedException;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\Util\RequestParserInterface;
use Ampache\Module\Util\UiInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Shows the create-a-collection form
 */
final readonly class ShowCreateAction implements ApplicationActionInterface
{
    public const string REQUEST_KEY = 'show_create';

    public function __construct(
        private ConfigContainerInterface $configContainer,
        private UiInterface $ui,
        private RequestParserInterface $requestParser,
    ) {}

    public function run(ServerRequestInterface $request, GuiGatekeeperInterface $gatekeeper): ?ResponseInterface
    {
        // Anyone who may curate a list may own one, so this is the same level a playlist asks for
        if (
            $this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::SHOW_COLLECTION) === false
            || $gatekeeper->mayAccess(AccessTypeEnum::INTERFACE, AccessLevelEnum::USER) === false
        ) {
            throw new AccessDeniedException();
        }

        $this->ui->showHeader();
        echo new CreateCollectionFormView(
            $this->configContainer->getWebPath(),
            $this->requestParser->getFromRequest('name'),
            $this->requestParser->getFromRequest('type') ?: 'private',
            $this->requestParser->getFromRequest('object_type')
        )->render();
        $this->ui->showQueryStats();
        $this->ui->showFooter();

        return null;
    }
}
