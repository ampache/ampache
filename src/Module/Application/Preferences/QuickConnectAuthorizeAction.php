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

namespace Ampache\Module\Application\Preferences;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Module\Application\ApplicationActionInterface;
use Ampache\Module\Application\Exception\AccessDeniedException;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\QuickConnect\QuickConnectService;
use Ampache\Module\Util\RequestParserInterface;
use Ampache\Module\Util\UiInterface;
use Ampache\Repository\Model\User;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Handles the web-UI QuickConnect approval form's POST — the human half of `POST /QuickConnect/Authorize`,
 * calling the same service directly rather than round-tripping through the Jellyfin API surface itself.
 */
final readonly class QuickConnectAuthorizeAction implements ApplicationActionInterface
{
    public const string REQUEST_KEY = 'quickconnect_authorize';

    public function __construct(
        private ConfigContainerInterface $configContainer,
        private RequestParserInterface $requestParser,
        private QuickConnectService $service,
        private UiInterface $ui,
    ) {}

    public function run(ServerRequestInterface $request, GuiGatekeeperInterface $gatekeeper): ?ResponseInterface
    {
        if (
            $gatekeeper->mayAccess(AccessTypeEnum::INTERFACE, AccessLevelEnum::USER) === false
            || !$this->requestParser->verifyForm('quickconnect_authorize')
        ) {
            throw new AccessDeniedException();
        }

        $user = $gatekeeper->getUser();

        $this->ui->showHeader();

        if ($user instanceof User) {
            $code = trim($this->requestParser->getFromRequest('code'));

            if ($code !== '' && $this->service->isEnabled()) {
                $result = $this->service->authorize($code, $user, null);
            } else {
                $result = ['success' => false, 'forbidden' => false];
            }

            [$title, $text] = ($result['success'])
                ? [T_('No Problem'), T_('Device approved')]
                : [T_('There Was a Problem'), T_('That code is invalid, expired, or already used')];

            $next_url = sprintf('%s/preferences.php?tab=quickconnect', $this->configContainer->getWebPath());
            $this->ui->showConfirmation($title, $text, $next_url);

            return null;
        }

        $this->ui->showQueryStats();
        $this->ui->showFooter();

        return null;
    }
}
