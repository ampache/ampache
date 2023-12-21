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

namespace Ampache\Module\Application\Mashup;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Module\Application\ApplicationActionInterface;
use Ampache\Module\Application\Exception\AccessDeniedException;
use Ampache\Module\Application\Exception\ObjectNotFoundException;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\Util\RequestParserInterface;
use Ampache\Module\Util\UiInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class WrappedAction implements ApplicationActionInterface
{
    public const REQUEST_KEY = 'wrapped';

    private ConfigContainerInterface $configContainer;

    private RequestParserInterface $requestParser;

    private UiInterface $ui;

    public function __construct(
        ConfigContainerInterface $configContainer,
        RequestParserInterface $requestParser,
        UiInterface $ui
    ) {
        $this->configContainer = $configContainer;
        $this->requestParser   = $requestParser;
        $this->ui              = $ui;
    }

    public function run(ServerRequestInterface $request, GuiGatekeeperInterface $gatekeeper): ?ResponseInterface
    {
        if (!$this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::SHOW_WRAPPED)) {
            throw new AccessDeniedException('Access Denied');
        }
        session_start();

        $userId = (int)$this->requestParser->getFromRequest('user_id');
        if ($userId === 0) {
            throw new ObjectNotFoundException('user_id');
        }
        $year = $this->requestParser->getFromRequest('year');
        if ($year === '') {
            $year = 'Y';
        }
        $startTime = strtotime(date($year . '-01-01'));
        if ($startTime === false) {
            throw new ObjectNotFoundException('year');
        }
        $endTime = strtotime(date($year . '-12-31')) ?: time();

        $this->ui->showHeader();
        $this->ui->show(
            'show_wrapped.inc.php',
            [
                'endTime' => $endTime,
                'startTime' => $startTime,
                'user_id' => $userId,
                'year' => (string)date($year),
            ]
        );
        $this->ui->showQueryStats();
        $this->ui->showFooter();

        return null;
    }
}
