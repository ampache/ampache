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

namespace Ampache\Module\Application\WebPlayer;

use Ampache\Config\AmpConfig;
use Ampache\Gui\Playback\WebPlayerPageView;
use Ampache\Module\Application\ApplicationActionInterface;
use Ampache\Module\Authorization\Access;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\Util\AjaxUriRetrieverInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final readonly class ShowEmbeddedAction implements ApplicationActionInterface
{
    public const string REQUEST_KEY = 'show';

    public function __construct(
        private AjaxUriRetrieverInterface $ajaxUriRetriever,
    ) {}

    public function run(ServerRequestInterface $request, GuiGatekeeperInterface $gatekeeper): ?ResponseInterface
    {
        header('Cache-Control: no-cache');
        header('Pragma: no-cache');
        header('Expires: ' . gmdate(DATE_RFC1123, time() - 1));

        echo new WebPlayerPageView(
            AmpConfig::get_web_path('/client'),
            $this->ajaxUriRetriever,
            Access::check(AccessTypeEnum::INTERFACE, AccessLevelEnum::USER)
        )->render();

        return null;
    }
}
