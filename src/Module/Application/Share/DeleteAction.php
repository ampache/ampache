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

namespace Ampache\Module\Application\Share;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Module\Application\ApplicationActionInterface;
use Ampache\Module\Application\Exception\AccessDeniedException;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\Util\RequestParserInterface;
use Ampache\Module\Util\UiInterface;
use Ampache\Repository\ShareRepositoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Deletes a share-item
 */
final class DeleteAction implements ApplicationActionInterface
{
    public const REQUEST_KEY = 'delete';

    private RequestParserInterface $requestParser;

    private ConfigContainerInterface $configContainer;

    private UiInterface $ui;

    private ShareRepositoryInterface $shareRepository;

    public function __construct(
        RequestParserInterface $requestParser,
        ConfigContainerInterface $configContainer,
        UiInterface $ui,
        ShareRepositoryInterface $shareRepository
    ) {
        $this->requestParser   = $requestParser;
        $this->configContainer = $configContainer;
        $this->ui              = $ui;
        $this->shareRepository = $shareRepository;
    }

    public function run(ServerRequestInterface $request, GuiGatekeeperInterface $gatekeeper): ?ResponseInterface
    {
        if (!$this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::SHARE)) {
            throw new AccessDeniedException('Access Denied: sharing features are not enabled.');
        }
        if ($this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::DEMO_MODE)) {
            throw new AccessDeniedException();
        }

        $user = $gatekeeper->getUser();

        $share = $this->shareRepository->findById(
            (int) $this->requestParser->getFromRequest('id')
        );

        if (
            $share === null ||
            $user === null ||
            !$share->isAccessible($user)
        ) {
            throw new AccessDeniedException();
        }

        $this->shareRepository->delete($share);

        $this->ui->showHeader();
        $this->ui->showConfirmation(
            T_('No Problem'),
            T_('Share has been deleted'),
            sprintf('%s/stats.php?action=share', $this->configContainer->getWebPath())
        );
        $this->ui->showFooter();

        return null;
    }
}
