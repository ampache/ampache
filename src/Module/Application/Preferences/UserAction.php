<?php
/*
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
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

namespace Ampache\Module\Application\Preferences;

use Ampache\Module\Util\QrCodeGeneratorInterface;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Module\Application\ApplicationActionInterface;
use Ampache\Module\Application\Exception\AccessDeniedException;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\Util\UiInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class UserAction implements ApplicationActionInterface
{
    public const REQUEST_KEY = 'user';

    private UiInterface $ui;

    private QrCodeGeneratorInterface $qrCodeGenerator;

    public function __construct(
        UiInterface $ui,
        QrCodeGeneratorInterface $qrCodeGenerator
    ) {
        $this->ui              = $ui;
        $this->qrCodeGenerator = $qrCodeGenerator;
    }

    public function run(ServerRequestInterface $request, GuiGatekeeperInterface $gatekeeper): ?ResponseInterface
    {
        if ($gatekeeper->mayAccess(AccessLevelEnum::TYPE_INTERFACE, AccessLevelEnum::LEVEL_ADMIN) === false) {
            throw new AccessDeniedException();
        }
        $queryParams = $request->getQueryParams();
        $tab         = $queryParams['tab'] ?? '0';

        $user = $gatekeeper->getUser();

        $apiKey       = $user->apikey;
        $apiKeyQrCode = '';
        if ($apiKey && $tab === 'account') {
            $apiKeyQrCode = $this->qrCodeGenerator->generate($apiKey, 156);
        }

        $this->ui->showHeader();
        $this->ui->show(
            'show_preferences.inc.php',
            [
                'fullname' => $user->fullname,
                'preferences' => $user->get_preferences($tab),
                'apiKeyQrCode' => $apiKeyQrCode,
                'ui' => $this->ui,
            ]
        );
        $this->ui->showQueryStats();
        $this->ui->showFooter();

        return null;
    }
}
