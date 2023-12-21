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

use Ampache\Module\Application\ApplicationActionInterface;
use Ampache\Module\Application\Exception\AccessDeniedException;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\Util\InterfaceImplementationChecker;
use Ampache\Module\Util\RequestParserInterface;
use Ampache\Module\Util\UiInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class ShowAction implements ApplicationActionInterface
{
    public const REQUEST_KEY = 'show';

    private RequestParserInterface $requestParser;

    private UiInterface $ui;

    public function __construct(
        RequestParserInterface $requestParser,
        UiInterface $ui
    ) {
        $this->requestParser = $requestParser;
        $this->ui            = $ui;
    }

    public function run(ServerRequestInterface $request, GuiGatekeeperInterface $gatekeeper): ?ResponseInterface
    {
        session_start();

        $object_type = $this->requestParser->getFromRequest('action');
        if (!InterfaceImplementationChecker::is_library_item($object_type)) {
            throw new AccessDeniedException();
        }

        $this->ui->showHeader();
        $this->ui->show(
            'show_mashup.inc.php',
            ['object_type' => $object_type]
        );
        $this->ui->showQueryStats();
        $this->ui->showFooter();

        return null;
    }
}
